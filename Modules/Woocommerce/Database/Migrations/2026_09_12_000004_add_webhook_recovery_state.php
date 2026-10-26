<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('woocommerce_webhook_receipts', 'recovery_claim')) {
            Schema::table('woocommerce_webhook_receipts', function (Blueprint $table): void {
                $table->uuid('recovery_claim')->nullable()->unique();
                $table->timestamp('recovery_claimed_at')->nullable()->index();
                $table->timestamp('next_attempt_at')->nullable()->index();
                $table->unsignedInteger('dispatch_attempt_count')->default(0);
                $table->timestamp('dispatched_at')->nullable();
            });
        }
        if (!Schema::hasColumn('woocommerce_settings', 'webhook_configuration_error')) {
            Schema::table('woocommerce_settings', function (Blueprint $table): void {
                $table->string('webhook_configuration_error', 64)->nullable();
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Webhook recovery state protects retained audit evidence and requires explicit downgrade review.');
    }
};
