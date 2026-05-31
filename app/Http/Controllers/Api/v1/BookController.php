<?php

namespace App\Http\Controllers\Api\v1;

use App\Filters\BookFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\Book\BookListResource;
use App\Http\Resources\Api\v1\Book\BookResource;
use App\Models\Book;
use App\Services\BookService;

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
        $books = $this->bookService->getPublicBooks($filter)->apiPaginate();
        return $this->respondWithPagination(BookListResource::collection($books));
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