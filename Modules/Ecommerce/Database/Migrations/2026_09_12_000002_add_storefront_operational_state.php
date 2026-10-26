<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ecommerce_settings', 'storefront_enabled')) {
            Schema::table('ecommerce_settings', function (Blueprint $table): void {
                $table->boolean('storefront_enabled')->default(true);
                $table->boolean('legacy_activation_review_required')->default(false);
            });

            // The field did not exist before this migration, so every current row
            // is necessarily unconfigured and receives the enabled default.
            DB::table('ecommerce_settings')->update(['storefront_enabled' => true]);
        }

        $legacy = (string) DB::table('general_settings')->value('modules');
        if (in_array('ecommerce', array_map('trim', explode(',', strtolower($legacy))), true)) {
            DB::table('ecommerce_settings')->update(['legacy_activation_review_required' => true]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Storefront operational state requires an explicit downgrade review.');
    }
};
