<?php

namespace Modules\Woocommerce\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Woocommerce\Http\Controllers\WooCommerceWebhookController;
use Modules\Woocommerce\Jobs\ProcessWooCommerceWebhookReceipt;
use Modules\Woocommerce\Models\WooCommerceWebhookReceipt;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Services\Webhooks\WebhookSecretLifecycle;
use Modules\Woocommerce\Services\Webhooks\WebhookReceiptRecovery;
use Tests\TestCase;

class WooCommerceWebhookTrustBoundaryTest extends TestCase
{
    private array $settingIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'woocommerce.webhooks.max_payload_bytes' => 1048576,
            'woocommerce.webhooks.json_depth' => 32,
            'woocommerce.webhooks.connection_requests_per_minute' => 90,
            'woocommerce.webhooks.supported_topics' => ['order.created', 'order.updated', 'order.deleted', 'order.restored'],
        ]);
        Route::post('/_webhook-test/{publicId}', [WooCommerceWebhookController::class, 'store']);
        Queue::fake();
    }

    protected function tearDown(): void
    {
        if ($this->settingIds !== []) {
            DB::table('woocommerce_webhook_security_events')->whereIn('woocommerce_setting_id', $this->settingIds)->delete();
            DB::table('woocommerce_webhook_receipts')->whereIn('woocommerce_setting_id', $this->settingIds)->delete();
            DB::table('woocommerce_settings')->whereIn('id', $this->settingIds)->delete();
        }
        parent::tearDown();
    }

    public function test_post_only_valid_raw_signature_stores_encrypted_receipt_and_dispatches_once(): void
    {
        $setting = $this->setting('current-secret');
        $body = '{"id":42,"date_modified_gmt":"2026-09-12T10:00:00Z","name":"雪"}';
        $before = $this->businessCounts();

        $response = $this->signedPost($setting, $body, 'delivery-1');
        $response->assertAccepted()->assertJson(['outcome' => 'accepted']);
        $this->get('/_webhook-test/'.$setting->public_webhook_id)->assertStatus(405);

        $receipt = WooCommerceWebhookReceipt::query()->where('delivery_id', 'delivery-1')->firstOrFail();
        self::assertSame('queued', $receipt->status);
        self::assertSame($body, $receipt->payload);
        self::assertArrayNotHasKey('payload', $receipt->toArray());
        self::assertStringNotContainsString($body, (string) DB::table('woocommerce_webhook_receipts')->where('id', $receipt->id)->value('payload'));
        self::assertArrayNotHasKey('x-wc-webhook-signature', $receipt->header_evidence);
        Queue::assertPushed(ProcessWooCommerceWebhookReceipt::class, 1);
        self::assertSame($before, $this->businessCounts());
    }

    public function test_signature_is_checked_before_json_and_invalid_requests_leave_no_receipt(): void
    {
        $setting = $this->setting('secret-a');
        $invalidJson = '{not-json';
        $headers = $this->headers('delivery-invalid', $invalidJson, 'wrong-secret');
        $this->call('POST', '/_webhook-test/'.$setting->public_webhook_id, [], [], [], $headers, $invalidJson)
            ->assertStatus(401)->assertJson(['code' => 'signature_invalid']);
        self::assertSame(0, WooCommerceWebhookReceipt::query()->where('woocommerce_setting_id', $setting->id)->count());
        Queue::assertNothingPushed();
    }

    public function test_missing_malformed_modified_and_whitespace_signatures_are_rejected(): void
    {
        $setting = $this->setting('secret-b');
        $body = '{"id":1}';
        $base = $this->headers('delivery-x', $body, 'secret-b');

        $missing = $base;
        unset($missing['HTTP_X_WC_WEBHOOK_SIGNATURE']);
        $this->call('POST', '/_webhook-test/'.$setting->public_webhook_id, [], [], [], $missing, $body)->assertStatus(401);
        $malformed = $base;
        $malformed['HTTP_X_WC_WEBHOOK_SIGNATURE'] = '%%%';
        $this->call('POST', '/_webhook-test/'.$setting->public_webhook_id, [], [], [], $malformed, $body)->assertStatus(401);
        $this->call('POST', '/_webhook-test/'.$setting->public_webhook_id, [], [], [], $base, $body.' ')->assertStatus(401);
        $this->call('POST', '/_webhook-test/'.$setting->public_webhook_id, [], [], [], $base, str_replace('}', "\r\n}", $body))->assertStatus(401);
        self::assertSame(0, WooCommerceWebhookReceipt::query()->where('woocommerce_setting_id', $setting->id)->count());
    }

    public function test_content_size_empty_unknown_disabled_and_revoked_fail_closed(): void
    {
        $setting = $this->setting('secret-c');
        $this->call('POST', '/_webhook-test/'.$setting->public_webhook_id, [], [], [], ['CONTENT_TYPE' => 'text/plain'], '{}')->assertStatus(415);
        $this->call('POST', '/_webhook-test/'.$setting->public_webhook_id, [], [], [], ['CONTENT_TYPE' => 'application/json'], '')->assertStatus(400);
        config(['woocommerce.webhooks.max_payload_bytes' => 5]);
        $this->signedPost($setting, '{"id":1}', 'too-large')->assertStatus(413);
        config(['woocommerce.webhooks.max_payload_bytes' => 1048576]);
        $this->postJson('/_webhook-test/'.Str::uuid(), ['id' => 1])->assertStatus(404);
        $setting->update(['sync_enabled' => false]);
        $this->signedPost($setting->fresh(), '{}', 'disabled')->assertStatus(404);
        $setting->update(['sync_enabled' => true, 'webhook_revoked_at' => now()]);
        $this->signedPost($setting->fresh(), '{}', 'revoked')->assertStatus(404);
        Queue::assertNothingPushed();
    }

    public function test_identical_duplicate_is_idempotent_and_conflict_preserves_original(): void
    {
        $setting = $this->setting('secret-d');
        $first = '{"id":7,"date_modified_gmt":"2026-09-12T10:00:00Z"}';
        $second = '{"id":7,"date_modified_gmt":"2026-09-12T10:01:00Z"}';
        $this->signedPost($setting, $first, 'delivery-race')->assertAccepted();
        $this->signedPost($setting, $first, 'delivery-race')->assertOk()->assertJson(['outcome' => 'duplicate']);
        $this->signedPost($setting, $second, 'delivery-race')->assertStatus(409)->assertJson(['outcome' => 'conflict']);

        self::assertSame(1, WooCommerceWebhookReceipt::query()->where('woocommerce_setting_id', $setting->id)->count());
        $receipt = WooCommerceWebhookReceipt::query()->where('woocommerce_setting_id', $setting->id)->firstOrFail();
        self::assertSame($first, $receipt->payload);
        self::assertSame(1, $receipt->conflict_count);
        Queue::assertPushed(ProcessWooCommerceWebhookReceipt::class, 1);
    }

    public function test_authenticated_unsupported_topic_is_retained_without_dispatch(): void
    {
        $setting = $this->setting('secret-e');
        $this->signedPost($setting, '{"id":9}', 'unsupported-1', 'customer.updated')
            ->assertAccepted()->assertJson(['outcome' => 'unsupported']);
        self::assertSame('unsupported', WooCommerceWebhookReceipt::query()->where('woocommerce_setting_id', $setting->id)->value('status'));
        Queue::assertNothingPushed();
    }

    public function test_secret_rotation_blank_preservation_expiry_and_revocation(): void
    {
        $setting = $this->setting('old-secret');
        $lifecycle = app(WebhookSecretLifecycle::class);
        $lifecycle->replace($setting, '', 60);
        self::assertSame('old-secret', $setting->fresh()->webhook_secret_current);
        $lifecycle->replace($setting, 'new-secret', 3600);
        $setting = $setting->fresh();
        self::assertSame('old-secret', $setting->webhook_secret_previous);
        $this->signedPost($setting, '{"id":10}', 'old-key', 'order.updated', 'old-secret')->assertAccepted();
        $this->signedPost($setting, '{"id":11}', 'new-key', 'order.updated', 'new-secret')->assertAccepted();
        $setting->update(['webhook_previous_expires_at' => now()->subSecond()]);
        $this->signedPost($setting->fresh(), '{"id":12}', 'expired-key', 'order.updated', 'old-secret')->assertStatus(401);
        $lifecycle->revoke($setting->fresh());
        $this->signedPost($setting->fresh(), '{"id":13}', 'revoked-key', 'order.updated', 'new-secret')->assertStatus(404);
        self::assertArrayNotHasKey('webhook_secret_current', $setting->fresh()->toArray());
    }

    public function test_queue_failure_preserves_receipt_as_retryable_and_recovery_dispatches_once(): void
    {
        $setting = $this->setting('queue-secret');
        Queue::swap(new ThrowingWebhookTestQueue());
        $this->signedPost($setting, '{"id":20}', 'queue-failure')->assertStatus(503)->assertJson(['outcome' => 'retryable']);
        $receipt = WooCommerceWebhookReceipt::query()->where('delivery_id', 'queue-failure')->firstOrFail();
        self::assertSame('failed_retryable', $receipt->status);
        self::assertSame('queue_dispatch_failed', $receipt->failure_code);
        self::assertSame(1, $receipt->dispatch_attempt_count);
        $receipt->update(['next_attempt_at' => now()->subSecond()]);

        Queue::fake();
        self::assertTrue(app(WebhookReceiptRecovery::class)->redispatch($receipt->fresh()));
        self::assertSame(2, $receipt->fresh()->dispatch_attempt_count);
        Queue::assertPushed(ProcessWooCommerceWebhookReceipt::class, 1);
        self::assertFalse(app(WebhookReceiptRecovery::class)->redispatch($receipt->fresh()));
        Queue::assertPushed(ProcessWooCommerceWebhookReceipt::class, 1);
    }

    public function test_corrupt_encrypted_secret_fails_closed_and_sets_sanitized_health_state(): void
    {
        $setting = $this->setting('never-exposed');
        DB::table('woocommerce_settings')->where('id', $setting->id)->update(['webhook_secret_current' => 'corrupt-ciphertext']);
        $body = '{"id":21}';
        $this->call('POST', '/_webhook-test/'.$setting->public_webhook_id, [], [], [], $this->headers('corrupt-secret', $body, 'never-exposed'), $body)
            ->assertStatus(404)->assertJson(['code' => 'connection_unavailable']);
        self::assertSame('webhook_secret_decryption_failed', DB::table('woocommerce_settings')->where('id', $setting->id)->value('webhook_configuration_error'));
        self::assertSame(0, WooCommerceWebhookReceipt::query()->where('woocommerce_setting_id', $setting->id)->count());
        Queue::assertNothingPushed();
    }

    public function test_rotation_slots_are_independently_fail_closed_and_invalid_expiry_is_ignored(): void
    {
        $setting = $this->setting('valid-current-secret');
        $setting->forceFill(['webhook_secret_previous' => 'valid-previous-secret', 'webhook_previous_expires_at' => now()->addHour(), 'webhook_key_version' => 2])->save();
        DB::table('woocommerce_settings')->where('id', $setting->id)->update(['webhook_secret_previous' => 'corrupt-previous']);
        $this->signedPost($setting->fresh(), '{"id":31}', 'valid-current-corrupt-previous', 'order.updated', 'valid-current-secret')->assertAccepted();
        self::assertSame('webhook_secret_decryption_failed', DB::table('woocommerce_settings')->where('id', $setting->id)->value('webhook_configuration_error'));

        $previousSetting = $this->setting('temporary-current');
        $previousSetting->forceFill(['webhook_secret_previous' => 'valid-previous-secret', 'webhook_previous_expires_at' => now()->addHour(), 'webhook_key_version' => 2])->save();
        DB::table('woocommerce_settings')->where('id', $previousSetting->id)->update(['webhook_secret_current' => 'corrupt-current']);
        $this->signedPost($previousSetting->fresh(), '{"id":32}', 'corrupt-current-valid-previous', 'order.updated', 'valid-previous-secret')->assertAccepted();

        DB::table('woocommerce_settings')->where('id', $previousSetting->id)->update(['webhook_secret_previous' => 'also-corrupt']);
        $this->signedPost($previousSetting->fresh(), '{"id":33}', 'both-corrupt', 'order.updated', 'synthetic-wrong-secret')->assertStatus(404);

        $expirySetting = $this->setting('valid-current-secret');
        $expirySetting->forceFill(['webhook_secret_previous' => 'unused-previous'])->save();
        try {
            DB::table('woocommerce_settings')->where('id', $expirySetting->id)->update(['webhook_previous_expires_at' => 'not-a-date']);
            self::fail('Database accepted malformed rotation expiry metadata.');
        } catch (\Illuminate\Database\QueryException) {
            self::assertNull($expirySetting->fresh()->webhook_previous_expires_at);
        }
        $this->signedPost($expirySetting->fresh(), '{"id":34}', 'invalid-expiry', 'order.updated', 'valid-current-secret')->assertAccepted();
        self::assertNull(DB::table('woocommerce_settings')->where('id', $expirySetting->id)->value('webhook_configuration_error'));
    }

    public function test_stale_worker_claim_cannot_overwrite_recovery_owner(): void
    {
        $setting = $this->setting('claim-secret');
        $this->signedPost($setting, '{"id":40}', 'claim-owner')->assertAccepted();
        $receipt = WooCommerceWebhookReceipt::query()->where('delivery_id', 'claim-owner')->firstOrFail();
        $oldClaim = $receipt->recovery_claim;
        $newClaim = (string) Str::uuid();
        $receipt->update(['recovery_claim' => $newClaim, 'status' => 'queued']);
        (new ProcessWooCommerceWebhookReceipt($receipt->id, $oldClaim))->handle();
        $receipt->refresh();
        self::assertSame('queued', $receipt->status);
        self::assertSame($newClaim, $receipt->recovery_claim);
        self::assertSame(0, $receipt->attempt_count);
    }

    private function setting(string $secret): WoocommerceSetting
    {
        $setting = WoocommerceSetting::query()->create([
            'sync_enabled' => true,
            'webhook_secret_current' => $secret,
            'webhook_key_version' => 1,
        ]);
        $this->settingIds[] = $setting->id;
        return $setting;
    }

    private function signedPost(WoocommerceSetting $setting, string $body, string $delivery, string $topic = 'order.updated', ?string $secret = null)
    {
        return $this->call('POST', '/_webhook-test/'.$setting->public_webhook_id, [], [], [], $this->headers($delivery, $body, $secret ?? $setting->webhook_secret_current, $topic), $body);
    }

    private function headers(string $delivery, string $body, string $secret, string $topic = 'order.updated'): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => $delivery,
            'HTTP_X_WC_WEBHOOK_TOPIC' => $topic,
            'HTTP_X_WC_WEBHOOK_RESOURCE' => explode('.', $topic)[0],
            'HTTP_X_WC_WEBHOOK_EVENT' => explode('.', $topic)[1],
            'HTTP_X_WC_WEBHOOK_SOURCE' => 'https://store.example.test/path?secret=redacted',
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => base64_encode(hash_hmac('sha256', $body, $secret, true)),
        ];
    }

    private function businessCounts(): array
    {
        $result = [];
        foreach (['products', 'product_variants', 'categories', 'customers', 'sales', 'payments', 'returns', 'product_returns', 'product_warehouse', 'product_batches', 'reward_points', 'deposits', 'journal_entries', 'journal_lines'] as $table) {
            if (\Schema::hasTable($table)) {
                $rows = DB::table($table)->orderBy(\Schema::hasColumn($table, 'id') ? 'id' : \Schema::getColumnListing($table)[0])->get();
                $result[$table] = hash('sha256', $rows->toJson());
            }
        }
        return $result;
    }
}

class ThrowingWebhookTestQueue extends \Illuminate\Queue\Queue implements \Illuminate\Contracts\Queue\Queue
{
    public function size($queue = null): int { return 0; }
    public function push($job, $data = '', $queue = null): never { throw new \RuntimeException('synthetic queue outage'); }
    public function pushRaw($payload, $queue = null, array $options = []): never { throw new \RuntimeException('synthetic queue outage'); }
    public function later($delay, $job, $data = '', $queue = null): never { throw new \RuntimeException('synthetic queue outage'); }
    public function pop($queue = null): mixed { return null; }
    public function getConnectionName(): string { return 'throwing-test'; }
    public function setConnectionName($name): static { return $this; }
}
