<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services') || DB::table('services')->exists()) return;

        $now = now();
        DB::table('services')->insert([
            ['id' => 1, 'name' => 'Dine In', 'description' => null, 'is_active' => 1, 'is_deleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Take Away', 'description' => null, 'is_active' => 1, 'is_deleted' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Delivery', 'description' => null, 'is_active' => 1, 'is_deleted' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        // Service rows may become business data. Intentionally non-destructive.
    }
};
