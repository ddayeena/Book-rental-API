<?php

namespace App\Models;

use App\Enums\BookLanguage;
use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

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
    'price',
    'is_active'
])]
class Book extends Model
{
    use HasUlids, SoftDeletes;
    protected $table = 'books';

    protected $casts = [
        'language' => BookLanguage::class, 
        'is_active' => 'boolean',
    ];


    protected static function booted(): void
    {
        // 
        $clearCatalogCache = function () {
            Cache::tags(['public_catalog'])->flush(); 
        };

        static::saved($clearCatalogCache);
        static::deleted($clearCatalogCache);
        static::restored($clearCatalogCache);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    protected function coverImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->cover_image ? Storage::disk('s3')->url($this->cover_image) : null
        );
    }
}
