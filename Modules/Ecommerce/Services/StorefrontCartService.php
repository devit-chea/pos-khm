<?php

namespace Modules\Ecommerce\Services;

use InvalidArgumentException;

class StorefrontCartService
{
    private const MONEY_SCALE = 4;
    private const QUANTITY_SCALE = 4;

    public function __construct(
        private StorefrontProductService $products,
        private StorefrontVariantService $variants,
        private StorefrontAvailabilityService $availability,
        private StorefrontPricingService $pricing,
        private DemoMediaResolver $demoMedia
    ) {
    }

    public function current(): array
    {
        return $this->recalculate(session()->get('cart', []));
    }

    public function add(int $productId, ?int $variantId, float $quantity): array
    {
        $this->validateQuantity($quantity);
        $cart = $this->current()['cart'];
        $key = $this->key($productId, $variantId);
        $quantity += (float) ($cart[$key]['qty'] ?? 0);
        $this->validateQuantity($quantity);
        $cart[$key] = [
            'id' => $productId,
            'variant_id' => $variantId,
            'qty' => $quantity,
        ];

        return $this->recalculate($cart, $key, true);
    }

    public function update(int $productId, ?int $variantId, float $quantity): array
    {
        $this->validateQuantity($quantity);
        $cart = $this->current()['cart'];
        $key = $this->key($productId, $variantId);
        if (!array_key_exists($key, $cart)) {
            throw new InvalidArgumentException(__('db.Ecommerce cart line unavailable'));
        }

        $cart[$key]['qty'] = $quantity;

        return $this->recalculate($cart, $key, true);
    }

    public function remove(int $productId, ?int $variantId): array
    {
        $cart = $this->current()['cart'];
        $key = $this->key($productId, $variantId);
        if (!array_key_exists($key, $cart)) {
            throw new InvalidArgumentException(__('db.Ecommerce cart line unavailable'));
        }

        unset($cart[$key]);
        $snapshot = $this->recalculate($cart);
        $snapshot['deleted_key'] = $key;

        return $snapshot;
    }

    public function recalculate(array $cart, ?string $requiredKey = null, bool $failRequired = false): array
    {
        $resolved = [];
        $removed = [];

        foreach ($cart as $storedKey => $storedLine) {
            try {
                $line = $this->resolveLine($storedLine, (string) $storedKey);
                $resolved[$line['key']] = $line;
            } catch (InvalidArgumentException $exception) {
                if ($failRequired && $requiredKey === (string) $storedKey) {
                    throw $exception;
                }
                $removed[(string) $storedKey] = $exception->getMessage();
            }
        }

        $totalQuantity = $this->normalizeQuantity(array_sum(array_column($resolved, 'qty')));
        $subtotal = $this->normalizeMoney(array_sum(array_column($resolved, 'total_price')));
        session([
            'cart' => $resolved,
            'total_qty' => $totalQuantity,
            'subTotal' => $subtotal,
        ]);

        return [
            'cart' => $resolved,
            'total_qty' => $totalQuantity,
            'subTotal' => $subtotal,
            'removed' => $removed,
            'line' => $requiredKey ? ($resolved[$requiredKey] ?? null) : null,
        ];
    }

    public function key(int $productId, ?int $variantId): string
    {
        return $variantId ? $productId . ',' . $variantId : (string) $productId;
    }

    private function resolveLine(array $storedLine, string $storedKey): array
    {
        $productId = filter_var($storedLine['id'] ?? null, FILTER_VALIDATE_INT);
        $variantId = isset($storedLine['variant_id']) && $storedLine['variant_id'] !== null
            ? filter_var($storedLine['variant_id'], FILTER_VALIDATE_INT)
            : null;
        $quantity = $storedLine['qty'] ?? null;

        if (!$productId || !is_numeric($quantity)) {
            throw new InvalidArgumentException(__('db.Ecommerce cart line unavailable'));
        }
        $quantity = (float) $quantity;
        $this->validateQuantity($quantity);

        // Demo catalog filters control discovery, not the underlying SalePro
        // cart. A visitor switching profiles must not corrupt an existing cart.
        $product = $this->products->findForCart((int) $productId);
        if (!$product || !$this->products->supportsGenericCart($product)) {
            throw new InvalidArgumentException(__('db.Ecommerce product unavailable'));
        }

        $variant = null;
        if ((bool) $product->is_variant) {
            if (!$variantId) {
                throw new InvalidArgumentException(__('db.Ecommerce configuration required'));
            }
            $variant = $this->variants->resolve($product->id, (int) $variantId);
            if (!$variant) {
                throw new InvalidArgumentException(__('db.Ecommerce configuration required'));
            }
        } elseif ($variantId) {
            throw new InvalidArgumentException(__('db.Ecommerce configuration required'));
        }

        $availability = $this->availability->forProduct($product, $variant ? (int) $variant->id : null);
        if ($availability['reason'] === StorefrontAvailabilityService::REASON_CONFIGURATION_REQUIRED) {
            throw new InvalidArgumentException(__('db.Ecommerce configuration required'));
        }
        $availableQuantity = $availability['available_qty'];
        if (!$availability['available'] || ($availableQuantity !== null && $quantity > $availableQuantity)) {
            throw new InvalidArgumentException(__('db.Ecommerce insufficient stock'));
        }

        $price = $this->pricing->resolve($product, $variant);
        $unitPrice = $this->normalizeMoney($price['selling_price']);
        $lineTotal = $this->multiply($unitPrice, $quantity);
        $key = $this->key($product->id, $variant ? (int) $variant->id : null);

        return [
            'key' => $key,
            'id' => (int) $product->id,
            'variant_id' => $variant ? (int) $variant->id : null,
            'variant' => $variant->name ?? 0,
            'item_code' => $variant->item_code ?? $product->code,
            'image' => $product->image,
            'storefront_image_url' => $this->demoMedia->productImageUrl($product, 'medium'),
            'name' => $product->name,
            'slug' => $product->slug,
            'qty' => $this->normalizeQuantity($quantity),
            'sale_unit_id' => $product->sale_unit_id,
            'unit_price' => $unitPrice,
            'regular_price' => $this->normalizeMoney($price['regular_price']),
            'promotion_active' => (bool) $price['promotion_active'],
            'total_price' => $lineTotal,
            'available_qty' => $availableQuantity,
            'stock_unlimited' => $availability['stock_unlimited'],
        ];
    }

    private function validateQuantity(float $quantity): void
    {
        if (!is_finite($quantity) || $quantity <= 0 || $quantity > StorefrontProductService::MAX_CART_QUANTITY) {
            throw new InvalidArgumentException(__('db.Ecommerce invalid quantity'));
        }
    }

    private function multiply(float $money, float $quantity): float
    {
        $moneyUnits = (int) round($money * (10 ** self::MONEY_SCALE));
        $quantityUnits = (int) round($quantity * (10 ** self::QUANTITY_SCALE));
        $resultUnits = (int) round(($moneyUnits * $quantityUnits) / (10 ** self::QUANTITY_SCALE));

        return $this->normalizeMoney($resultUnits / (10 ** self::MONEY_SCALE));
    }

    private function normalizeMoney(float $value): float
    {
        return round($value, self::MONEY_SCALE);
    }

    private function normalizeQuantity(float $value): float
    {
        return round($value, self::QUANTITY_SCALE);
    }
}
