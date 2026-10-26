<?php

namespace Modules\Woocommerce\Services\Webhooks;

use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use JsonException;
use Modules\Woocommerce\Exceptions\WebhookRejected;
use Modules\Woocommerce\Jobs\ProcessWooCommerceWebhookReceipt;
use Modules\Woocommerce\Models\WooCommerceWebhookReceipt;
use Modules\Woocommerce\Models\WoocommerceSetting;

class WooCommerceWebhookIntake
{
    public function __construct(private readonly WooCommerceSignatureVerifier $signatures) {}

    /** @return array{outcome:string,receipt_id:string,status:int} */
    public function receive(Request $request, string $publicId): array
    {
        if (!Str::isUuid($publicId)) {
            throw new WebhookRejected('connection_unavailable', 404);
        }

        $raw = $request->getContent();
        $bytes = strlen($raw);
        if ($bytes === 0) {
            throw new WebhookRejected('payload_empty', 400);
        }
        if ($bytes > (int) config('woocommerce.webhooks.max_payload_bytes', 1048576)) {
            throw new WebhookRejected('payload_too_large', 413);
        }
        if (!$request->isJson()) {
            throw new WebhookRejected('content_type_invalid', 415);
        }

        $setting = WoocommerceSetting::query()->where('public_webhook_id', $publicId)->first();
        if (!$setting || !$setting->sync_enabled || $setting->webhook_revoked_at) {
            throw new WebhookRejected('connection_unavailable', 404);
        }

        if (!RateLimiter::attempt(
            'woocommerce-webhook:connection:'.$setting->id,
            (int) config('woocommerce.webhooks.connection_requests_per_minute', 90),
            static fn () => true,
            60
        )) {
            throw new WebhookRejected('rate_limited', 429);
        }

        $key = $this->signatures->verify($setting, $raw, $this->header($request, 'X-WC-Webhook-Signature', 128, false));

        try {
            $payload = json_decode($raw, true, (int) config('woocommerce.webhooks.json_depth', 32), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new WebhookRejected('payload_json_invalid', 400);
        }
        if (!is_array($payload) || array_is_list($payload)) {
            throw new WebhookRejected('payload_not_object', 400);
        }

        $topic = strtolower($this->header($request, 'X-WC-Webhook-Topic', 100, true));
        if (!preg_match('/^[a-z][a-z0-9_-]*\.[a-z][a-z0-9_-]*$/', $topic)) {
            throw new WebhookRejected('topic_invalid', 400);
        }
        [$resource, $event] = explode('.', $topic, 2);
        $declaredResource = $this->header($request, 'X-WC-Webhook-Resource', 64, false);
        $declaredEvent = $this->header($request, 'X-WC-Webhook-Event', 64, false);
        if (($declaredResource !== null && strtolower($declaredResource) !== $resource)
            || ($declaredEvent !== null && strtolower($declaredEvent) !== $event)) {
            throw new WebhookRejected('topic_headers_mismatch', 400);
        }

        $entityId = isset($payload['id']) && (is_int($payload['id']) || is_string($payload['id']))
            ? substr((string) $payload['id'], 0, 191) : null;
        $hash = hash('sha256', $raw);
        $delivery = $this->header($request, 'X-WC-Webhook-Delivery-ID', 191, false);
        if ($delivery !== null && !preg_match('/^[A-Za-z0-9._:-]+$/', $delivery)) {
            throw new WebhookRejected('delivery_id_invalid', 400);
        }

        $sourceModified = $this->timestamp($payload['date_modified_gmt'] ?? $payload['date_modified'] ?? null);
        $sourceCreated = $this->timestamp($payload['date_created_gmt'] ?? $payload['date_created'] ?? null);
        $delivery ??= 'derived:'.hash('sha256', implode('|', [
            $setting->id, $topic, $entityId ?? '', $sourceModified?->toIso8601String() ?? $sourceCreated?->toIso8601String() ?? '', $hash,
        ]));
        $supported = in_array($topic, config('woocommerce.webhooks.supported_topics', []), true);

        $attributes = [
            'public_id' => (string) Str::uuid(),
            'woocommerce_setting_id' => $setting->id,
            'delivery_id' => $delivery,
            'topic' => $topic,
            'resource' => $resource,
            'event' => $event,
            'external_entity_id' => $entityId,
            'source_created_at' => $sourceCreated,
            'source_modified_at' => $sourceModified,
            'received_at' => now(),
            'payload_sha256' => $hash,
            'payload' => $raw,
            'payload_bytes' => $bytes,
            'header_evidence' => $this->headerEvidence($request),
            'signature_key_version' => $key['version'],
            'status' => $supported ? 'received' : 'unsupported',
            'failure_code' => $supported ? null : 'topic_unsupported',
            'failure_message' => $supported ? null : 'Authenticated topic is not enabled for processing.',
        ];

        $dispatchFailed = false;
        try {
            $receipt = DB::transaction(function () use ($attributes, $supported, &$dispatchFailed): WooCommerceWebhookReceipt {
                $receipt = WooCommerceWebhookReceipt::query()->create($attributes);
                if ($supported) {
                    DB::afterCommit(function () use ($receipt, &$dispatchFailed): void {
                        $claim = (string) Str::uuid();
                        try {
                            $receipt->newQuery()->whereKey($receipt->id)->update([
                                'status' => 'queued',
                                'recovery_claim' => $claim,
                                'recovery_claimed_at' => now(),
                                'dispatch_attempt_count' => DB::raw('dispatch_attempt_count + 1'),
                            ]);
                            ProcessWooCommerceWebhookReceipt::dispatch($receipt->id, $claim);
                            $receipt->newQuery()->whereKey($receipt->id)->update(['dispatched_at' => now()]);
                        } catch (\Throwable) {
                            $dispatchFailed = true;
                            $receipt->newQuery()->whereKey($receipt->id)->update([
                                'status' => 'failed_retryable',
                                'failure_code' => 'queue_dispatch_failed',
                                'failure_message' => 'The receipt is durable and requires queue dispatch recovery.',
                                'recovery_claim' => null,
                                'recovery_claimed_at' => null,
                                'next_attempt_at' => now()->addSeconds(30),
                            ]);
                        }
                    });
                }
                return $receipt;
            });
        } catch (QueryException $exception) {
            if (!$this->isUniqueViolation($exception)) {
                throw $exception;
            }
            $receipt = WooCommerceWebhookReceipt::query()
                ->where('woocommerce_setting_id', $setting->id)
                ->where('delivery_id', $delivery)
                ->firstOrFail();
            if (hash_equals($receipt->payload_sha256, $hash)) {
                return ['outcome' => 'duplicate', 'receipt_id' => $receipt->public_id, 'status' => 200];
            }
            $receipt->newQuery()->whereKey($receipt->id)->update([
                'conflict_count' => DB::raw('conflict_count + 1'),
                'last_conflicting_payload_sha256' => $hash,
                'last_conflict_at' => now(),
            ]);
            return ['outcome' => 'conflict', 'receipt_id' => $receipt->public_id, 'status' => 409];
        }

        if ($dispatchFailed) {
            return ['outcome' => 'retryable', 'receipt_id' => $receipt->public_id, 'status' => 503];
        }
        return ['outcome' => $supported ? 'accepted' : 'unsupported', 'receipt_id' => $receipt->public_id, 'status' => 202];
    }

    private function header(Request $request, string $name, int $max, bool $required): ?string
    {
        $value = $request->headers->get($name);
        if (!is_string($value) || $value === '') {
            if ($required) {
                throw new WebhookRejected('header_missing', 400);
            }
            return null;
        }
        if (strlen($value) > $max || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new WebhookRejected('header_invalid', 400);
        }
        return $value;
    }

    /** @return array<string,string> */
    private function headerEvidence(Request $request): array
    {
        $evidence = [];
        foreach (['X-WC-Webhook-ID' => 64, 'X-WC-Webhook-Delivery-ID' => 191, 'X-WC-Webhook-Topic' => 100, 'X-WC-Webhook-Resource' => 64, 'X-WC-Webhook-Event' => 64] as $name => $max) {
            if (($value = $this->header($request, $name, $max, false)) !== null) {
                $evidence[strtolower($name)] = $value;
            }
        }
        if (($source = $this->header($request, 'X-WC-Webhook-Source', 2048, false)) !== null) {
            $host = parse_url($source, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $evidence['x-wc-webhook-source-host'] = strtolower(substr($host, 0, 253));
            }
        }
        return $evidence;
    }

    private function timestamp(mixed $value): ?CarbonImmutable
    {
        if (!is_string($value) || strlen($value) > 64) {
            return null;
        }
        try {
            return CarbonImmutable::parse($value, 'UTC')->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true);
    }
}
