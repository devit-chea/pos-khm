<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('woocommerce_settings', 'public_webhook_id')) {
            Schema::table('woocommerce_settings', function (Blueprint $table): void {
                $table->uuid('public_webhook_id')->nullable()->unique();
                $table->text('webhook_secret_current')->nullable();
                $table->text('webhook_secret_previous')->nullable();
                $table->timestamp('webhook_previous_expires_at')->nullable();
                $table->unsignedInteger('webhook_key_version')->default(1);
                $table->timestamp('webhook_revoked_at')->nullable();
            });

            DB::table('woocommerce_settings')->orderBy('id')->each(function ($row): void {
                DB::table('woocommerce_settings')->where('id', $row->id)->update([
                    'public_webhook_id' => (string) Str::uuid(),
                    'webhook_secret_current' => $row->webhook_secret_order_created ?: null,
                ]);
            });
        }

        if (!Schema::hasTable('woocommerce_webhook_receipts')) {
            Schema::create('woocommerce_webhook_receipts', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->uuid('public_id')->unique();
                $table->unsignedBigInteger('woocommerce_setting_id');
                $table->string('delivery_id', 191);
                $table->string('topic', 100);
                $table->string('resource', 64)->nullable();
                $table->string('event', 64)->nullable();
                $table->string('external_entity_id', 191)->nullable();
                $table->timestamp('source_created_at')->nullable();
                $table->timestamp('source_modified_at')->nullable();
                $table->timestamp('received_at')->useCurrent();
                $table->char('payload_sha256', 64);
                $table->longText('payload');
                $table->unsignedInteger('payload_bytes');
                $table->json('header_evidence')->nullable();
                $table->unsignedInteger('signature_key_version');
                $table->string('status', 32)->default('received');
                $table->unsignedInteger('attempt_count')->default(0);
                $table->timestamp('last_attempted_at')->nullable();
                $table->timestamp('processing_heartbeat_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->string('failure_code', 64)->nullable();
                $table->string('failure_message', 255)->nullable();
                $table->unsignedBigInteger('superseded_by_id')->nullable();
                $table->unsignedInteger('conflict_count')->default(0);
                $table->char('last_conflicting_payload_sha256', 64)->nullable();
                $table->timestamp('last_conflict_at')->nullable();
                $table->timestamps();

                $table->unique(['woocommerce_setting_id', 'delivery_id'], 'woo_receipt_connection_delivery_unique');
                $table->index(['status', 'last_attempted_at']);
                $table->index(['woocommerce_setting_id', 'topic', 'external_entity_id'], 'woo_receipt_entity_ordering_idx');
                $table->index('received_at');
                $table->index('superseded_by_id');
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Webhook receipts are retained audit evidence; destructive rollback requires explicit review.');
    }
};
