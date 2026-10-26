<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('woocommerce_webhook_security_events')) {
            Schema::create('woocommerce_webhook_security_events', function (Blueprint $table): void { $table->bigIncrements('id'); $table->unsignedBigInteger('woocommerce_setting_id'); $table->unsignedBigInteger('actor_id')->nullable(); $table->string('event_type', 64); $table->json('metadata')->nullable(); $table->timestamp('created_at'); });
        }
        $indexes = array_column(Schema::getIndexes('woocommerce_webhook_security_events'), 'name');
        if (!in_array('woo_webhook_security_setting_created_idx', $indexes, true)) {
            Schema::table('woocommerce_webhook_security_events', fn (Blueprint $table) => $table->index(['woocommerce_setting_id', 'created_at'], 'woo_webhook_security_setting_created_idx'));
        }
    }
    public function down(): void { throw new RuntimeException('Webhook security audit evidence cannot be destroyed automatically.'); }
};
