<?php

namespace App\Http\Resources\Api\v1\Admin\Rental;

use App\Http\Resources\Api\v1\Admin\Books\BookListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalListResource extends JsonResource
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
            
            'book_title'   => $this->whenLoaded('book', fn() => $this->book->title),
            'user_first_name'   => $this->whenLoaded('user', fn() => $this->user->first_name),
            'user_last_name'    => $this->whenLoaded('user', fn() => $this->user->last_name),
            
            'start_date'   => $this->start_date->format('Y-m-d'),
            'end_date'     => $this->end_date->format('Y-m-d'),
            'total_price'  => (float) $this->total_price,
            
            'status'       => $this->status->value,
            'status_label' => $this->status->label(),

            'payment_status'       => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            
            'created_at'   => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
