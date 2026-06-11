<?php

namespace App\Http\Resources\Api\v1\Rental;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Resources\Api\v1\Book\BookResource;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class RentalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'book'         => new BookResource($this->whenLoaded('book')),

            // Format dates to be strictly consistent for the frontend
            'start_date'   => $this->start_date->format('Y-m-d'),
            'end_date'     => $this->end_date->format('Y-m-d'),
            'returned_at'  => $this->returned_at ? $this->returned_at->format('Y-m-d H:i:s') : null,

            'daily_price'  => (float) $this->daily_price,
            'total_price'  => (float) $this->total_price,
            'late_fee'     => $this->late_fee ? (float) $this->late_fee : null,

            'status'       => $this->status?->value,
            'status_label' => $this->status?->label(),

            // Payment details
            'payment_method'       => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'payment_status'       => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),

            // If payment is online and pending, generate payment data on the fly
            'payment_data' => $this->when(
                $this->payment_method->value === PaymentMethod::PAY_ONLINE->value
                    && $this->payment_status->value === PaymentStatus::PENDING->value,
                function () {
                    return app(PaymentService::class)->generateCheckoutUrl($this->resource);
                }
            ),

            'checkout_url'         => $this->checkout_url ?? null,
            'created_at'   => $this->created_at->toIso8601String(),
        ];
    }
}
