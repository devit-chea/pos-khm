<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Modules\Ecommerce\Http\Controllers\CartController;
use Modules\Ecommerce\Http\Controllers\FrontController;
use Modules\Ecommerce\Services\StorefrontProductService;
use Modules\Ecommerce\Services\StorefrontPricingService;
use Modules\Ecommerce\Services\StorefrontCartService;
use Modules\Ecommerce\Services\DemoMediaResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class StorefrontCatalogFoundationTest extends TestCase
{
    use DatabaseTransactions;

    private StorefrontProductService $products;
    private StorefrontPricingService $pricing;
    private StorefrontCartService $cart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = app(StorefrontProductService::class);
        $this->pricing = app(StorefrontPricingService::class);
        $this->cart = app(StorefrontCartService::class);
    }

    public function test_shared_eligibility_excludes_inactive_and_offline_products(): void
    {
        $product = Product::query()->firstOrFail();

        $product->update(['is_active' => 0, 'is_online' => 1]);
        $this->assertNull($this->products->find($product->id));

        $product->update(['is_active' => 1, 'is_online' => 0]);
        $this->assertNull($this->products->find($product->id));

        $product->update(['is_active' => 1, 'is_online' => 1]);
        $this->assertSame($product->id, $this->products->find($product->id)->id);
    }

    public function test_live_search_is_bounded_and_excludes_hidden_products(): void
    {
        $visible = Product::query()->firstOrFail();
        $visible->update(['is_active' => 1, 'is_online' => 1]);
        $hidden = Product::query()->where('id', '!=', $visible->id)->firstOrFail();
        $term = 'phase3a1-' . uniqid();
        $visible->update(['name' => $term . '-visible', 'is_active' => 1, 'is_online' => 1]);
        $hidden->update(['name' => $term . '-hidden', 'is_active' => 0, 'is_online' => 1]);

        $response = app(FrontController::class)->search(
            $term,
            $this->products,
            $this->pricing,
            app(DemoMediaResolver::class)
        );
        $ids = collect($response->getData(true))->pluck('id');

        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
        $this->assertLessThanOrEqual(StorefrontProductService::LIVE_SEARCH_LIMIT, $ids->count());
    }

    /**
     * @dataProvider invalidQuantityProvider
     */
    public function test_cart_rejects_invalid_quantities($quantity): void
    {
        $product = $this->genericVisibleProduct();
        $response = app(CartController::class)->addToCart(
            Request::create('/add-to-cart', 'POST', ['product_id' => $product->id, 'qty' => $quantity]),
            $this->cart
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public static function invalidQuantityProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'malformed' => ['not-a-number'],
            'unreasonable' => [StorefrontProductService::MAX_CART_QUANTITY + 1],
        ];
    }

    public function test_cart_rejects_invalid_hidden_and_offline_product_identity(): void
    {
        $invalid = app(CartController::class)->addToCart(
            Request::create('/add-to-cart', 'POST', ['product_id' => 999999999, 'qty' => 1]),
            $this->cart
        );
        $this->assertSame(422, $invalid->getStatusCode());

        foreach (['is_active', 'is_online'] as $field) {
            $product = Product::query()->firstOrFail();
            $product->update(['is_active' => 1, 'is_online' => 1, $field => 0]);
            $response = app(CartController::class)->addToCart(
                Request::create('/add-to-cart', 'POST', ['product_id' => $product->id, 'qty' => 1]),
                $this->cart
            );
            $this->assertSame(422, $response->getStatusCode());
        }
    }

    public function test_cart_rejects_quantity_beyond_enforced_stock(): void
    {
        $product = $this->genericVisibleProduct();
        $product->update(['in_stock' => 0, 'qty' => 2]);

        $response = app(CartController::class)->addToCart(
            Request::create('/add-to-cart', 'POST', ['product_id' => $product->id, 'qty' => 3]),
            $this->cart
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_unsupported_category_filter_controls_are_not_rendered(): void
    {
        $source = file_get_contents(module_path('Ecommerce', 'Resources/views/frontend/products.blade.php'));

        $this->assertStringNotContainsString('variant-section', $source);
        $this->assertStringNotContainsString('data-v-option', $source);
    }

    public function test_invalid_brand_returns_404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        app(FrontController::class)->brandProducts(
            'missing-brand-' . uniqid(),
            Request::create('/brand/missing', 'GET'),
            $this->products,
            $this->pricing
        );
    }

    private function genericVisibleProduct(): Product
    {
        $product = Product::query()
            ->where(function ($query) {
                $query->whereNull('is_variant')->orWhere('is_variant', 0);
            })
            ->where(function ($query) {
                $query->whereNull('is_imei')->orWhere('is_imei', 0);
            })
            ->firstOrFail();
        $product->update(['is_active' => 1, 'is_online' => 1, 'in_stock' => 1]);

        return $product->fresh();
    }
}
