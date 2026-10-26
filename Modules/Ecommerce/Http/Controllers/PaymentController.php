<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Services\Payments\PaymentProviderRegistry;
use Modules\Ecommerce\Services\StorefrontPaymentService;

class PaymentController extends Controller
{
    public function initiate(
        string $provider,
        PaymentProviderRegistry $registry,
        StorefrontPaymentService $payments
    ) {
        $attempt = $this->attempt($provider, $payments);
        if ($attempt->provider_reference) {
            throw ValidationException::withMessages(['payment' => __('db.Payment was already initiated')]);
        }
        $result = $registry->adapter($attempt->provider)->initiate($attempt);
        $payments->recordProviderReference($attempt->public_id, $result['provider_reference']);

        return response()->json($result);
    }

    public function complete(
        Request $request,
        string $provider,
        PaymentProviderRegistry $registry,
        StorefrontPaymentService $payments
    ) {
        $attempt = $this->attempt($provider, $payments);
        $verified = $registry->adapter($attempt->provider)->verify($attempt, $request->all());
        $completed = $payments->completeVerified(
            $attempt->public_id,
            $verified['transaction_id'],
            $verified['amount'],
            $verified['currency'],
            $verified['evidence']
        );
        $payments->clearCartAfterVerifiedBrowserReturn($completed->public_id);

        return response()->json([
            'success' => true,
            'redirect' => route('order.success', $completed->sale_id
                ? \App\Models\Sale::findOrFail($completed->sale_id)->reference_no
                : ''),
        ]);
    }

    private function attempt(string $provider, StorefrontPaymentService $payments)
    {
        $publicId = (string) session('ecommerce_payment_attempt');
        abort_if($publicId === '', 404);
        $attempt = $payments->read($publicId);
        abort_unless((int) session('ecommerce_payment_sale_id') === (int) $attempt->sale_id, 403);
        abort_unless($attempt->provider === strtolower($provider), 404);
        return $attempt;
    }
}
