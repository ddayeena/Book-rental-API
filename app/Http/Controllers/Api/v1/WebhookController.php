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

        // Liqpay statuses
        $successStatuses = ['success', 'sandbox'];
        $failedStatuses = ['failure', 'error'];

        $rental = Rental::find($payload['order_id']);

        if ($rental) {
            // If payment was successful, update the rental status to PAID
            if (in_array($payload['status'], $successStatuses)) {
                if ($rental->payment_status !== PaymentStatus::PAID) {
                    $rental->update([
                        'payment_status' => PaymentStatus::PAID,
                        'transaction_id' => $payload['payment_id'] ?? null,
                    ]);
                    Log::info("Rental {$rental->id} paid successfully via LiqPay.");
                }
            }
            // if payment failed, update the rental status to FAILED only if it was still PENDING
            elseif (in_array($payload['status'], $failedStatuses)) {
                if ($rental->payment_status === PaymentStatus::PENDING) {
                    $rental->update([
                        'payment_status' => PaymentStatus::FAILED,
                    ]);
                    Log::warning("Rental {$rental->id} payment failed. Reason: " . ($payload['err_description'] ?? 'Unknown'));
                }
            }
        }

        return $this->success(null, 'Webhook processed successfully');
    }
}
