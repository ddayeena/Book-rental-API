<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function liqpay(Request $request)
    {
        $data = $request->input('data');
        $signature = $request->input('signature');

        if (!$data || !$signature) {
            return $this->error('Bad request', 400); 
        }

        $payload = $this->paymentService->handleWebhook($data, $signature);

        if (!$payload) {
            Log::warning('LiqPay Webhook: Invalid signature detected.');
            return $this->error('Invalid signature', 403);
        }

        $successStatuses = ['success', 'sandbox'];

        if (in_array($payload['status'], $successStatuses)) {
            $rental = Rental::find($payload['order_id']);

            if ($rental && $rental->payment_status !== PaymentStatus::PAID) {
                $rental->update([
                    'payment_status' => PaymentStatus::PAID,
                    'transaction_id' => $payload['payment_id'] ?? null, 
                ]);

                Log::info("Rental {$rental->id} paid successfully via LiqPay.");
            }
        }

        return $this->success(null, 'Webhook processed successfully');
    }
}