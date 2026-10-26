<?php

namespace Modules\Ecommerce\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Product_Sale;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\Sale;
use App\Models\Unit;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontOrderService
{
    public function __construct(
        private StorefrontCheckoutService $checkout,
        private AccountingService $accounting
    ) {
    }

    public function create(array $quote, array $header): array
    {
        app(\App\Services\ZatcaIntegrationService::class)->assertStorefrontCheckoutAvailable();
        return DB::transaction(function () use ($quote, $header) {
            $this->lockCartIdentity();
            $coupon = $this->lockCoupon($quote['coupon_id'] ?? null);
            $resolved = $this->checkout->quote(
                $quote['coupon_code'] ?? null,
                $quote['delivery_area_id'] ?? null
            );
            $this->validateLockedCoupon($coupon, $resolved);

            $sale = Sale::create(array_merge($header, [
                'reference_no' => $this->reference(),
                'warehouse_id' => $resolved['warehouse_id'],
                'biller_id' => $resolved['biller_id'],
                'item' => $resolved['item'],
                'total_qty' => $resolved['total_qty'],
                'total_discount' => 0,
                // Store Sale monetary values in the selected transaction currency.
                // StorefrontCheckoutService keeps canonical quote arithmetic in base
                // currency and exposes converted transaction values under display.
                'total_tax' => $resolved['display']['total_tax'],
                'total_price' => $resolved['display']['sub_total'],
                'grand_total' => $resolved['display']['grand_total'],
                'coupon_id' => $resolved['coupon_id'],
                'coupon_discount' => $resolved['display']['coupon_discount'],
                'shipping_cost' => $resolved['display']['shipping_cost'],
                'sale_status' => 2,
                'payment_status' => 1,
                'sale_type' => 'online',
                'currency_id' => $resolved['currency_id'],
                'exchange_rate' => $resolved['exchange_rate'],
                'ecommerce_inventory_deducted_at' => now(),
                'created_at' => now(),
            ]));

            foreach ($resolved['lines'] as $line) {
                $inventory = $this->deductLine($line, $resolved['warehouse_id']);
                $quantity = (float) $line['qty'];
                $rate = (float) $resolved['exchange_rate'];
                $netLine = ((float) $line['net_total'] - ((int) $line['tax_method'] === 2 ? (float) $line['tax'] : 0)) * $rate;

                Product_Sale::create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['id'],
                    'variant_id' => $inventory['variant_id'],
                    'product_variant_id' => $line['variant_id'],
                    'qty' => $quantity,
                    'sale_unit_id' => $inventory['sale_unit_id'],
                    'net_unit_price' => round($netLine / $quantity, 4),
                    'discount' => round((float) $line['discount'] * $rate, 4),
                    'tax_rate' => $line['tax_rate'],
                    'tax' => round((float) $line['tax'] * $rate, 4),
                    'total' => round((float) $line['gross_total'] * $rate, 4),
                ]);
            }

            if ($coupon) {
                $coupon->used = (int) $coupon->used + 1;
                $coupon->save();
            }

            $accountingResult = $this->accounting->recordSale($sale, 'ecommerce_order_created');
            if (!$accountingResult->success) {
                throw ValidationException::withMessages([
                    'accounting' => $accountingResult->error ?: __('db.Accounting posting failed'),
                ]);
            }

            app(\App\Services\SaleActivityLogService::class)->logCreated($sale);

            return ['sale' => $sale->fresh(), 'quote' => $resolved];
        }, 3);
    }

    public function cancelForCustomer(int $saleId, int $customerId): Sale
    {
        return DB::transaction(function () use ($saleId, $customerId) {
            $sale = Sale::whereKey($saleId)
                ->where('customer_id', $customerId)
                ->where('sale_type', 'online')
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->ecommerce_inventory_restored_at || !$sale->ecommerce_inventory_deducted_at) {
                throw ValidationException::withMessages(['order' => __('db.Ecommerce order cannot be cancelled')]);
            }

            // A paid order must go through the normal refund workflow so the
            // payment/cash journal is reversed together with the receivable.
            // Reversing only the Sale would create an unexplained customer credit.
            if ((float) $sale->paid_amount > 0 || $sale->payments()->exists()) {
                throw ValidationException::withMessages(['order' => __('db.Paid ecommerce order must be refunded before cancellation')]);
            }

            $lines = Product_Sale::where('sale_id', $sale->id)->lockForUpdate()->get();
            foreach ($lines as $line) {
                $this->restoreLine($line, (int) $sale->warehouse_id);
            }

            if ($sale->coupon_id) {
                $coupon = Coupon::whereKey($sale->coupon_id)->lockForUpdate()->first();
                if ($coupon) {
                    $coupon->used = max(0, (int) $coupon->used - 1);
                    $coupon->save();
                }
            }

            $reverse = $this->accounting->reverseTransaction(
                Sale::class,
                $sale->id,
                '_cancelled',
                ['points_earned', 'points_reversed']
            );
            if (!$reverse->success) {
                throw ValidationException::withMessages([
                    'accounting' => $reverse->error ?: __('db.Accounting reversal failed'),
                ]);
            }

            $sale->sale_status = 3;
            $sale->ecommerce_inventory_restored_at = now();
            $sale->save();

            return $sale;
        }, 3);
    }

    private function lockCartIdentity(): void
    {
        $cart = session()->get('cart', []);
        $productIds = collect($cart)->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $variantIds = collect($cart)->pluck('variant_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

        Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();
        if ($variantIds->isNotEmpty()) {
            ProductVariant::whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get();
        }
    }

    private function lockCoupon(?int $couponId): ?Coupon
    {
        return $couponId ? Coupon::whereKey($couponId)->lockForUpdate()->first() : null;
    }

    private function validateLockedCoupon(?Coupon $coupon, array $quote): void
    {
        if (!$quote['coupon_id']) {
            return;
        }
        if (!$coupon || (int) $coupon->id !== (int) $quote['coupon_id'] ||
            ($coupon->quantity && (int) $coupon->used >= (int) $coupon->quantity)) {
            throw ValidationException::withMessages(['coupon_code' => __('db.Ecommerce coupon unavailable')]);
        }
    }

    private function deductLine(array $line, int $warehouseId): array
    {
        $product = Product::whereKey($line['id'])->lockForUpdate()->firstOrFail();
        $lineUnitId = (int) ($line['sale_unit_id'] ?? 0);
        $saleUnitId = $product->type === 'combo' ? 0 : $lineUnitId;
        $baseQuantity = $this->baseQuantity((float) $line['qty'], $lineUnitId);

        if ($product->type === 'combo') {
            $this->adjustCombo($product, $baseQuantity, $warehouseId, -1);
            return ['variant_id' => null, 'sale_unit_id' => 0];
        }
        if ($product->type === 'service' || (bool) $product->in_stock) {
            return [
                'variant_id' => $this->variantDefinitionId($product, $line['variant_id']),
                'sale_unit_id' => $saleUnitId,
            ];
        }

        $variantDefinitionId = null;
        $variant = null;
        if ($line['variant_id']) {
            $variant = ProductVariant::whereKey($line['variant_id'])
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();
            if (!$variant || (float) $variant->qty < $baseQuantity) {
                $this->stockError();
            }
            $variantDefinitionId = (int) $variant->variant_id;
        }

        if ((float) $product->qty < $baseQuantity) {
            $this->stockError();
        }
        $warehouse = $this->warehouseRow($product->id, $warehouseId, $variantDefinitionId);
        if (!$warehouse || (float) $warehouse->qty < $baseQuantity) {
            $this->stockError();
        }

        $product->qty = (float) $product->qty - $baseQuantity;
        $product->save();
        if ($variant) {
            $variant->qty = (float) $variant->qty - $baseQuantity;
            $variant->save();
        }
        $warehouse->qty = (float) $warehouse->qty - $baseQuantity;
        $warehouse->save();

        return ['variant_id' => $variantDefinitionId, 'sale_unit_id' => $saleUnitId];
    }

    private function restoreLine(Product_Sale $line, int $warehouseId): void
    {
        $product = Product::whereKey($line->product_id)->lockForUpdate()->firstOrFail();
        $baseQuantity = $this->baseQuantity((float) $line->qty, (int) $line->sale_unit_id);
        if ($product->type === 'combo') {
            $this->adjustCombo($product, $baseQuantity, $warehouseId, 1);
            return;
        }
        if ($product->type === 'service' || (bool) $product->in_stock) {
            return;
        }

        $product->qty = (float) $product->qty + $baseQuantity;
        $product->save();
        if ($line->product_variant_id) {
            $variant = ProductVariant::whereKey($line->product_variant_id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();
            $variant->qty = (float) $variant->qty + $baseQuantity;
            $variant->save();
        }
        $warehouse = $this->warehouseRow($product->id, $warehouseId, $line->variant_id);
        if (!$warehouse) {
            throw ValidationException::withMessages(['stock' => __('db.Ecommerce inventory unavailable')]);
        }
        $warehouse->qty = (float) $warehouse->qty + $baseQuantity;
        $warehouse->save();
    }

    private function adjustCombo(Product $combo, float $orderedQuantity, int $warehouseId, int $direction): void
    {
        $productIds = array_values(array_filter(explode(',', (string) $combo->product_list), 'strlen'));
        $quantities = explode(',', (string) $combo->qty_list);
        $variantIds = $combo->variant_list ? explode(',', $combo->variant_list) : [];
        $unitIds = $combo->combo_unit_id ? explode(',', $combo->combo_unit_id) : [];

        if (!$productIds || count($productIds) !== count($quantities)) {
            throw ValidationException::withMessages(['stock' => __('db.Ecommerce inventory unavailable')]);
        }

        foreach ($productIds as $index => $productId) {
            $child = Product::whereKey((int) $productId)->lockForUpdate()->first();
            if (!$child) {
                throw ValidationException::withMessages(['stock' => __('db.Ecommerce inventory unavailable')]);
            }
            $required = (float) $quantities[$index] * $orderedQuantity;
            if (!empty($unitIds[$index])) {
                $required = $this->baseQuantity($required, (int) $unitIds[$index]);
            }
            if ($child->type === 'service' || (bool) $child->in_stock) {
                continue;
            }

            $variantDefinitionId = !empty($variantIds[$index]) ? (int) $variantIds[$index] : null;
            $variant = null;
            if ($variantDefinitionId) {
                $variant = ProductVariant::where('product_id', $child->id)
                    ->where('variant_id', $variantDefinitionId)
                    ->lockForUpdate()
                    ->first();
                if (!$variant) {
                    throw ValidationException::withMessages(['stock' => __('db.Ecommerce inventory unavailable')]);
                }
            }
            $warehouse = $this->warehouseRow($child->id, $warehouseId, $variantDefinitionId);
            if (!$warehouse || ($direction < 0 && (
                (float) $child->qty < $required ||
                ($variant && (float) $variant->qty < $required) ||
                (float) $warehouse->qty < $required
            ))) {
                $this->stockError();
            }

            $child->qty = (float) $child->qty + ($direction * $required);
            $child->save();
            if ($variant) {
                $variant->qty = (float) $variant->qty + ($direction * $required);
                $variant->save();
            }
            $warehouse->qty = (float) $warehouse->qty + ($direction * $required);
            $warehouse->save();
        }
    }

    private function warehouseRow(int $productId, int $warehouseId, ?int $variantId): ?Product_Warehouse
    {
        return Product_Warehouse::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->whereNull('product_batch_id')
            ->lockForUpdate()
            ->first();
    }

    private function variantDefinitionId(Product $product, ?int $productVariantId): ?int
    {
        if (!$productVariantId) {
            return null;
        }
        $variant = ProductVariant::whereKey($productVariantId)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->firstOrFail();

        return (int) $variant->variant_id;
    }

    private function baseQuantity(float $quantity, int $unitId): float
    {
        if (!$unitId) {
            return $quantity;
        }
        $unit = Unit::whereKey($unitId)->lockForUpdate()->first();
        if (!$unit) {
            throw ValidationException::withMessages(['stock' => __('db.Ecommerce inventory unavailable')]);
        }

        return match ($unit->operator) {
            '*' => $quantity * (float) $unit->operation_value,
            '/' => $quantity / (float) $unit->operation_value,
            default => $quantity,
        };
    }

    private function reference(): string
    {
        do {
            $reference = 'ecomm-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(8));
        } while (Sale::where('reference_no', $reference)->exists());

        return $reference;
    }

    private function stockError(): never
    {
        throw ValidationException::withMessages(['stock' => __('db.Ecommerce insufficient stock')]);
    }
}
