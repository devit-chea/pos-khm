<?php

namespace Modules\Ecommerce\Tests\Unit;

use App\Services\ModuleRegistry;
use App\Traits\TenantInfo;
use Database\Seeders\Tenant\TenantDatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CommercePermissionConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_canonical_commerce_permission_sets_are_complete(): void
    {
        $this->assertSame(
            ['module.ecommerce.access'],
            ModuleRegistry::permissionNames('ecommerce')
        );

        $this->assertSame(
            ['module.woocommerce.access', 'woocommerce.sync', 'woocommerce.manage'],
            ModuleRegistry::permissionNames('woocommerce')
        );
    }

    public function test_permission_data_exposes_the_registry_labels_and_granular_permissions(): void
    {
        $data = include resource_path('views/backend/role/permission_data.php');

        foreach (['ecommerce', 'woocommerce'] as $slug) {
            $definition = ModuleRegistry::get($slug);
            $this->assertSame($definition['label'], $data['commerce_module_access'][$slug]);
        }

        $this->assertSame(
            ModuleRegistry::get('woocommerce')['permissions'],
            $data['woocommerce_actions']
        );
    }

    public function test_tenant_features_and_canonical_seeder_use_registry_permissions(): void
    {
        $tenantInfo = new class {
            use TenantInfo;
        };
        $features = $tenantInfo->addonFeatures();
        $seededNames = (new TenantDatabaseSeeder())->seedCanonicalPermissions()->pluck('name')->all();

        foreach (['ecommerce', 'woocommerce'] as $slug) {
            $this->assertSame(
                ModuleRegistry::permissionNames($slug),
                explode(',', $features[$slug]['permission_names'])
            );

            foreach (ModuleRegistry::permissionNames($slug) as $permission) {
                $this->assertContains($permission, $seededNames);
            }
        }
    }
}
