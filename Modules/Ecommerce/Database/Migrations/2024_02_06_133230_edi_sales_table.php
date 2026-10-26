<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EdiSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Ensure no NULL values exist before altering the column
        \Illuminate\Support\Facades\DB::table('sales')->whereNull('sale_type')->update(['sale_type' => 'pos']);

        Schema::table('sales', function (Blueprint $table) {
            $table->string('sale_type')->default('pos')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
