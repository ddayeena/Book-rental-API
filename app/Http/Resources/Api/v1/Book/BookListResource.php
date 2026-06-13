<?php

namespace App\Http\Resources\Api\v1\Book;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'authors'         => $this->authors->pluck('name')->implode(', '),
            'categories'      => $this->categories->pluck('name')->implode(', '),
            'cover_image_url' => $this->cover_image_url,
            'daily_price'     => $this->daily_price,
            'is_available'    => $this->available_copies > 0,
            'rating'          => $this->reviews_avg_rating ? round($this->reviews_avg_rating, 1) : 0,
            'reviews_count'   => $this->reviews_count ?? 0,
        ];
    }
}
