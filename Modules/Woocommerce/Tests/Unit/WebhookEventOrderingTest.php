<?php

namespace Modules\Woocommerce\Tests\Unit;

use Carbon\CarbonImmutable;
use Modules\Woocommerce\Models\WooCommerceWebhookReceipt;
use Modules\Woocommerce\Services\Webhooks\WebhookEventOrdering;
use Tests\TestCase;

class WebhookEventOrderingTest extends TestCase
{
    public function test_only_newer_successfully_applied_state_can_make_event_stale(): void
    {
        $ordering = new WebhookEventOrdering();
        $older = $this->receipt('updated', '2026-01-01T00:00:00Z', 'a');
        $newer = $this->receipt('updated', '2026-01-02T00:00:00Z', 'b');
        self::assertSame('reviewable', $ordering->classifyAgainstApplied($older, $newer));
        $newer->status = 'processed';
        self::assertSame('stale', $ordering->classifyAgainstApplied($older, $newer));
        self::assertSame('current', $ordering->classifyAgainstApplied($newer, $older->forceFill(['status' => 'processed'])));
    }

    public function test_equal_timestamps_use_delete_restore_precedence_then_hash(): void
    {
        $ordering = new WebhookEventOrdering();
        $update = $this->receipt('updated', '2026-01-01T00:00:00Z', 'a');
        $delete = $this->receipt('deleted', '2026-01-01T00:00:00Z', 'b');
        $restore = $this->receipt('restored', '2026-01-01T00:00:00Z', 'c');
        self::assertGreaterThan(0, $ordering->compare($delete, $update));
        self::assertGreaterThan(0, $ordering->compare($restore, $delete));
    }

    public function test_missing_timestamp_remains_reviewable(): void
    {
        $candidate = new WooCommerceWebhookReceipt(['event' => 'updated', 'payload_sha256' => 'a']);
        $applied = $this->receipt('updated', '2026-01-01T00:00:00Z', 'b')->forceFill(['status' => 'processed']);
        self::assertSame('reviewable', (new WebhookEventOrdering())->classifyAgainstApplied($candidate, $applied));
    }

    private function receipt(string $event, string $time, string $hash): WooCommerceWebhookReceipt
    {
        return new WooCommerceWebhookReceipt([
            'event' => $event,
            'source_modified_at' => CarbonImmutable::parse($time),
            'payload_sha256' => str_repeat($hash, 64),
            'status' => 'queued',
        ]);
    }
}
