<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Modules\Ecommerce\Entities\DeliveryArea;
use Modules\Ecommerce\Services\StorefrontCheckoutService;
use Modules\Ecommerce\Services\StorefrontPaymentService;
use Modules\Ecommerce\Services\StorefrontCacheKey;

class CheckoutController extends Controller
{
	public function index(
        Request $request,
        StorefrontCheckoutService $checkout,
        StorefrontPaymentService $payments
    )
	{
        $quote = $checkout->quote(
            $request->filled('coupon_code') ? $request->string('coupon_code')->toString() : null,
            $request->filled('delivery_area_id') ? $request->integer('delivery_area_id') : null,
            false
        );
		$cart = $quote['cart'];
		$total_qty = $quote['total_qty'];
		$subTotal = $quote['sub_total'];
        $delivery_areas = DeliveryArea::active()->orderBy('name')->get();

		$settingsKey = StorefrontCacheKey::for('setting');
		if(cache()->has($settingsKey)){
			$settings = cache($settingsKey);
			if(isset($settings->checkout_pages)){
				$pages = DB::table('pages')->select('id','page_name','slug')->whereIn('id',json_decode($settings->checkout_pages))->get();
			}else{
				$pages = NULL;
			}
		} else {
		    $pages = NULL;
		}

		$gateways = DB::table('external_services')
            ->where('type', 'payment')
            ->where('active', 1)
            ->whereIn('name', $payments->exposedProviderNames())
            ->get();
		foreach ($gateways as $gateway) {
			$gateway->module_status = json_decode($gateway->module_status);
		}

		if(auth()->user() && auth()->user()->role_id == 5) {
			$customer = DB::table('customers')->where('user_id',auth()->user()->id)->first();
			$addresses = DB::table('customer_addresses')->where('customer_id',$customer->id)->get();
			$def_address = $addresses->where('default',1)->first();

			return theme_view('checkout', compact('cart', 'total_qty', 'subTotal', 'quote', 'customer', 'addresses', 'def_address', 'pages','gateways','delivery_areas'));
		}

		return theme_view('checkout', compact('cart', 'total_qty', 'subTotal', 'quote', 'pages','gateways','delivery_areas'));
	}

	public function quote(Request $request, StorefrontCheckoutService $checkout)
	{
        $validated = $request->validate([
            'coupon_code' => ['nullable', 'string', 'max:255'],
            'delivery_area_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json($checkout->quote(
            $validated['coupon_code'] ?? null,
            isset($validated['delivery_area_id']) ? (int) $validated['delivery_area_id'] : null
        ));
	}

	public function applyCoupon(Request $request, StorefrontCheckoutService $checkout)
	{
        $request->validate(['coupon_code' => ['required', 'string', 'max:255']]);

        return response()->json($checkout->quote(
            $request->string('coupon_code')->toString(),
            $request->filled('delivery_area_id') ? $request->integer('delivery_area_id') : null
        ));
	}

	public function applyGiftCard()
	{
        return response()->json([
            'message' => __('db.Ecommerce gift card checkout unavailable'),
        ], 422);
	}
}
