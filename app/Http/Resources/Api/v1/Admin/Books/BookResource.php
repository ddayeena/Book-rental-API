<?php

namespace App\Http\Resources\Api\v1\Admin\Books;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'cover_image_url'  => $this->cover_image ? Storage::disk('s3')->url($this->cover_image) : null,
            'language'         => $this->language,
            'pages_count'      => $this->pages_count,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'publication_year' => $this->publication_year,
            'isbn'             => $this->isbn,
            'total_copies'     => $this->total_copies,
            'available_copies' => $this->available_copies,
            'daily_price'      => $this->daily_price,
            'is_active'        => $this->is_active,
            'categories'       => CategoryResource::collection($this->whenLoaded('categories')),
            'authors'          => AuthorResource::collection($this->whenLoaded('authors')),
        ];
    }
}
