<?php

namespace App\Http\Resources\Api\v1\Book;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'authors'          => $this->authors->pluck('name'),
            'categories'       => $this->categories->pluck('name'),
            'language'         => $this->language?->label(),
            'pages_count'      => $this->pages_count,
            'publication_year' => $this->publication_year,
            'isbn'             => $this->isbn,
            'cover_image_url'  => $this->cover_image_url,
            'daily_price'      => $this->daily_price,
            'prce'             => $this->price,
            'is_available'     => $this->available_copies > 0,
            'rating'           => $this->reviews_avg_rating ? round($this->reviews_avg_rating, 1) : 0,
            'reviews_count'    => $this->reviews_count ?? 0,
        ];
    }
}
