<?php

namespace Modules\Ecommerce\Tests\Unit;

use App\Http\Controllers\RoleController;
use App\Models\Roles;
use App\Models\User;
use App\Services\ModuleAccessService;
use App\Services\ModuleRegistry;
use App\Traits\TenantInfo;
use Database\Seeders\Tenant\TenantDatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ModuleControlConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            1 => ['name' => 'Admin', 'description' => 'Reserved administrator role'],
            2 => ['name' => 'Owner', 'description' => 'Reserved owner role'],
            5 => ['name' => 'Customer', 'description' => 'Customer portal role'],
        ] as $id => $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $id],
                $role + [
                    'guard_name' => 'web',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        (new TenantDatabaseSeeder())->seedCanonicalPermissions();
    }

    public function test_every_registered_module_has_a_complete_unique_permission_contract(): void
    {
        $allPermissionNames = [];

        foreach (ModuleRegistry::all() as $slug => $definition) {
            $this->assertSame($definition, ModuleRegistry::get(strtoupper($slug)));
            $this->assertSame("module.{$slug}.access", $definition['access_permission']);
            $this->assertNotEmpty($definition['module_name']);
            $this->assertNotEmpty($definition['label']);
            $this->assertContains($definition['legacy_access'], ['permissions', 'all_roles', 'admins']);
            $this->assertSame(
                array_values(array_unique(ModuleRegistry::permissionNames($slug))),
                ModuleRegistry::permissionNames($slug)
            );
            $allPermissionNames = array_merge($allPermissionNames, ModuleRegistry::permissionNames($slug));
        }

        $this->assertSame(array_values(array_unique($allPermissionNames)), $allPermissionNames);
        $this->assertContains('woocommerce.sync', ModuleRegistry::permissionNames('woocommerce', false));
        $this->assertContains('woocommerce.manage', ModuleRegistry::permissionNames('woocommerce', false));
        $this->assertContains('repair-dashboard', ModuleRegistry::permissionNames('repair', false));
        $this->assertContains('repair-service-index', ModuleRegistry::permissionNames('repair', false));
        $this->assertContains('repair-device-type', ModuleRegistry::permissionNames('repair', false));
    }

    public function test_tenant_features_role_ui_data_and_seeder_cover_every_installed_registered_module(): void
    {
        $access = app(ModuleAccessService::class);
        $tenantInfo = new class {
            use TenantInfo;
        };
        $features = $tenantInfo->addonFeatures();
        $permissionData = include resource_path('views/backend/role/permission_data.php');
        $seededNames = (new TenantDatabaseSeeder())->seedCanonicalPermissions()->pluck('name')->all();

        $uiPermissionGroups = collect($permissionData)
            ->filter(fn ($value) => is_array($value))
            ->flatMap(fn (array $value) => array_keys($value))
            ->all();

        foreach (ModuleRegistry::all() as $slug => $definition) {
            foreach (ModuleRegistry::permissionNames($slug) as $permission) {
                $this->assertContains($permission, $seededNames, "Seeder omitted {$permission}.");
            }

            if (!$access->isInstalled($slug)) {
                $this->assertArrayNotHasKey($slug, $features);
                continue;
            }

            $this->assertArrayHasKey($slug, $features);
            $this->assertSame(
                ModuleRegistry::permissionNames($slug),
                explode(',', $features[$slug]['permission_names'])
            );
            foreach (array_keys($definition['permissions']) as $permission) {
                $this->assertContains($permission, $uiPermissionGroups, "Role UI omitted {$permission}.");
            }
        }
    }

    public function test_business_switch_preserves_role_permissions_and_reenabling_restores_access(): void
    {
        $access = app(ModuleAccessService::class);
        if (!DB::table('general_settings')->exists()) {
            DB::table('general_settings')->insert([
                'site_title' => 'Module control test',
                'currency' => 1,
                'currency_position' => 'prefix',
                'staff_access' => 'own',
                'without_stock' => 'no',
                'date_format' => 'd-m-Y',
                'theme' => 'default.css',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $available = $access->availableModules();
        $slug = collect($available)->first(fn (string $candidate) => ModuleRegistry::permissionNames($candidate, false) !== []);
        $this->assertNotNull($slug, 'At least one available granular-permission module is required.');

        $role = $this->customRole();
        $permissions = ModuleRegistry::permissionNames($slug);
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'role_id' => $role->id,
                'permission_id' => $permissionId,
            ]);
        }
        $before = $this->rolePermissionIds($role->id);
        $user = (new User())->forceFill([
            'role_id' => $role->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $this->assertTrue($access->userCanAccess($user, $slug));
        $access->setBusinessEnabledModules(array_values(array_diff($available, [$slug])));
        $this->assertFalse($access->businessEnabled($slug));
        $this->assertFalse($access->userCanAccess($user, $slug));
        $this->assertSame($before, $this->rolePermissionIds($role->id));

        config(['app.user_verified' => true]);
        $this->actingAs($this->createIsolatedAdminUser());
        app(RoleController::class)->setPermission(Request::create('/role/set-permission', 'POST', [
            'role_id' => $role->id,
        ]));
        $this->assertSame($before, $this->rolePermissionIds($role->id));

        $access->setBusinessEnabledModules($available);
        $this->assertTrue($access->businessEnabled($slug));
        $this->assertTrue($access->userCanAccess($user, $slug));
        $this->assertSame($before, $this->rolePermissionIds($role->id));
    }

    public function test_unconfigured_business_enables_every_available_module_once(): void
    {
        $access = app(ModuleAccessService::class);
        $settingsId = $this->ensureGeneralSettingsRow();
        DB::table('general_settings')->where('id', $settingsId)->update([
            'disabled_modules' => null,
        ]);

        $this->assertTrue($access->initializeBusinessModulesIfUnconfigured());
        $this->assertSame('', (string) DB::table('general_settings')->latest('id')->value('disabled_modules'));

        foreach ($access->availableModules() as $slug) {
            $this->assertTrue($access->businessEnabled($slug), "Fresh tenant did not enable {$slug}.");
        }

        $missingSlug = collect(array_keys(ModuleRegistry::all()))
            ->first(fn (string $slug) => !is_dir(base_path('Modules/'.ModuleRegistry::get($slug)['module_name'])));
        $this->assertNotNull($missingSlug, 'The registry must retain coverage for at least one unavailable package.');
        $this->assertFalse($access->isInstalled($missingSlug));
        $this->assertNotContains($missingSlug, $access->availableModules());
        $this->assertFalse($access->businessEnabled($missingSlug));
    }

    public function test_module_backfill_does_not_overwrite_an_explicit_off_choice(): void
    {
        $access = app(ModuleAccessService::class);
        $slug = collect($access->availableModules())->first();
        $this->assertNotNull($slug, 'At least one available module is required.');

        $settingsId = $this->ensureGeneralSettingsRow();
        DB::table('general_settings')->where('id', $settingsId)->update([
            'disabled_modules' => $slug,
        ]);

        $this->assertFalse($access->initializeBusinessModulesIfUnconfigured());
        $this->assertSame($slug, DB::table('general_settings')->latest('id')->value('disabled_modules'));
        $this->assertFalse($access->businessEnabled($slug));
    }

    public function test_admin_and_owner_can_access_every_enabled_installed_module(): void
    {
        $access = app(ModuleAccessService::class);
        $access->setBusinessEnabledModules($access->availableModules());

        foreach ([1, 2] as $roleId) {
            $user = (new User())->forceFill([
                'role_id' => $roleId,
                'is_active' => true,
                'is_deleted' => false,
            ]);
            foreach ($access->availableModules() as $slug) {
                $this->assertTrue($access->userCanAccess($user, $slug), "Role {$roleId} cannot access {$slug}.");
            }
        }
    }

    public function test_reserved_and_custom_role_semantics_apply_to_every_registered_module(): void
    {
        $access = app(ModuleAccessService::class);
        $customRole = $this->customRole();
        $grantedSlug = array_key_first(ModuleRegistry::all());
        $accessPermissionId = DB::table('permissions')
            ->where('name', ModuleRegistry::get($grantedSlug)['access_permission'])
            ->value('id');
        DB::table('role_has_permissions')->insert([
            'role_id' => $customRole->id,
            'permission_id' => $accessPermissionId,
        ]);

        foreach (array_keys(ModuleRegistry::all()) as $slug) {
            $this->assertTrue($access->roleCanAccess(1, $slug), "Admin lost {$slug} access.");
            $this->assertTrue($access->roleCanAccess(2, $slug), "Owner lost {$slug} access.");
            $this->assertFalse($access->roleCanAccess(5, $slug), "Customer gained {$slug} back-office access.");
            $this->assertSame($slug === $grantedSlug, $access->roleCanAccess($customRole, $slug));
        }
    }

    public function test_every_available_module_exposes_role_gated_routes_and_protects_authenticated_routes(): void
    {
        $access = app(ModuleAccessService::class);

        foreach ($access->availableModules() as $slug) {
            $moduleName = ModuleRegistry::get($slug)['module_name'];
            $routes = collect(Route::getRoutes()->getRoutes())->filter(function ($route) use ($moduleName) {
                return str_starts_with((string) $route->getActionName(), "Modules\\{$moduleName}\\");
            });

            $this->assertNotEmpty($routes, "No routes were registered for available module {$slug}.");
            $this->assertTrue($routes->contains(function ($route) use ($slug) {
                return in_array("module.access:{$slug}", $route->gatherMiddleware(), true);
            }), "No role-gated route was registered for {$slug}.");

            foreach ($routes->filter(fn ($route) => in_array('auth', $route->gatherMiddleware(), true)) as $route) {
                $middleware = $route->gatherMiddleware();
                $this->assertTrue(
                    in_array("module.access:{$slug}", $middleware, true),
                    "Authenticated route {$route->uri()} bypasses the {$slug} role gate."
                );
            }
        }
    }

    public function test_product_create_and_edit_views_compile_with_woocommerce_access_gate(): void
    {
        foreach (['backend.product.create', 'backend.product.edit'] as $view) {
            $compiled = Blade::compileString((string) file_get_contents(view($view)->getPath()));
            $this->assertMatchesRegularExpression(
                '/ModuleAccessService::class\)->userCanAccess\(auth\(\)->user\(\),\s*[\'\"]woocommerce[\'\"]\)/',
                $compiled,
                "{$view} does not gate WooCommerce fields through ModuleAccessService."
            );
        }
    }

    private function rolePermissionIds(int $roleId): array
    {
        return DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->orderBy('permission_id')
            ->pluck('permission_id')
            ->all();
    }

    private function ensureGeneralSettingsRow(): int
    {
        $id = DB::table('general_settings')->max('id');
        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('general_settings')->insertGetId([
            'site_title' => 'Module control test',
            'currency' => 1,
            'currency_position' => 'prefix',
            'staff_access' => 'own',
            'without_stock' => 'no',
            'date_format' => 'd-m-Y',
            'theme' => 'default.css',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function customRole(): Roles
    {
        return Roles::create([
            'name' => 'Module control '.bin2hex(random_bytes(4)),
            'description' => 'Test-only role',
            'guard_name' => 'web',
            'is_active' => true,
        ]);
    }
}
