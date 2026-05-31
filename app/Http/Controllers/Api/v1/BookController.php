<?php

namespace App\Http\Controllers\Api\v1;

use App\Filters\BookFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\Book\BookListResource;
use App\Http\Resources\Api\v1\Book\BookResource;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    public function __construct(
        protected BookService $bookService
    ) {}

    /**
     * Display a listing of active books.
     */
    public function index(BookFilter $filter)
    {
        $cacheKey = 'client_books_' . md5(request()->fullUrl());

        $jsonString = Cache::tags(['public_catalog'])
            ->remember($cacheKey, now()->addMinutes(15), function () use ($filter) {

                $books = $this->bookService->getPublicBooks($filter)->apiPaginate();
                $response = $this->respondWithPagination(BookListResource::collection($books));
                return $response->getContent();
            });

        return response($jsonString, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Display the specified active book.
     */
    public function show(string $id)
    {
        $book = $this->bookService->getPublicBookById($id);
        return $this->success(new BookResource($book));
    }

    /**
     * Display related books for a specific book.
     */
    public function related(Book $book)
    {
        if (!$book->is_active) {
            abort(404, 'Book not found.');
        }

        $relatedBooks = $this->bookService->getRelatedBooks($book);

        return $this->success(BookListResource::collection($relatedBooks));
    }
}
