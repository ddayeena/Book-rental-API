<?php

namespace App\Http\Resources\Api\v1\Admin\Books;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'id'               => $this->id,
            'title'            => $this->title,
            'cover_image_url'  => $this->cover_image_url,
            'total_copies'     => $this->total_copies,
            'available_copies' => $this->available_copies,
            'daily_price'      => (float) $this->daily_price,
            'is_active'        => $this->is_active,
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(fn($category) => [
                    'id'   => $category->id,
                    'name' => $category->name,
                ]);
            }),

            'authors' => $this->whenLoaded('authors', function () {
                return $this->authors->map(fn($author) => [
                    'id'   => $author->id,
                    'name' => $author->name,
                ]);
            }),
        ];
    }
}
