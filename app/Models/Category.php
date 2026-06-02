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
    'description'
])]
class Category extends Model
{
    use HasUlids;
    protected $table = 'categories';

    public function scopeFilter(Builder $builder, QueryFilter $filter): Builder
    {
        return $filter->apply($builder);
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_category');
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::tags(['authors'])->flush();
        });

        static::deleted(function () {
            Cache::tags(['authors'])->flush();
        });
    }
}
