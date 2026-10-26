<?php

namespace Modules\Ecommerce\Entities;

use Illuminate\Database\Eloquent\Model;

class EcommercePaymentAttempt extends Model
{
    protected $fillable = [
        'public_id', 'sale_id', 'payment_id', 'provider', 'expected_amount',
        'currency_id', 'currency_code', 'exchange_rate', 'provider_amount',
        'minor_unit', 'provider_reference', 'provider_transaction_id', 'state',
        'idempotency_key', 'verification_data', 'failure_reason', 'verified_at',
        'failed_at',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:8',
        'provider_amount' => 'decimal:4',
        'verification_data' => 'array',
        'verified_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
