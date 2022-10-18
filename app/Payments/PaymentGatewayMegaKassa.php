<?php

namespace App\Payments;

/**
 * Payment Gateway for MegaKassa service
 */
class PaymentGatewayMegaKassa extends PaymentGateway
{

    /**
     * Main method for check payment
     *
     * @return string
     */
    public function gatewayProcess(): string
    {
        $this->rateLimiter(config('app.gateway_megakassa_merchant_attempts_per_day'));
        return parent::gatewayProcess();
    }

    /**
     * Get payment ID from request
     *
     * @return int|string
     */
    protected function getPaymentIdFromRequest(): int|string
    {
        return $this->request->payment_id;
    }

    /**
     * Get sign field from request
     *
     * @return string
     */
    protected function getSignFromRequest(): string
    {
        return $this->request->sign;
    }

    /**
     * Sign request and return it
     *
     * @return string
     */
    protected function signRequest(): string
    {
        $fields = $this->request->all();
        unset($fields['sign']);
        ksort($fields);
        $sign_string = implode(":", $fields) . config('app.gateway_megakassa_merchant_key');
        return hash('sha256', $sign_string);
    }

    /**
     * Get merchant ID field from request
     *
     * @return int|string
     */
    protected function getMerchantIDFromRequest(): int|string
    {
        return $this->request->merchant_id;
    }

    /**
     * Get merchant ID from config
     *
     * @return int|string
     */
    protected function getMerchantIDFromConfig(): int|string
    {
        return config('app.gateway_megakassa_merchant_id');
    }

    /**
     * Set limits for requests
     *
     * @param $attempts
     * @return void
     */
    protected function rateLimiter($attempts): void {
        parent::rateLimiter($attempts);
    }

    /**
     * Validate rules for this gateway
     *
     * @return string[]
     */
    protected function validateRules(): array {
        return [
            'merchant_id' => 'required|integer',
            'payment_id' => 'required|integer',
            'status' => 'required',
            'amount' => 'required|numeric',
            'amount_paid' => 'required|numeric',
            'timestamp' => 'required|integer',
            'sign' => 'required',
        ];
    }
}
