<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Filters\Admin\BookFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Admin\Books\BulkDeleteBooksRequest;
use App\Http\Requests\Api\v1\Admin\Books\BulkExportBooksRequest;
use App\Http\Requests\Api\v1\Admin\Books\BulkUpdateActiveBooksRequest;
use App\Http\Requests\Api\v1\Admin\Books\BulkUpdatePriceBooksRequest;
use App\Http\Requests\Api\v1\Admin\Books\StoreBookRequest;
use App\Http\Requests\Api\v1\Admin\Books\UpdateBookRequest;
use App\Http\Resources\Api\v1\Admin\Books\BookListResource;
use App\Http\Resources\Api\v1\Admin\Books\BookResource;
use App\Models\Book;
use App\Services\BookService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookController extends Controller
{
    /**
     * Inject BookService globally for this controller.
     */
    public function __construct(
        protected BookService $bookService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(BookFilter $filter)
    {
        $books = Book::with(['authors', 'categories'])->filter($filter)->apiPaginate();
        return $this->respondWithPagination(BookListResource::collection($books));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        try {
            $book = $this->bookService->createBook(
                $request->validated(), 
                $request->file('cover_image')
            );
            
            return $this->success(new BookResource($book), __('messages.created'), 201);
            
        } catch (\Exception $e) {
            return $this->error(__('messages.creation_failed'), 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load(['authors', 'categories']);
        return $this->success(new BookResource($book), '', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        try {
            $updatedBook = $this->bookService->updateBook(
                $book,
                $request->validated(),
                $request->file('cover_image')
            );

            return $this->success(new BookResource($updatedBook), __('messages.updated'), 200);
            
        } catch (\Exception $e) {
            return $this->error(__('messages.update_failed'), 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        try {
            $this->bookService->deleteBook($book);
            
            return $this->success(null, __('messages.deleted'), 200);
            
        } catch (\Exception $e) {
            return $this->error(__('messages.deletion_failed'), 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk delete resources.
     */
    public function bulkDestroy(BulkDeleteBooksRequest $request)
    {
        try {
            $report = $this->bookService->bulkDeleteBooks($request->input('ids'));
            
            return $this->success($report, __('messages.deleted'), 200);
            
        } catch (\Exception $e) {
            return $this->error(__('messages.deletion_failed'), 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk toggle active status.
     */
    public function bulkToggleActive(BulkUpdateActiveBooksRequest $request)
    {
        try {
            $this->bookService->bulkUpdateActiveStatus(
                $request->input('ids'),
                $request->boolean('is_active')
            );
            
            return $this->success(null, __('messages.updated'), 200);
            
        } catch (\Exception $e) {
            return $this->error(__('messages.update_failed'), 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk update rental price.
     */
    public function bulkUpdatePrice(BulkUpdatePriceBooksRequest $request)
    {
        try {
            $this->bookService->bulkUpdatePrice(
                $request->input('ids'),
                $request->input('type'),
                (float) $request->input('value')
            );
            
            return $this->success(null, __('messages.updated'), 200);
            
        } catch (\Exception $e) {
            return $this->error(__('messages.update_failed'), 500, ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Export multiple specified resources as a CSV file.
     */
    public function bulkExport(BulkExportBooksRequest $request): StreamedResponse
    {
        return $this->bookService->exportBooksToCsv($request->input('ids'));
    }
}