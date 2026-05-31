<?php

namespace App\Filters;

use App\Filters\QueryFilter;

class BookFilter extends QueryFilter
{
    /**
     * Filter by title.
     */
    public function title(string $value): void
    {
        $this->builder->where('title', 'like', "%{$value}%");
    }

    /**
     * Filter by language.
     */
    public function language(string $value): void
    {
        $this->builder->where('language', $value);
    }

    /**
     * Filter by stock availability.
     */
    public function inStock(string $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $this->builder->where('available_copies', '>', 0);
        } else {
            $this->builder->where('available_copies', 0);
        }
    }
    
    /**
     * Filter by publication year.
     */
    public function publicationYear(string $value): void
    {
        $this->builder->where('publication_year', $value);
    }

    /**
     * Filter by minimum daily rental price.
     */
    public function priceMin(string $value): void
    {
        $this->builder->where('daily_price', '>=', (float) $value);
    }

    /**
     * Filter by maximum daily rental price.
     */
    public function priceMax(string $value): void
    {
        $this->builder->where('daily_price', '<=', (float) $value);
    }

    /**
     * Filter by category Slug 
     */
    public function category(string $value): void
    {
        $this->builder->whereHas('categories', function ($query) use ($value) {
            $query->where('categories.slug', $value);
        });
    }
    /**
     * Filter by author Slug
     */
    public function author(string $value): void
    {
        $this->builder->whereHas('authors', function ($query) use ($value) {
            $query->where('authors.slug', $value);
        });
    }

    /**
     * Sort the results.
     * Default sorting is newest first.
     */
    public function sort(string $value): void
    {
        match ($value) {
            'price_asc'  => $this->builder->orderBy('daily_price', 'asc'),
            'price_desc' => $this->builder->orderBy('daily_price', 'desc'),
            'title_asc'  => $this->builder->orderBy('title', 'asc'),
            'title_desc' => $this->builder->orderBy('title', 'desc'),
            'oldest'     => $this->builder->orderBy('created_at', 'asc'),
            default      => $this->builder->orderBy('created_at', 'desc'),
        };
    }
}
