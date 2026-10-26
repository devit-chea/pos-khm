<?php

namespace Modules\Woocommerce\Services\Webhooks;

use Modules\Woocommerce\Models\WooCommerceWebhookReceipt;

class WebhookEventOrdering
{
    private const PRECEDENCE = ['created' => 10, 'updated' => 20, 'deleted' => 30, 'restored' => 40];

    public function compare(WooCommerceWebhookReceipt $candidate, WooCommerceWebhookReceipt $applied): ?int
    {
        $left = $candidate->source_modified_at ?? $candidate->source_created_at;
        $right = $applied->source_modified_at ?? $applied->source_created_at;
        if (!$left || !$right) {
            return null;
        }

        $time = $left->getTimestamp() <=> $right->getTimestamp();
        if ($time !== 0) {
            return $time;
        }

        $event = (self::PRECEDENCE[$candidate->event] ?? 0) <=> (self::PRECEDENCE[$applied->event] ?? 0);
        return $event !== 0 ? $event : strcmp($candidate->payload_sha256, $applied->payload_sha256);
    }

    public function classifyAgainstApplied(WooCommerceWebhookReceipt $candidate, ?WooCommerceWebhookReceipt $applied): string
    {
        if (!$applied || $applied->status !== 'processed') {
            return 'reviewable';
        }
        $comparison = $this->compare($candidate, $applied);
        return $comparison === null ? 'reviewable' : ($comparison < 0 ? 'stale' : 'current');
    }
}
