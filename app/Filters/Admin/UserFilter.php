<?php

namespace App\Filters\Admin;

use App\Filters\QueryFilter; 

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
}