<?php

namespace Modules\Woocommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WoocommerceSetting extends Model
{
    use HasFactory;

    protected $hidden = [
        'woocomerce_consumer_key',
        'woocomerce_consumer_secret',
        'webhook_secret_order_created',
        'webhook_secret_order_updated',
        'webhook_secret_order_deleted',
        'webhook_secret_order_restored',
        'webhook_secret_current',
        'webhook_secret_previous',
        'sync_enabled',
        'legacy_activation_review_required',
    ];

    protected function casts(): array
    {
        return [
            'sync_enabled' => 'boolean',
            'legacy_activation_review_required' => 'boolean',
            'woocomerce_consumer_key' => 'encrypted',
            'woocomerce_consumer_secret' => 'encrypted',
            'webhook_secret_order_created' => 'encrypted',
            'webhook_secret_order_updated' => 'encrypted',
            'webhook_secret_order_deleted' => 'encrypted',
            'webhook_secret_order_restored' => 'encrypted',
            'webhook_secret_current' => 'encrypted',
            'webhook_secret_previous' => 'encrypted',
            'webhook_previous_expires_at' => 'immutable_datetime',
            'webhook_revoked_at' => 'immutable_datetime',
        ];
    }

    protected $fillable =[
        'sync_enabled',
        'woocomerce_app_url',
        'woocomerce_consumer_key',
        'woocomerce_consumer_secret',
        'default_tax_class',
        'product_tax_type',
        'manage_stock',
        'stock_status',
        'product_status',
        'customer_group_id',
        'warehouse_id',
        'biller_id',
        'order_status_pending',
        'order_status_processing',
        'order_status_on_hold',
        'order_status_completed',
        'order_status_draft',
        'webhook_secret_order_created',
        'webhook_secret_order_updated',
        'webhook_secret_order_deleted',
        'webhook_secret_order_restored',
        'webhook_secret_current',
        'webhook_secret_previous',
        'webhook_previous_expires_at',
        'webhook_key_version',
        'webhook_revoked_at',
        'webhook_configuration_error',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $setting): void {
            $setting->public_webhook_id ??= (string) Str::uuid();
        });
    }
}
