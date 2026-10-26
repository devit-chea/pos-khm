<?php

namespace Modules\Woocommerce\Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Woocommerce\Exceptions\WooCommerceError;
use Modules\Woocommerce\Services\Legacy\SaleProUnitHierarchy;
use Tests\TestCase;

class SaleProUnitHierarchyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_same_name_units_are_resolved_by_id_and_hierarchy(): void
    {
        $product = Product::query()->firstOrFail();
        $base = Unit::query()->create($this->unit('Base '.bin2hex(random_bytes(3))));
        $validPiece = Unit::query()->create($this->unit('Piece', $base->id, '*', 0.5));
        $otherBase = Unit::query()->create($this->unit('Other '.bin2hex(random_bytes(3))));
        $ambiguousPiece = Unit::query()->create($this->unit('Piece', $otherBase->id, '*', 3));
        $product->unit_id = $base->id;
        $product->save();

        $service = app(SaleProUnitHierarchy::class);
        self::assertSame(2.0, $service->toBaseQuantity($product, $validPiece->id, 4));

        $this->expectException(WooCommerceError::class);
        $service->toBaseQuantity($product, $ambiguousPiece->id, 4);
    }

    public function test_stale_unit_id_fails_without_name_fallback(): void
    {
        $product = Product::query()->firstOrFail();
        $base = Unit::query()->create($this->unit('Base '.bin2hex(random_bytes(3))));
        $product->unit_id = $base->id;
        $product->save();

        $this->expectException(WooCommerceError::class);
        app(SaleProUnitHierarchy::class)->toBaseQuantity($product, 2147483647, 1);
    }

    public function test_invalid_conversion_and_unrelated_hierarchy_fail_safely(): void
    {
        $product = Product::query()->firstOrFail();
        $base = Unit::query()->create($this->unit('Base '.bin2hex(random_bytes(3))));
        $invalid = Unit::query()->create($this->unit('Piece', $base->id, '/', 0));
        $product->unit_id = $base->id;
        $product->save();

        try {
            app(SaleProUnitHierarchy::class)->toBaseQuantity($product, $invalid->id, 1);
            self::fail('Invalid conversion was accepted.');
        } catch (WooCommerceError $error) {
            self::assertStringNotContainsString($product->name, $error->getMessage());
            self::assertStringNotContainsString('Piece', $error->getMessage());
        }

        $unrelated = Unit::query()->create($this->unit('Piece'));
        $this->expectException(WooCommerceError::class);
        app(SaleProUnitHierarchy::class)->toBaseQuantity($product, $unrelated->id, 1);
    }

    private function unit(string $name, ?int $base = null, ?string $operator = null, float $value = 1): array
    {
        return ['unit_code' => 'T'.bin2hex(random_bytes(4)), 'unit_name' => $name, 'base_unit' => $base, 'operator' => $operator, 'operation_value' => $value, 'is_active' => true];
    }
}
