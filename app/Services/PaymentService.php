<?php

namespace App\Services;

use App\Models\Rental;

class PaymentService
{
    protected readonly string $publicKey;
    protected readonly string $privateKey;
    protected readonly string $sandbox;

    public function __construct()
    {
        $this->publicKey = config('services.liqpay.public_key');
        $this->privateKey = config('services.liqpay.private_key');
        $this->sandbox = config('services.liqpay.sandbox', true) ? '1' : '0';
    }

    /**
     * Generate a checkout URL for LiqPay.
     */
    public function generateCheckoutUrl(Rental $rental): string
    {
        $shortId = strtoupper(substr($rental->id, -6));
        
        $params = [
            'public_key'   => $this->publicKey,
            'version'      => '3',
            'action'       => 'pay',
            'amount'       => (float) $rental->total_price,
            'currency'     => 'UAH',
            'description'  => "Оренда книги замовлення (№{$shortId})",
            'order_id'     => $rental->id,
            'server_url'   => rtrim(config('app.url'), '/') . '/api/v1/webhooks/liqpay',
            'result_url'   => config('app.frontend_url', 'http://localhost:3000') . '/profile/rentals',
            'sandbox'      => $this->sandbox
        ];

        $data = base64_encode(json_encode($params));
        $signature = $this->generateSignature($data);

        return "https://www.liqpay.ua/api/3/checkout?data=" . urlencode($data) . "&signature=" . urlencode($signature);
    }

    /**
     * Parse and verify LiqPay webhook data.
     * Returns the decoded array if the signature is valid, or null if it's fake.
     */
    public function handleWebhook(string $data, string $signature): ?array
    {
        $expectedSignature = $this->generateSignature($data);

        if ($signature !== $expectedSignature) {
            return null;
        }

        return json_decode(base64_decode($data), true);
    }

    /**
     * Generate a unique digital signature for LiqPay.
     */
    private function generateSignature(string $data): string
    {
        return base64_encode(sha1($this->privateKey . $data . $this->privateKey, true));
    }
}