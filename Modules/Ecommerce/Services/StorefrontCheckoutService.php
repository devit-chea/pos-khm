<?php

namespace Modules\Ecommerce\Services;

use App\Models\Coupon;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Entities\DeliveryArea;

class StorefrontCheckoutService
{
    private const SCALE = 4;

    public function __construct(private StorefrontCartService $cart)
    {
    }

    public function quote(?string $couponCode = null, ?int $deliveryAreaId = null, bool $requireDeliveryArea = true): array
    {
        $snapshot = $this->cart->current();
        if (!$snapshot['cart']) {
            throw ValidationException::withMessages(['cart' => __('db.Ecommerce cart is empty')]);
        }

        $settings = DB::table('ecommerce_settings')->latest('id')->first();
        if (!$settings) {
            throw ValidationException::withMessages(['checkout' => __('db.Ecommerce checkout unavailable')]);
        }

        $subtotal = $this->money($snapshot['subTotal']);
        $coupon = $this->resolveCoupon($couponCode, $subtotal);
        $discount = $coupon['discount'];
        $discountedSubtotal = $this->money($subtotal - $discount);
        $tax = $this->tax($snapshot['cart'], $discount, $subtotal);
        $shipping = $this->shipping($settings, $discountedSubtotal, $deliveryAreaId, $requireDeliveryArea);
        $grandTotal = $this->money($discountedSubtotal + $tax['exclusive'] + $shipping['amount']);
        $currency = $this->currency();

        return [
            'cart' => $snapshot['cart'],
            'item' => count($snapshot['cart']),
            'total_qty' => $snapshot['total_qty'],
            'sub_total' => $subtotal,
            'coupon_id' => $coupon['id'],
            'coupon_code' => $coupon['code'],
            'coupon_discount' => $discount,
            'discounted_sub_total' => $discountedSubtotal,
            'total_tax' => $tax['total'],
            'exclusive_tax' => $tax['exclusive'],
            'lines' => $tax['lines'],
            'delivery_area_id' => $shipping['delivery_area_id'],
            'delivery_area_required' => $shipping['required'],
            'shipping_cost' => $shipping['amount'],
            'grand_total' => $grandTotal,
            'warehouse_id' => $this->configuredId('warehouses', $settings->warehouse_id ?? null, 'warehouse'),
            'biller_id' => $this->configuredId('billers', $settings->biller_id ?? null, 'biller'),
            'currency_id' => (int) $currency->id,
            'currency_code' => $currency->code,
            'currency_symbol' => $currency->symbol ?? $currency->code,
            'exchange_rate' => $this->money((float) ($currency->exchange_rate ?: 1)),
            'display' => [
                'sub_total' => $this->convert($subtotal, $currency),
                'coupon_discount' => $this->convert($discount, $currency),
                'total_tax' => $this->convert($tax['total'], $currency),
                'shipping_cost' => $this->convert($shipping['amount'], $currency),
                'grand_total' => $this->convert($grandTotal, $currency),
            ],
        ];
    }

    private function resolveCoupon(?string $code, float $subtotal): array
    {
        $code = trim((string) $code);
        if ($code === '') {
            return ['id' => null, 'code' => null, 'discount' => 0.0];
        }

        $coupon = Coupon::where('code', $code)->where('is_active', 1)->first();
        $valid = $coupon
            && (!$coupon->expired_date || $coupon->expired_date >= now()->toDateString())
            && (!$coupon->quantity || (int) $coupon->used < (int) $coupon->quantity)
            && $subtotal >= (float) ($coupon->minimum_amount ?? 0)
            && in_array($coupon->type, ['percentage', 'fixed'], true);

        if (!$valid) {
            throw ValidationException::withMessages(['coupon_code' => __('db.Ecommerce coupon unavailable')]);
        }

        $amount = (float) $coupon->amount;
        if ($coupon->type === 'percentage') {
            if ($amount <= 0 || $amount > 100) {
                throw ValidationException::withMessages(['coupon_code' => __('db.Ecommerce coupon unavailable')]);
            }
            $discount = $subtotal * $amount / 100;
        } else {
            if ($amount <= 0) {
                throw ValidationException::withMessages(['coupon_code' => __('db.Ecommerce coupon unavailable')]);
            }
            $discount = $amount;
        }

        return [
            'id' => (int) $coupon->id,
            'code' => $coupon->code,
            'discount' => $this->money(min($subtotal, $discount)),
        ];
    }

