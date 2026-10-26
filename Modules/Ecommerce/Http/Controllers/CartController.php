<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Modules\Ecommerce\Services\StorefrontCartService;
use Modules\Ecommerce\Services\StorefrontProductService;

class CartController extends Controller
{
    public function index(Request $request, StorefrontCartService $storefrontCart)
    {
        $snapshot = $storefrontCart->current();
        $cart = $snapshot['cart'];
        $total_qty = $snapshot['total_qty'];
        $subTotal = $snapshot['subTotal'];

        $selectedCurrency = $this->selectedCurrency();
        if (!$selectedCurrency) {
            if ($request->ajax()) {
                return $this->cartValidationError(__('db.Ecommerce currency unavailable'));
            }
            abort(503, __('db.Ecommerce currency unavailable'));
        }

        if ($request->ajax()) {
            return response()->json([
                'cart'=>$cart,
                'total_qty'=>$total_qty,
                'subTotal'=>$subTotal,
                'currency_rate'=>$selectedCurrency->exchange_rate,
                'currency_code'=>$selectedCurrency->symbol ?? $selectedCurrency->code,

            ]);
        }

        return theme_view('cart', compact('cart', 'total_qty', 'subTotal'));
    }

    public function addToCart(Request $request, StorefrontCartService $storefrontCart)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'min:1'],
            'qty' => ['nullable', 'numeric', 'gt:0', 'max:' . StorefrontProductService::MAX_CART_QUANTITY],
            'variant_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->has('product_id')
                ? __('db.Ecommerce product unavailable')
                : __('db.Ecommerce invalid quantity');
            return $this->cartValidationError($message);
        }

        $selectedCurrency = $this->selectedCurrency();
        if (!$selectedCurrency) {
            return $this->cartValidationError(__('db.Ecommerce currency unavailable'));
        }

        try {
            $snapshot = $storefrontCart->add(
                (int) $request->input('product_id'),
                $request->filled('variant_id') ? (int) $request->input('variant_id') : null,
                $request->filled('qty') ? (float) $request->input('qty') : 1.0
            );
        } catch (InvalidArgumentException $exception) {
            return $this->cartValidationError($exception->getMessage());
        }

        session(['currency_code'=>$selectedCurrency->code,'currency_rate'=>$selectedCurrency->exchange_rate]);

        return response()->json([
            'total_qty' => $snapshot['total_qty'],
            'subTotal' => $snapshot['subTotal'],
            'line' => $snapshot['line'],
            'removed' => $snapshot['removed'],
            'success'=>__('db.Product added to cart'),
            'currency_code'=>$selectedCurrency->code,
            'currency_rate'=>$selectedCurrency->exchange_rate,
        ]);
    }

    private function cartValidationError(string $message)
    {
        return response()->json([
            'message' => $message,
            'errors' => ['product' => [$message]],
        ], 422);
    }

    private function selectedCurrency(): ?Currency
    {
        $selectedCurrency = null;
        if (session()->get('currency_code')) {
            $selectedCurrency = Currency::where('code', session()->get('currency_code'))
                ->where('is_active', 1)
                ->first();
        }

        return $selectedCurrency ?? Currency::where('is_active', 1)->orderBy('id')->first();
    }

    public function updateCart(Request $request, StorefrontCartService $storefrontCart)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'product_qty' => ['required', 'numeric', 'gt:0', 'max:' . StorefrontProductService::MAX_CART_QUANTITY],
        ]);
        if ($validator->fails()) {
            return $this->cartValidationError(__('db.Ecommerce invalid quantity'));
        }

        try {
            $snapshot = $storefrontCart->update(
                (int) $request->input('product_id'),
                $request->filled('variant_id') ? (int) $request->input('variant_id') : null,
                (float) $request->input('product_qty')
            );
        } catch (InvalidArgumentException $exception) {
            return $this->cartValidationError($exception->getMessage());
        }

        return response()->json([
            'total_qty' => $snapshot['total_qty'],
            'subTotal' => $snapshot['subTotal'],
            'line' => $snapshot['line'],
            'removed' => $snapshot['removed'],
            'success' => __('db.Ecommerce cart updated'),
        ]);
    }

    public function removeFromCart(Request $request, StorefrontCartService $storefrontCart)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
        ]);
        if ($validator->fails()) {
            return $this->cartValidationError(__('db.Ecommerce cart line unavailable'));
        }
        $selectedCurrency = $this->selectedCurrency();
        if (!$selectedCurrency) {
            return $this->cartValidationError(__('db.Ecommerce currency unavailable'));
        }
        try {
            $snapshot = $storefrontCart->remove(
                (int) $request->input('product_id'),
                $request->filled('variant_id') ? (int) $request->input('variant_id') : null
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        // Save updated values back to the session
        session(['currency_code'=>$selectedCurrency->code,'currency_rate'=>$selectedCurrency->exchange_rate]);

        // Return success response with the "id-variant1-variant2" format for `cart_key`
        return response()->json([
            'deleted_item' => $snapshot['deleted_key'],
            'total_qty' => $snapshot['total_qty'],
            'subTotal' => $snapshot['subTotal'],
            'currency_code'=>$selectedCurrency->code,
            'currency_rate'=>$selectedCurrency->exchange_rate,
            'success' => __('db.Ecommerce cart line removed')
        ]);
    }

}
