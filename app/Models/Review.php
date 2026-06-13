<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(
    'user_id',
    'book_id',
    'rating',
    'comment',
    'admin_reply'
)]
class Review extends Model
{
    use HasUlids;
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function scopeFilter(Builder $builder, QueryFilter $filter): Builder
    {
        return $filter->apply($builder);
    }
}
