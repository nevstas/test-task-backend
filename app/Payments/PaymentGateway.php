<?php

namespace App\Payments;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

/**
 * Abstract class Payment Gateway
 */
abstract class PaymentGateway
{
    /**
     * Laravel Request
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Payment model
     *
     * @var Payment
     */
    protected Payment $payment;

    /**
     * Default statuses for gateway
     *
     * @var string[]
     */
    protected $statuses = [
        'new' => 'new',
        'pending' => 'pending',
        'completed' => 'completed',
        'expired' => 'expired',
        'rejected' => 'rejected',
    ];

    /**
     * Get payment ID from request
     *
     * @return int|string
     */
    abstract protected function getPaymentIdFromRequest(): int|string;

    /**
     * Get sign field from request
     *
     * @return string
     */
    abstract protected function getSignFromRequest(): string;

    /**
     * Sign request and return it
     *
     * @return string
     */
    abstract protected function signRequest(): string;

    /**
     * Get merchant ID field from request
     *
     * @return int|string
     */
    abstract protected function getMerchantIDFromRequest(): int|string;

    /**
     * Get merchant ID from config
     *
     * @return int|string
     */
    abstract protected function getMerchantIDFromConfig(): int|string;

    /**
     * Validate rules for this gateway
     *
     * @return array
     */
    abstract protected function validateRules(): array;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->payment = Payment::findOrFail($this->getPaymentIdFromRequest());
    }

    /**
     * Main method for check payment
     *
     * @return string
     */
    public function gatewayProcess(): string
    {
        $this->validateRequest();
        $this->checkSign();
        $this->checkMerchantID();
        $this->checkAmount();
        $this->savePayment();
        return response()->json([
            'status' => 'ok',
        ]);
    }

    /**
     * Convert custom status to default status
     *
     * @param $status
     * @return string
     */
    protected function convertStatus($status): string
    {
        if (!isset($this->statuses[$status])) {
            abort(response()->json(['message' => 'Status not found'], Response::HTTP_BAD_REQUEST));
        }
        return $this->statuses[$status];
    }

    /**
     * Validate request
     *
     * @return void
     */
    private function validateRequest(): void
    {
        $rules = $this->validateRules();
        $validator = Validator::make($this->request->all(), $rules);
        if ($validator->fails()) {
            abort(response()->json(['message' => $validator->messages()], Response::HTTP_BAD_REQUEST));
        }
    }

    /**
     * Check sign of request
     *
     * @return void
     */
    private function checkSign(): void
    {
        $signFromRequest = $this->getSignFromRequest();
        $signRequest = $this->signRequest();
        if ($signFromRequest !== $signRequest) {
            abort(response()->json(['message' => 'Wrong sign'], Response::HTTP_FORBIDDEN));
        }
    }

    /**
     * Check merchant ID
     *
     * @return void
     */
    private function checkMerchantID(): void
    {
        if ($this->getMerchantIDFromRequest() != $this->getMerchantIDFromConfig()) {
            abort(response()->json(['message' => 'Merchant ID not found'], Response::HTTP_BAD_REQUEST));
        }
    }

    /**
     * Check "amount" from db equals "amount_paid" from request
     *
     * @return void
     */
    private function checkAmount(): void
    {
        if ($this->payment->amount != $this->request->amount_paid) {
            abort(response()->json(['message' => 'Wrong payment amount'], Response::HTTP_BAD_REQUEST));
        }
    }

    /**
     * Save model Payment
     *
     * @return void
     */
    private function savePayment(): void
    {
        $this->payment->status = $this->convertStatus($this->request->status);
        $this->payment->amount_paid = $this->request->amount_paid;
        $this->payment->save();
    }

    /**
     * Set limits for requests
     *
     * @param $attempts
     * @return void
     */
    protected function rateLimiter($attempts): void
    {
        $executed = RateLimiter::attempt(
            'confirm-payment:' . $this::class,
            $attempts,
            function () {
                return true;
            },
            60 * 60 * 24
        );

        if (!$executed) {
            abort(response()->json(['message' => 'Too Many Requests'], Response::HTTP_TOO_MANY_REQUESTS));
        }
    }
}
