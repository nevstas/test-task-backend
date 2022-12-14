<?php

namespace App\Payments;

/**
 * Payment Gateway for TopKassa service
 */
class PaymentGatewayTopKassa extends PaymentGateway
{
    /**
     * Custom statuses for this gateway
     *
     * @var string[]
     */
    protected $statuses = [
        'created' => 'new',
        'inprogress' => 'pending',
        'paid' => 'completed',
        'expired' => 'expired',
        'rejected' => 'rejected',
    ];

    /**
     * Main method for check payment
     *
     * @return string
     */
    public function gatewayProcess(): string
    {
        $this->rateLimiter(config('app.gateway_topkassa_merchant_attempts_per_day'));
        return parent::gatewayProcess();
    }

    /**
     * Get payment ID from request
     *
     * @return int|string
     */
    protected function getPaymentIdFromRequest(): int|string
    {
        return $this->request['invoice'];
    }

    /**
     * Get sign field from request
     *
     * @return string
     */
    protected function getSignFromRequest(): string
    {
        return $this->request->header('Authorization');
    }

    /**
     * Sign request and return it
     *
     * @return string
     */
    protected function signRequest(): string
    {
        $fields = $this->request->all();
        ksort($fields);
        $sign_string = implode(".", $fields) . config('app.gateway_topkassa_merchant_key');
        return md5($sign_string);
    }

    /**
     * Get merchant ID field from request
     *
     * @return int|string
     */
    protected function getMerchantIDFromRequest(): int|string
    {
        return $this->request->project;
    }

    /**
     * Get merchant ID from config
     *
     * @return int|string
     */
    protected function getMerchantIDFromConfig(): int|string
    {
        return config('app.gateway_topkassa_merchant_id');
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
            'project' => 'required|integer',
            'invoice' => 'required|integer',
            'status' => 'required',
            'amount' => 'required|numeric',
//            'amount_paid' => 'required|numeric',
            'rand' => 'required',
        ];
    }
}
