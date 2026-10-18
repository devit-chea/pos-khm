<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Google Maps link to the customer's / supplier's location.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('map_link')->nullable()->after('address');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->text('map_link')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('map_link');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('map_link');
        });
    }
};
