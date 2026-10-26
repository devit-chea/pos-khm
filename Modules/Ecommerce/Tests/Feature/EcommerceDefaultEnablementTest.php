<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Database\Seeders\EcommerceDatabaseSeeder;
use App\Services\ModuleAccessService;
use Tests\TestCase;

class EcommerceDefaultEnablementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fresh_ecommerce_settings_enable_the_public_storefront(): void
    {
        DB::table('ecommerce_settings')->delete();

        (new EcommerceDatabaseSeeder())->run();

        $this->assertTrue((bool) DB::table('ecommerce_settings')->latest('id')->value('storefront_enabled'));
        $this->assertTrue((bool) config('ecommerce.storefront_enabled_default'));
    }

    public function test_explicit_storefront_off_survives_subsequent_seeding(): void
    {
        DB::table('ecommerce_settings')->updateOrInsert(
            ['id' => 1],
            [
                'site_title' => 'Explicitly disabled storefront',
                'storefront_enabled' => false,
                'legacy_activation_review_required' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        (new EcommerceDatabaseSeeder())->run();

        $this->assertFalse((bool) DB::table('ecommerce_settings')->where('id', 1)->value('storefront_enabled'));
    }

    public function test_upgrade_backfill_enables_only_previously_unconfigured_storefronts(): void
    {
        $base = (array) DB::table('ecommerce_settings')->first();
        $this->assertNotEmpty($base);
        $unconfiguredId = (int) $base['id'];

        DB::table('ecommerce_settings')->where('id', $unconfiguredId)->update([
            'site_title' => 'Unconfigured storefront',
            'storefront_enabled' => false,
            'legacy_activation_review_required' => true,
        ]);
        unset($base['id']);
        $base['site_title'] = 'Explicitly disabled storefront';
        $base['storefront_enabled'] = false;
        $base['legacy_activation_review_required'] = false;
        $explicitId = DB::table('ecommerce_settings')->insertGetId($base);

        $migration = require base_path('Modules/Ecommerce/Database/Migrations/2026_09_30_000000_enable_unconfigured_storefronts.php');
        $migration->up();

        $this->assertTrue((bool) DB::table('ecommerce_settings')->where('id', $unconfiguredId)->value('storefront_enabled'));
        $this->assertFalse((bool) DB::table('ecommerce_settings')->where('id', $unconfiguredId)->value('legacy_activation_review_required'));
        $this->assertFalse((bool) DB::table('ecommerce_settings')->where('id', $explicitId)->value('storefront_enabled'));
    }

    public function test_existing_saas_tenant_reads_its_enabled_storefront_configuration(): void
    {
        $originalLandlord = config('database.connections.saleprosaas_landlord');

        try {
            config(['database.connections.saleprosaas_landlord' => ['driver' => 'mysql']]);
            $settings = DB::table('general_settings')->latest('id')->first();
            $modules = array_values(array_unique(array_filter(array_merge(
                explode(',', (string) ($settings->modules ?? '')),
                ['ecommerce']
            ))));
            $disabled = array_values(array_diff(
                array_filter(explode(',', (string) ($settings->disabled_modules ?? ''))),
                ['ecommerce']
            ));
            DB::table('general_settings')->where('id', $settings->id)->update([
                'modules' => implode(',', $modules),
                'disabled_modules' => implode(',', $disabled),
            ]);
            DB::table('ecommerce_settings')->update(['storefront_enabled' => true]);

            $this->assertTrue(app(ModuleAccessService::class)->isOperational('ecommerce'));
        } finally {
            config(['database.connections.saleprosaas_landlord' => $originalLandlord]);
        }
    }
}
