<?php

namespace Modules\Woocommerce\Services\Legacy;

use App\Models\Product;
use App\Models\Unit;
use Modules\Woocommerce\Exceptions\WooCommerceError;

final class SaleProUnitHierarchy
{
    public function toBaseQuantity(Product $product, int $unitId, int|float|string $quantity): float
    {
        $unit = Unit::query()->whereKey($unitId)->where('is_active', 1)->first();
        if (!$unit || ($unit->id !== (int) $product->unit_id && (int) $unit->base_unit !== (int) $product->unit_id)) {
            throw new WooCommerceError("Unit does not belong to this product's hierarchy.");
        }
        if ($unit->id === (int) $product->unit_id) return (float) $quantity;
        if (!in_array($unit->operator, ['*','/'], true) || (float) $unit->operation_value <= 0) throw new WooCommerceError('The SalePro sale unit conversion is invalid.');
        return $unit->operator === '*' ? (float) $quantity * (float) $unit->operation_value : (float) $quantity / (float) $unit->operation_value;
    }
}
