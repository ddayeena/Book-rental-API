<?php

namespace App\Filters\Admin;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class RentalFilter extends QueryFilter
{
    /**
     * Filter by rental status
     */
    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    /**
     * Filter by payment status 
     */
    public function paymentStatus(string $value): Builder
    {
        return $this->builder->where('payment_status', $value);
    }

    /**
     * Filter by specific user.
     */
    public function userId(string $value): Builder
    {
        return $this->builder->where('user_id', $value);
    }

    /**
     * Filter by specific book.
     */
    public function bookId(string $value): Builder
    {
        return $this->builder->where('book_id', $value);
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

    /**
     * Sort rentals.
     */
    public function sort(string $value): Builder
    {
        return match ($value) {
            'oldest'        => $this->builder->orderBy('created_at', 'asc'),
            'deadline_asc'  => $this->builder->orderBy('end_date', 'asc'),  
            'deadline_desc' => $this->builder->orderBy('end_date', 'desc'),
            'fee_desc'      => $this->builder->orderBy('late_fee', 'desc'), 
            'price_desc'    => $this->builder->orderBy('total_price', 'desc'), 
            'start_desc'    => $this->builder->orderBy('start_date', 'desc'), 
            'returned_desc' => $this->builder->orderBy('returned_at', 'desc'),
            default         => $this->builder->orderBy('created_at', 'desc'), 
        };
    }
}