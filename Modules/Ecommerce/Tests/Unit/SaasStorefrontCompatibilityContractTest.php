<?php

namespace Modules\Ecommerce\Tests\Unit;

use Modules\Ecommerce\Services\StorefrontCacheKey;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SaasStorefrontCompatibilityContractTest extends TestCase
{
    public function test_storefront_cache_keys_are_isolated_by_tenant_host(): void
    {
        $first = StorefrontCacheKey::for('setting', 'tenant-one.example.test');
        $second = StorefrontCacheKey::for('setting', 'tenant-two.example.test');

        $this->assertSame('ecommerce.tenant-one.example.test.setting', $first);
        $this->assertSame('ecommerce.tenant-two.example.test.setting', $second);
        $this->assertNotSame($first, $second);
    }

    #[DataProvider('tenantAssetViewProvider')]
    public function test_storefront_local_assets_use_the_saas_relative_prefix(string $path, string $asset): void
    {
        $view = file_get_contents(__DIR__ . '/../../' . $path);

        $this->assertStringContainsString("!config('database.connections.saleprosaas_landlord') ? '' : '../../'", $view);
        $this->assertStringContainsString("asset(\$asset_prefix . '{$asset}')", $view);
    }

    public static function tenantAssetViewProvider(): array
    {
        return [
            'legacy home swiper' => ['Resources/views/frontend/home.blade.php', 'modules/ecommerce/js/swiper.min.js'],
            'product swiper' => ['Resources/views/frontend/product-details.blade.php', 'modules/ecommerce/js/swiper.min.js'],
            'electronics widgets' => ['Resources/views/frontend/themes/electronics/home.blade.php', 'modules/ecommerce/js/homepage-widgets.js'],
            'grocery widgets' => ['Resources/views/frontend/themes/grocery/home.blade.php', 'modules/ecommerce/js/homepage-widgets.js'],
            'fashion widgets' => ['Resources/views/frontend/themes/fashion/home.blade.php', 'modules/ecommerce/js/homepage-widgets.js'],
            'minimal theme css' => ['Resources/views/frontend/themes/minimal/layout/main.blade.php', 'frontend/css/tailwind.css'],
            'checkout product image' => ['Resources/views/frontend/checkout.blade.php', 'images/product/small'],
        ];
    }

    public function test_imei_integrity_boundary_is_not_removed_to_accommodate_tenant_data(): void
    {
        $service = file_get_contents(__DIR__ . '/../../Services/StorefrontProductService.php');
        $view = file_get_contents(__DIR__ . '/../../Resources/views/frontend/product-details.blade.php');

        $this->assertStringContainsString('&& !(bool) $product->is_imei', $service);
        $this->assertStringContainsString("action=\"{{ route('addToCart') }}\"", $view);
        $this->assertStringContainsString('name="product_id" value="{{ $product->id }}"', $view);
        $this->assertStringContainsString("__('db.Ecommerce product unavailable')", $view);
        $this->assertStringContainsString("{{ \$genericCartSupported ? 'true' : 'false' }}", $view);
    }
}
