<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\Book\BookListResource;
use App\Http\Resources\Api\v1\CategoryResource;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        // Top 15 latest books with authors and categories
        $latestBooks = Book::with(['authors', 'categories']) 
            ->latest() 
            ->take(15)
            ->get();

        // Top 5 rated books with authors and categories, sorted by average rating
        $topRatedBooks = Book::with(['authors', 'categories'])
            ->withAvg('reviews', 'rating') 
            ->having('reviews_avg_rating', '>', 0) 
            ->orderByDesc('reviews_avg_rating')
            ->take(5)
            ->get();

        // List of categories with the count of books in each category, only those with at least one book
        $categories = Category::withCount('books')
            ->having('books_count', '>', 0) 
            ->orderByDesc('books_count') 
            ->get();

        return $this->success([
            'latest_books'    => BookListResource::collection($latestBooks),
            'top_rated_books' => BookListResource::collection($topRatedBooks),
            'categories'      => $categories,
        ]);
    }
}
