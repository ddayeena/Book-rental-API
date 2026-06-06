<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class RentalFilter extends QueryFilter
{
    /**
     * Filter by rental status (e.g., active, pending, completed).
     */
    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /**
     * Sort rentals.
     */
    public function sort(string $value): Builder
    {
        return match ($value) {
            'oldest'        => $this->builder->orderBy('created_at', 'asc'),
            'deadline_asc'  => $this->builder->orderBy('end_date', 'asc'),  
            'deadline_desc' => $this->builder->orderBy('end_date', 'desc'),
            default         => $this->builder->orderBy('created_at', 'desc'), 
        };
    }
}