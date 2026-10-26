<?php

namespace Modules\Woocommerce\Services\Webhooks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Woocommerce\Jobs\ProcessWooCommerceWebhookReceipt;
use Modules\Woocommerce\Models\WooCommerceWebhookReceipt;

class WebhookReceiptRecovery
{
    public function markStaleProcessingRetryable(): int
    {
        $cutoff = now()->subSeconds((int) config('woocommerce.webhooks.stale_heartbeat_seconds', 900));
        return WooCommerceWebhookReceipt::query()
            ->where('status', 'processing')
            ->where(function ($query) use ($cutoff): void {
                $query->whereNull('processing_heartbeat_at')->orWhere('processing_heartbeat_at', '<', $cutoff);
            })
            ->update([
                'status' => 'failed_retryable',
                'failure_code' => 'processing_heartbeat_stale',
                'failure_message' => 'Processing heartbeat expired; controlled redispatch is required.',
                'recovery_claim' => null,
                'recovery_claimed_at' => null,
                'next_attempt_at' => now(),
            ]);
    }

    public function redispatch(WooCommerceWebhookReceipt $receipt): bool
    {
        $claim = (string) Str::uuid();
        $claimed = DB::transaction(function () use ($receipt, $claim): bool {
            $locked = WooCommerceWebhookReceipt::query()->with('setting')->lockForUpdate()->findOrFail($receipt->id);
            if (!in_array($locked->status, ['received', 'failed_retryable'], true)
                || !$locked->setting?->sync_enabled || $locked->setting->webhook_revoked_at
                || ($locked->next_attempt_at && $locked->next_attempt_at->isFuture())) {
                return false;
            }
            if ($locked->dispatch_attempt_count >= (int) config('woocommerce.webhooks.max_dispatch_attempts', 5)) {
                $locked->update(['status' => 'failed_terminal', 'failure_code' => 'dispatch_attempts_exhausted', 'failure_message' => 'Webhook dispatch exhausted its bounded recovery policy.']);
                return false;
            }
            return $locked->update([
                'status' => 'queued', 'failure_code' => null, 'failure_message' => null,
                'recovery_claim' => $claim, 'recovery_claimed_at' => now(), 'next_attempt_at' => null,
                'dispatch_attempt_count' => DB::raw('dispatch_attempt_count + 1'),
            ]);
        });
        if (!$claimed) {
            return false;
        }
        try {
            ProcessWooCommerceWebhookReceipt::dispatch($receipt->id, $claim);
            $receipt->newQuery()->whereKey($receipt->id)->where('recovery_claim', $claim)->update(['dispatched_at' => now()]);
            return true;
        } catch (\Throwable) {
            $receipt->newQuery()->whereKey($receipt->id)->where('recovery_claim', $claim)->update([
                'status' => 'failed_retryable', 'failure_code' => 'queue_dispatch_failed',
                'failure_message' => 'The receipt is durable and requires queue dispatch recovery.',
                'recovery_claim' => null, 'recovery_claimed_at' => null, 'next_attempt_at' => now()->addSeconds(30),
            ]);
            return false;
        }
    }
}
