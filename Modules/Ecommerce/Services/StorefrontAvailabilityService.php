<?php

namespace Modules\Ecommerce\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StorefrontAvailabilityService
{
    public const REASON_OUT_OF_STOCK = 'out_of_stock';
    public const REASON_CONFIGURATION_REQUIRED = 'configuration_required';
    public const REASON_IMEI_UNSUPPORTED = 'imei_unsupported';
    public const REASON_PRODUCT_UNSUPPORTED = 'product_unsupported';

    private bool $warehouseResolved = false;
    private ?int $warehouseId = null;
    private array $warehouseQuantities = [];

    public function __construct(
        private StorefrontProductService $products,
        private StorefrontVariantService $variants
    ) {
    }

    public function decorate(Product $product): array
    {
        $availability = $this->forProduct($product);
        $product->setAttribute('storefront_availability', $availability);
        $product->setAttribute('storefront_available_qty', $availability['available_qty']);
        $product->setAttribute('storefront_is_available', $availability['available']);
        $product->setAttribute('storefront_unavailable_reason', $availability['reason']);

        return $availability;
    }

    public function decorateMany(iterable $products): void
    {
        $products = collect($products)->filter(fn ($product) => $product instanceof Product);
        $warehouseId = $this->configuredWarehouseId();
        if ($warehouseId && $products->isNotEmpty()) {
            $rows = DB::table('product_warehouse')
                ->where('warehouse_id', $warehouseId)
                ->whereIn('product_id', $products->pluck('id')->all())
                ->whereNull('product_batch_id')
                ->orderBy('id')
                ->get(['product_id', 'variant_id', 'qty']);
            foreach ($rows as $row) {
                $key = $this->warehouseKey((int) $row->product_id, $warehouseId, $row->variant_id);
                $this->warehouseQuantities[$key] ??= max(0, (float) $row->qty);
            }
        }

        foreach ($products as $product) {
            if ($product instanceof Product) {
                $this->decorate($product);
            }
        }
    }

    public function decorateVariants(Product $product, iterable $variants): void
    {
        foreach ($variants as $variant) {
            $availability = $this->forProduct($product, (int) $variant->id);
            $variant->storefront_availability = $availability;
            $variant->storefront_available_qty = $availability['available_qty'];
            $variant->storefront_is_available = $availability['available'];
        }
    }

    public function forProduct(Product $product, ?int $productVariantId = null): array
    {
        if ((bool) $product->is_imei) {
            return $this->unavailable(self::REASON_IMEI_UNSUPPORTED, false);
        }
        if (!$this->products->supportsGenericCart($product)) {
            return $this->unavailable(self::REASON_PRODUCT_UNSUPPORTED, false);
        }

        $warehouseId = $this->configuredWarehouseId();
        if (!$warehouseId) {
            return $this->unavailable(self::REASON_CONFIGURATION_REQUIRED, (bool) $product->is_variant);
        }

        if ($product->type === 'combo') {
            return $this->quantityResult($this->comboAvailableQuantity($product, $warehouseId), false);
        }

        if ((bool) $product->is_variant) {
            if ($productVariantId) {
                $variant = $this->variants->resolve((int) $product->id, $productVariantId);
                if (!$variant) {
                    return $this->unavailable(self::REASON_CONFIGURATION_REQUIRED, true);
                }

                if ($product->type === 'service' || (bool) $product->in_stock) {
                    return $this->available(null, true, true);
                }

                $warehouseQty = $this->warehouseQuantity(
                    (int) $product->id,
                    $warehouseId,
                    (int) $variant->variant_definition_id
                );
                $baseQuantity = min((float) $product->qty, (float) $variant->qty, $warehouseQty);

                return $this->quantityResult($this->saleQuantity($baseQuantity, (int) $product->sale_unit_id), true);
            }

            $rows = $this->variants->forProduct((int) $product->id);
            if ($rows->isEmpty()) {
                return $this->unavailable(self::REASON_CONFIGURATION_REQUIRED, true);
            }

            $available = $rows->contains(function ($variant) use ($product): bool {
                return $this->forProduct($product, (int) $variant->id)['available'];
            });

            return $available
                ? $this->available(null, true, (bool) $product->in_stock || $product->type === 'service')
                : $this->unavailable(self::REASON_OUT_OF_STOCK, true);
        }

        if ($product->type === 'service' || (bool) $product->in_stock) {
            return $this->available(null, false, true);
        }

        $baseQuantity = min(
            (float) $product->qty,
            $this->warehouseQuantity((int) $product->id, $warehouseId, null)
        );

        return $this->quantityResult($this->saleQuantity($baseQuantity, (int) $product->sale_unit_id), false);
    }

