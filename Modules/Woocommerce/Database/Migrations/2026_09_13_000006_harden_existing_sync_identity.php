<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales') || !Schema::hasColumn('sales', 'woocommerce_order_id')) return;
        $duplicate = DB::table('sales')->whereNotNull('woocommerce_order_id')->select('woocommerce_order_id')
            ->groupBy('woocommerce_order_id')->havingRaw('COUNT(*) > 1')->first();
        if ($duplicate) throw new RuntimeException('Duplicate WooCommerce order mappings require explicit review before upgrade.');
        $indexes = collect(Schema::getIndexes('sales'));
        if (!$indexes->contains(fn (array $index) => $index['unique'] && $index['columns'] === ['woocommerce_order_id'])) {
            Schema::table('sales', fn (Blueprint $table) => $table->unique('woocommerce_order_id', 'sales_woocommerce_order_unique'));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('WooCommerce order idempotency constraints cannot be weakened automatically.');
    }
};
