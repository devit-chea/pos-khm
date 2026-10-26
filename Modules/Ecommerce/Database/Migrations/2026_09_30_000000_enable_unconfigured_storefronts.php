<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecommerce_settings')
            || !Schema::hasColumn('ecommerce_settings', 'storefront_enabled')) {
            return;
        }

        Schema::table('ecommerce_settings', function (Blueprint $table): void {
            $table->boolean('storefront_enabled')->default(true)->change();
        });

        // The original operational-state migration marked legacy active stores
        // for a one-time decision. A remaining marker therefore means the new
        // switch has never been explicitly saved. The settings controller clears
        // it on every save, so an explicit OFF is never overwritten here.
        if (Schema::hasColumn('ecommerce_settings', 'legacy_activation_review_required')) {
            DB::table('ecommerce_settings')
                ->where('legacy_activation_review_required', true)
                ->update([
                    'storefront_enabled' => true,
                    'legacy_activation_review_required' => false,
                ]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('The storefront default requires an explicit downgrade review.');
    }
};
