<?php

namespace Modules\Woocommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Modules\Woocommerce\Models\WooCommerceWebhookReceipt;

class ProcessWooCommerceWebhookReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public function __construct(public readonly int $receiptId, public readonly string $claim) {}

    public function uniqueId(): string
    {
        return (string) $this->receiptId;
    }

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function middleware(): array
    {
        $receipt = WooCommerceWebhookReceipt::query()->find($this->receiptId);
        $key = $receipt ? implode(':', [$receipt->woocommerce_setting_id, $receipt->topic, $receipt->external_entity_id ?: 'none']) : 'missing:'.$this->receiptId;
        return [(new WithoutOverlapping('woocommerce-webhook:'.$key))->releaseAfter(30)->expireAfter(300)];
    }

    public function handle(): void
    {
        $updated = WooCommerceWebhookReceipt::query()
            ->whereKey($this->receiptId)
            ->where('recovery_claim', $this->claim)
            ->where('status', 'queued')
            ->update([
            'status' => 'failed_terminal',
            'attempt_count' => \DB::raw('attempt_count + 1'),
            'last_attempted_at' => now(),
            'processing_heartbeat_at' => now(),
            'failure_code' => 'entity_processing_not_implemented',
            'failure_message' => 'Authenticated receipt retained; business processing is not implemented in this phase.',
            'recovery_claim' => null,
            'recovery_claimed_at' => null,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        WooCommerceWebhookReceipt::query()->whereKey($this->receiptId)->where('recovery_claim', $this->claim)->update([
            'status' => 'failed_terminal',
            'failure_code' => 'processing_attempts_exhausted',
            'failure_message' => 'Webhook processing exhausted its bounded retry policy.',
            'processing_heartbeat_at' => null,
        ]);
    }
}
