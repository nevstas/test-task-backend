<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Payments\PaymentHelper;
use Illuminate\Support\Facades\RateLimiter;

class PaymentController extends Controller
{
    /**
     * Confirm Payment
     *
     * @return \Illuminate\Http\Response
     */
    public function confirmPayment(Request $request)
    {
        $paymentGateway = PaymentHelper::detectGatewayAndReturnInstance($request); //PaymentGatewayMegaKassa or PaymentGatewayTopKassa
        return $paymentGateway->gatewayProcess();;
    }
}
