<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grand total of each sale in the other currencies shown at checkout (e.g. KHR),
     * snapshotted with the rate used at the time of sale. One row per currency,
     * so adding another currency later needs no schema change.
     */
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            // Show this currency's grand total in POS and store it with each sale
            $table->boolean('show_in_pos')->default(false)->after('exchange_rate');
            // Round totals in this currency to a multiple of this step (e.g. 100 for KHR); null = app decimals
            $table->decimal('rounding', 12, 4)->nullable()->after('show_in_pos');
        });

        Schema::create('sale_currency_totals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sale_id');
            $table->unsignedBigInteger('currency_id');
            // 1 unit of the sale currency = exchange_rate units of this currency, at time of sale
            $table->double('exchange_rate');
            // Grand total in this currency, after rounding
            $table->double('grand_total');
            $table->timestamps();

            $table->unique(['sale_id', 'currency_id']);
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies');
        });

        // Keep current POS behaviour: every active non-base currency was already shown
        $base_id = DB::table('general_settings')->latest()->value('currency');
        DB::table('currencies')->where('is_active', true)->where('id', '!=', $base_id)->update(['show_in_pos' => true]);
        // Riel: round to the nearest 100
        DB::table('currencies')->where('code', 'KHR')->update(['rounding' => 100]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_currency_totals');
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['show_in_pos', 'rounding']);
        });
    }
};