    private function tax(array $cart, float $discount, float $subtotal): array
    {
        $products = DB::table('products')
            ->leftJoin('taxes', function ($join) {
                $join->on('taxes.id', '=', 'products.tax_id')->where('taxes.is_active', 1);
            })
            ->whereIn('products.id', array_column($cart, 'id'))
            ->get(['products.id', 'products.tax_method', 'taxes.rate'])
            ->keyBy('id');
        $lines = [];
        $totalTax = 0.0;
        $exclusiveTax = 0.0;
        $remainingDiscount = $discount;
        $lastKey = array_key_last($cart);

        foreach ($cart as $key => $line) {
            $lineTotal = (float) $line['total_price'];
            $share = $subtotal > 0
                ? ($key === $lastKey ? $remainingDiscount : $this->money($discount * $lineTotal / $subtotal))
                : 0.0;
            $remainingDiscount = $this->money($remainingDiscount - $share);
            $taxable = $this->money(max(0, $lineTotal - $share));
            $product = $products->get($line['id']);
            $rate = $this->money((float) ($product->rate ?? 0));
            $method = (int) ($product->tax_method ?? 1);
            $lineTax = 0.0;

            if ($rate > 0) {
                $lineTax = $method === 2
                    ? $this->money($taxable * $rate / (100 + $rate))
                    : $this->money($taxable * $rate / 100);
            }
            if ($method !== 2) {
                $exclusiveTax = $this->money($exclusiveTax + $lineTax);
            }
            $totalTax = $this->money($totalTax + $lineTax);
            $lines[$key] = array_merge($line, [
                'discount' => $share,
                'tax_rate' => $rate,
                'tax_method' => $method,
                'tax' => $lineTax,
                'net_total' => $taxable,
                'gross_total' => $this->money($taxable + ($method === 2 ? 0 : $lineTax)),
            ]);
        }

        return ['total' => $totalTax, 'exclusive' => $exclusiveTax, 'lines' => $lines];
    }

    private function shipping(object $settings, float $subtotal, ?int $deliveryAreaId, bool $requireDeliveryArea): array
    {
        $threshold = max(0, (float) ($settings->free_shipping_from ?? 0));
        $free = $threshold > 0 && $subtotal >= $threshold;
        $activeAreas = DeliveryArea::active();
        $hasAreas = (clone $activeAreas)->exists();
        $area = null;

        if ($deliveryAreaId) {
            $area = (clone $activeAreas)->whereKey($deliveryAreaId)->first();
            if (!$area) {
                throw ValidationException::withMessages(['delivery_area_id' => __('db.Ecommerce delivery area unavailable')]);
            }
        } elseif ($hasAreas && $requireDeliveryArea) {
            throw ValidationException::withMessages(['delivery_area_id' => __('db.Ecommerce delivery area required')]);
        }

        return [
            'delivery_area_id' => $area ? (int) $area->id : null,
            'required' => $hasAreas,
            'amount' => $free ? 0.0 : $this->money((float) ($area->delivery_charge ?? ($hasAreas ? 0 : ($settings->flat_rate_shipping ?? 0)))),
        ];
    }

    private function currency(): object
    {
        $currency = null;
        if (session('currency_code')) {
            $currency = Currency::where('code', session('currency_code'))->where('is_active', 1)->first();
        }
        $currency ??= Currency::where('is_active', 1)->orderBy('id')->first();
        if (!$currency || (float) $currency->exchange_rate <= 0) {
            throw ValidationException::withMessages(['currency' => __('db.Ecommerce currency unavailable')]);
        }
        session(['currency_code' => $currency->code, 'currency_rate' => $currency->exchange_rate]);

        return $currency;
    }

    private function configuredId(string $table, mixed $id, string $field): int
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id || !DB::table($table)->where('id', $id)->exists()) {
            throw ValidationException::withMessages([$field => __('db.Ecommerce checkout unavailable')]);
        }

        return (int) $id;
    }

    private function convert(float $amount, object $currency): float
    {
        return $this->money($amount * (float) $currency->exchange_rate);
    }

    private function money(float $value): float
    {
        return round($value, self::SCALE);
    }
}
