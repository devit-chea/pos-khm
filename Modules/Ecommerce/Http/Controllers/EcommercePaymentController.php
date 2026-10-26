<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Services\Payment\MpesaService;
use App\Services\Payment\MtnMoMoService;

class EcommercePaymentController extends Controller
{
    /**
     * M-Pesa Endpoints
     */
    public function mpesaPush(Request $request, MpesaService $mpesaService)
    {
        return $mpesaService->push($request);
    }

    public function mpesaQr(Request $request, MpesaService $mpesaService)
    {
        return $mpesaService->generateDynamicQr($request);
    }

    public function mpesaStatus(Request $request, MpesaService $mpesaService)
    {
        return $mpesaService->queryStatus($request);
    }

    /**
     * MTN MoMo Endpoints
     */
    public function momoPush(Request $request, MtnMoMoService $momoService)
    {
        return $momoService->push($request);
    }

    public function momoQr(Request $request, MtnMoMoService $momoService)
    {
        return $momoService->generateUssdQr($request);
    }

    public function momoStatus(Request $request, MtnMoMoService $momoService)
    {
        return $momoService->queryStatus($request);
    }

    /**
     * PayHere Endpoints
     */
    public function payherePush(Request $request, \App\Services\Payment\PayHereService $payhereService)
    {
        return $payhereService->push($request);
    }

    public function payhereStatus(Request $request, \App\Services\Payment\PayHereService $payhereService)
    {
        return $payhereService->queryStatus($request);
    }
}
