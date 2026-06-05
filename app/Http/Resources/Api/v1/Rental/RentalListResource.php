<?php

namespace App\Http\Resources\Api\v1\Rental;

use App\Http\Resources\Api\v1\Book\BookListResource;
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
            
            'book'         => new BookListResource($this->whenLoaded('book')),
            
            'start_date'   => $this->start_date->format('Y-m-d'),
            'end_date'     => $this->end_date->format('Y-m-d'),
            
            'total_price'  => (float) $this->total_price,
            
            'status'       => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}
