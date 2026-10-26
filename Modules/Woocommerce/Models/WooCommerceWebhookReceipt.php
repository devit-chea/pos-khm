<?php

namespace Modules\Woocommerce\Models;

use Illuminate\Database\Eloquent\Model;

class WooCommerceWebhookReceipt extends Model
{
    protected $table = 'woocommerce_webhook_receipts';

    protected $guarded = [];

    protected $hidden = ['payload', 'payload_sha256', 'header_evidence', 'last_conflicting_payload_sha256'];

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted',
            'header_evidence' => 'array',
            'received_at' => 'immutable_datetime',
            'source_created_at' => 'immutable_datetime',
            'source_modified_at' => 'immutable_datetime',
            'last_attempted_at' => 'immutable_datetime',
            'processing_heartbeat_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
            'recovery_claimed_at' => 'immutable_datetime',
            'next_attempt_at' => 'immutable_datetime',
            'dispatched_at' => 'immutable_datetime',
            'last_conflict_at' => 'immutable_datetime',
        ];
    }

    public function setting()
    {
        return $this->belongsTo(WoocommerceSetting::class, 'woocommerce_setting_id');
    }
}
