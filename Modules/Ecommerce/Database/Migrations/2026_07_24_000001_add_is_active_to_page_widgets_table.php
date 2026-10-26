<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_widgets', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index()->after('order');
        });
    }

    public function down(): void
    {
        Schema::table('page_widgets', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
