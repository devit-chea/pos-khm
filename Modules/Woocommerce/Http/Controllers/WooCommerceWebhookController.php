<?php

namespace Modules\Woocommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Woocommerce\Exceptions\WebhookRejected;
use Modules\Woocommerce\Services\Webhooks\WooCommerceWebhookIntake;

class WooCommerceWebhookController extends Controller
{
    public function store(Request $request, string $publicId, WooCommerceWebhookIntake $intake): JsonResponse
    {
        try {
            $result = $intake->receive($request, $publicId);
            return response()->json([
                'outcome' => $result['outcome'],
                'receipt_id' => $result['receipt_id'],
            ], $result['status']);
        } catch (WebhookRejected $exception) {
            $generic = $exception->httpStatus === 404 ? 'Webhook unavailable.' : 'Webhook request rejected.';
            return response()->json(['message' => $generic, 'code' => $exception->failureCode], $exception->httpStatus);
        } catch (\Throwable) {
            return response()->json(['message' => 'Webhook request could not be accepted.', 'code' => 'intake_unavailable'], 503);
        }
    }
}
