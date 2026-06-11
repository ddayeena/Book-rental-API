<?php

namespace App\Http\Resources\Api\v1\Admin\Rental;

use App\Http\Resources\Api\v1\Admin\Books\BookListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            
            'start_date'   => $this->start_date->format('Y-m-d'),
            'end_date'     => $this->end_date->format('Y-m-d'),
            'returned_at'  => $this->returned_at ? $this->returned_at->format('Y-m-d H:i:s') : null,
            
            'daily_price'  => (float) $this->daily_price,
            'late_fee'     => (float) $this->late_fee,
            'total_price'  => (float) $this->total_price,
            
            'status'       => $this->status->value,
            'status_label' => $this->status->label(),
            
            'notes'        => $this->notes,

            'book'         => new BookListResource($this->whenLoaded('book')),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id'    => $this->user->id,
                    'first_name'  => $this->user->first_name,
                    'last_name'  => $this->user->last_name,
                    'email' => $this->user->email,
                ];
            }),

            'payment' => [
                'method'               => $this->payment_method->value,
                'method_label'         => $this->payment_method->label(),
                'status'               => $this->payment_status->value,
                'status_label'         => $this->payment_status->label(),
                'transaction_id'       => $this->transaction_id, 
            ],

            'created_at'   => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'   => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
