<?php

namespace App\Filters\Admin;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class UserFilter extends QueryFilter
{
    /**
     * Global search across first_name, last_name, email, and phone_number.
     * 
     */
    public function search(string $value): void
    {
        $this->builder->where(function ($query) use ($value) {
            $query->where('first_name', 'like', "%{$value}%")
                ->orWhere('last_name', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%")
                ->orWhere('phone_number', 'like', "%{$value}%");
        });
    }

    /**
     * Filter by user role 
     */
    public function role(string $value): void
    {
        $this->builder->where('role', $value);
    }

    /**
     * Filter by blocked status.
     */
    public function isBlocked(string $value): void
    {
        $isBlocked = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        $this->builder->where('is_blocked', $isBlocked);
    }

    /**
     * Filter by trashed (deleted) status.
     * Accessible values: 'with' (all), 'only' (just deleted)
     */
    public function trashed(string $value): Builder
    {
        return match ($value) {
            'with'  => $this->builder->withTrashed(),
            'only'  => $this->builder->onlyTrashed(),
            default => $this->builder,
        };
    }
}
