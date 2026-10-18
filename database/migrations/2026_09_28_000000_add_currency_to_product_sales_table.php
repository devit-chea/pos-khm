<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-line currency chosen in POS. Amounts on the line stay in the sale's
     * currency; these record which currency the line was priced in and at what rate.
     */
    public function up(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->unsignedInteger('currency_id')->nullable()->after('total');
            $table->double('exchange_rate')->nullable()->after('currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->dropColumn(['currency_id', 'exchange_rate']);
        });
    }
};