    private function configuredWarehouseId(): ?int
    {
        if ($this->warehouseResolved) {
            return $this->warehouseId;
        }

        $this->warehouseResolved = true;
        $warehouseId = filter_var(
            DB::table('ecommerce_settings')->latest('id')->value('warehouse_id'),
            FILTER_VALIDATE_INT
        );
        if ($warehouseId && DB::table('warehouses')->where('id', $warehouseId)->exists()) {
            $this->warehouseId = (int) $warehouseId;
        }

        return $this->warehouseId;
    }

    private function warehouseQuantity(int $productId, int $warehouseId, ?int $variantDefinitionId): float
    {
        $key = $this->warehouseKey($productId, $warehouseId, $variantDefinitionId);
        if (array_key_exists($key, $this->warehouseQuantities)) {
            return $this->warehouseQuantities[$key];
        }

        return $this->warehouseQuantities[$key] = max(0, (float) DB::table('product_warehouse')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantDefinitionId)
            ->whereNull('product_batch_id')
            ->value('qty'));
    }

    private function warehouseKey(int $productId, int $warehouseId, ?int $variantDefinitionId): string
    {
        return $warehouseId . ':' . $productId . ':' . ($variantDefinitionId ?? 'none');
    }

    private function comboAvailableQuantity(Product $combo, int $warehouseId): ?float
    {
        $productIds = array_values(array_filter(explode(',', (string) $combo->product_list), 'strlen'));
        $quantities = explode(',', (string) $combo->qty_list);
        $variantDefinitionIds = $combo->variant_list ? explode(',', (string) $combo->variant_list) : [];
        $unitIds = $combo->combo_unit_id ? explode(',', (string) $combo->combo_unit_id) : [];

        if (!$productIds || count($productIds) !== count($quantities)) {
            return 0.0;
        }

        $maximum = null;
        foreach ($productIds as $index => $productId) {
            $child = Product::find((int) $productId);
            $perCombo = (float) ($quantities[$index] ?? 0);
            if (!$child || $perCombo <= 0) {
                return 0.0;
            }
            if ($child->type === 'service' || (bool) $child->in_stock) {
                continue;
            }

            $requiredBase = $this->baseQuantity($perCombo, (int) ($unitIds[$index] ?? 0));
            if ($requiredBase <= 0) {
                return 0.0;
            }
            $variantDefinitionId = !empty($variantDefinitionIds[$index])
                ? (int) $variantDefinitionIds[$index]
                : null;
            $stock = min(
                (float) $child->qty,
                $this->warehouseQuantity((int) $child->id, $warehouseId, $variantDefinitionId)
            );
            if ($variantDefinitionId) {
                $variantQty = DB::table('product_variants')
                    ->where('product_id', $child->id)
                    ->where('variant_id', $variantDefinitionId)
                    ->value('qty');
                if ($variantQty === null) {
                    return 0.0;
                }
                $stock = min($stock, (float) $variantQty);
            }
            $maximum = min($maximum ?? INF, $stock / $requiredBase);
        }

        if ($maximum === null) {
            return null;
        }

        return $this->saleQuantity($maximum, (int) $combo->sale_unit_id);
    }

    private function baseQuantity(float $quantity, int $unitId): float
    {
        if (!$unitId) {
            return $quantity;
        }
        $unit = DB::table('units')->where('id', $unitId)->first(['operator', 'operation_value']);
        if (!$unit || (float) $unit->operation_value <= 0) {
            return 0.0;
        }

        return $unit->operator === '*'
            ? $quantity * (float) $unit->operation_value
            : ($unit->operator === '/' ? $quantity / (float) $unit->operation_value : $quantity);
    }

    private function saleQuantity(float $baseQuantity, int $unitId): float
    {
        if (!$unitId) {
            return max(0, $baseQuantity);
        }
        $unit = DB::table('units')->where('id', $unitId)->first(['operator', 'operation_value']);
        if (!$unit || (float) $unit->operation_value <= 0) {
            return 0.0;
        }

        return max(0, $unit->operator === '*'
            ? $baseQuantity / (float) $unit->operation_value
            : ($unit->operator === '/' ? $baseQuantity * (float) $unit->operation_value : $baseQuantity));
    }

    private function quantityResult(?float $quantity, bool $requiresVariant): array
    {
        if ($quantity === null) {
            return $this->available(null, $requiresVariant, true);
        }

        return $quantity > 0
            ? $this->available(round($quantity, 4), $requiresVariant, false)
            : $this->unavailable(self::REASON_OUT_OF_STOCK, $requiresVariant);
    }

    private function available(?float $quantity, bool $requiresVariant, bool $unlimited): array
    {
        return [
            'available' => true,
            'available_qty' => $quantity,
            'requires_variant' => $requiresVariant,
            'stock_unlimited' => $unlimited,
            'reason' => null,
        ];
    }

    private function unavailable(string $reason, bool $requiresVariant): array
    {
        return [
            'available' => false,
            'available_qty' => 0.0,
            'requires_variant' => $requiresVariant,
            'stock_unlimited' => false,
            'reason' => $reason,
        ];
    }
}
