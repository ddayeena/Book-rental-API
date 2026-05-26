<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class AuthorFilter extends QueryFilter
{
    public function search(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function sort(string $value): Builder
    {
        return match ($value) {
            'name_asc'  => $this->builder->orderBy('name', 'asc'),
            'name_desc' => $this->builder->orderBy('name', 'desc'),
            'oldest'    => $this->builder->orderBy('created_at', 'asc'),
            default     => $this->builder->orderBy('created_at', 'desc'), 
        };
    }
}