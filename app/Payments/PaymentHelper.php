<?php

namespace App\Payments;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Payment Helper
 */
class PaymentHelper {

    /**
     * Detect type of PaymentGateway and return instance of Class
     *
     * @param Request $request
     * @return PaymentGateway
     */
    public static function detectGatewayAndReturnInstance(Request $request): PaymentGateway{
        if ($request->isJson() && $request->has('merchant_id')) {
            return new PaymentGatewayMegaKassa($request);
        } else if ($request->has('project')) {
            return new PaymentGatewayTopKassa($request);
        } else {
            abort(response()->json(['message' => 'Gateway not found'], Response::HTTP_BAD_REQUEST));
        }
    }
}
