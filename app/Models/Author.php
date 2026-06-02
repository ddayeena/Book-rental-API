<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'name',
    'slug',
    'bio'
])]
class Author extends Model
{
    use HasUlids;
    protected $table = 'authors';

    public function scopeFilter(Builder $builder, QueryFilter $filter): Builder
    {
        return $filter->apply($builder);
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'author_book');
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::tags(['categories'])->flush();
        });

        static::deleted(function () {
            Cache::tags(['categories'])->flush();
        });
    }
}
