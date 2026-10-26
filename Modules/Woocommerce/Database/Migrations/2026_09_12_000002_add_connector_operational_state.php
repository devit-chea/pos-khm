<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('woocommerce_settings', 'sync_enabled')) {
            Schema::table('woocommerce_settings', function (Blueprint $table): void {
                $table->boolean('sync_enabled')->default(false);
                $table->boolean('legacy_activation_review_required')->default(false);
            });
        }

        $legacy = (string) DB::table('general_settings')->value('modules');
        if (in_array('woocommerce', array_map('trim', explode(',', strtolower($legacy))), true)) {
            DB::table('woocommerce_settings')->update(['legacy_activation_review_required' => true]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Connector operational state requires an explicit downgrade review.');
    }
};
