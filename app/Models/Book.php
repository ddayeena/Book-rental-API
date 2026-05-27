<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'cover_image',
    'language',
    'pages_count',
    'slug',
    'description',
    'publication_year',
    'isbn',
    'total_copies',
    'available_copies',
    'daily_price',
    'is_active'
])]
class Book extends Model
{
    use HasUlids;
    protected $table = 'books';

    public function scopeFilter(Builder $builder, QueryFilter $filter): Builder
    {
        return $filter->apply($builder);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_category');
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'author_book');
    }
}
