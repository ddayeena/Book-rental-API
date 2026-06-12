<?php

namespace App\Http\Resources\Api\v1\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'email'             => $this->email,
            'phone_number'      => $this->phone_number,
            'role'              => $this->role,
            'is_blocked'        => $this->is_blocked,
            'rentals_count'     => $this->rentals()->count(),
            'created_at'        => $this->created_at,  
        ];
    }
}
