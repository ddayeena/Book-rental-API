<?php

namespace App\Filters\Admin;

use App\Filters\QueryFilter;

class BookFilter extends QueryFilter
{
    /**
     * Filter by title (partial match).
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
     * Filter by ISBN (partial match).
     */
    public function isbn(string $value): void
    {
        $this->builder->where('isbn', 'like', "%{$value}%");
    }

    /**
     * Filter by active status.
     */
    public function isActive(string $value): void
    {
        $this->builder->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * Filter by publication year.
     */
    public function publicationYear(string $value): void
    {
        $this->builder->where('publication_year', $value);
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
     * Filter by category ID.
     */
    public function categoryId(string $value): void
    {
        $this->builder->whereHas('categories', function ($query) use ($value) {
            $query->where('categories.id', $value);
        });
    }

    /**
     * Filter by author ID.
     */
    public function authorId(string $value): void
    {
        $this->builder->whereHas('authors', function ($query) use ($value) {
            $query->where('authors.id', $value);
        });
    }

    /**
     * Sort the results.
     */
    public function sort(string $value): void
    {
        match ($value) {
            'price_asc' => $this->builder->orderBy('daily_price', 'asc'),
            'price_desc' => $this->builder->orderBy('daily_price', 'desc'),
            'title_asc' => $this->builder->orderBy('title', 'asc'),
            'title_desc' => $this->builder->orderBy('title', 'desc'),
            'oldest' => $this->builder->orderBy('created_at', 'asc'),
            default => $this->builder->orderBy('created_at', 'desc'),
        };
    }
}
