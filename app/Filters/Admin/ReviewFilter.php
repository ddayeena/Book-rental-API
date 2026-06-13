<?php

namespace App\Filters\Admin;

use App\Filters\QueryFilter;

class ReviewFilter extends QueryFilter
{
    /**
     * Filter by book ID 
     */
    public function bookId(string $id)
    {
        return $this->builder->where('book_id', $id);
    }

    /**
     * Filter by rating 
     */
    public function rating(int $rating)
    {
        return $this->builder->where('rating', $rating);
    }

    /**
     * Filter by user ID
     */
    public function user_id(int $id)
    {
        return $this->builder->where('user_id', $id);
    }

    /**
     * Sort reviews by specified criteria
     */
    public function sort(string $value)
    {
        return match ($value) {
            'oldest'      => $this->builder->orderBy('created_at', 'asc'),
            'rating_asc'  => $this->builder->orderBy('rating', 'asc'),
            'rating_desc' => $this->builder->orderBy('rating', 'desc'),
            default       => $this->builder
        };
    }
}