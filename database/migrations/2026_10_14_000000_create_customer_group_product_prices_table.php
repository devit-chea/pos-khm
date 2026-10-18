<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fixed sale price of a product for a customer group. Products without a row
     * here fall back to the group's global percentage.
     */
    public function up(): void
    {
        Schema::create('customer_group_product_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_group_id');
            $table->unsignedInteger('product_id');
            $table->double('price');
            $table->timestamps();

            // Explicit name: the generated one is over MySQL's 64-character limit
            $table->unique(['customer_group_id', 'product_id'], 'cg_product_prices_group_product_unique');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_group_product_prices');
    }
};
