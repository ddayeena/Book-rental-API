<?php

namespace App\Filters; 

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
}