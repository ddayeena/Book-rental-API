<?php

namespace App\Http\Resources\Api\v1\Review;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'book_id'    => $this->book_id,
            'rating'     => $this->rating,
            'comment'    => $this->comment,
            
            'user'       => $this->whenLoaded('user', function () {
                return [
                    'id'   => $this->user->id,
                    'name' => $this->user->first_name . ' ' . $this->user->last_name,
                ];
            }),

            'is_edited'  => $this->created_at->ne($this->updated_at),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
