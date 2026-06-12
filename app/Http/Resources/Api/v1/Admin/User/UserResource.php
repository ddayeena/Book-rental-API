<?php

namespace App\Http\Resources\Api\v1\Admin\User;

use App\Http\Resources\Api\v1\Admin\Rental\RentalListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email_verified_at' => $this->email_verified_at,
            'statistics' => [
                'total_rentals'   => $this->total_rentals_count ?? 0,
                'active_rentals'  => $this->active_rentals_count ?? 0,
                'overdue_rentals' => $this->overdue_rentals_count ?? 0,
                'total_debt'      => (float) ($this->current_penalties_sum ?? 0),
            ],

            'recent_rentals'    => RentalListResource::collection($this->whenLoaded('rentals')),
            'created_at'        => $this->created_at,
            'is_deleted'        => $this->trashed(), 
            'deleted_at'        => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
