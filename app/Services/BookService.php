<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookService
{
    public function __construct(
        protected ImageService $imageService
    ) {}

    public function createBook(array $data, ?UploadedFile $coverImage = null): Book
    {
        return DB::transaction(function () use ($data, $coverImage) {

            if ($coverImage) {
                $data['cover_image'] = $this->imageService->upload($coverImage, 'books/covers');
            }

            $book = Book::create($data);

            if (isset($data['authors'])) {
                $book->authors()->attach($data['authors']);
            }
            if (isset($data['categories'])) {
                $book->categories()->attach($data['categories']);
            }

            $book->load(['authors', 'categories']);

            return $book;
        });
    }

    public function updateBook(Book $book, array $data, ?UploadedFile $coverImage = null): Book
    {
        return DB::transaction(function () use ($book, $data, $coverImage) {

            // Update cover image if a new one is provided or if the cover_image field is explicitly set to null
            if ($coverImage) {
                if ($book->cover_image) {
                    $this->imageService->delete($book->cover_image);
                }
                $data['cover_image'] = $this->imageService->upload($coverImage, 'books/covers');
            } elseif (array_key_exists('cover_image', $data) && is_null($data['cover_image'])) {
                if ($book->cover_image) {
                    $this->imageService->delete($book->cover_image);
                }
                $data['cover_image'] = null;
            }

            $book->update($data);

            if (isset($data['authors'])) {
                $book->authors()->sync($data['authors']);
            }

            if (isset($data['categories'])) {
                $book->categories()->sync($data['categories']);
            }

            $book->load(['authors', 'categories']);

            return $book;
        });
    }

    public function deleteBook(Book $book): void
    {
        DB::transaction(function () use ($book) {
            $book->authors()->detach();
            $book->categories()->detach();

            if ($book->cover_image) {
                $this->imageService->delete($book->cover_image);
            }

            $book->delete();
        });
    }

    /**
     * Delete multiple books and return a performance report.
     */
    public function bulkDeleteBooks(array $ids): array
    {
        $report = [
            'deleted_ids' => [],
            'failed_ids' => []
        ];

        // Fetch all books matching the provided IDs
        $books = Book::whereIn('id', $ids)->get();

        foreach ($books as $book) {
            try {
                // Reuse the single delete method to clean DB relations and Cloudflare storage
                $this->deleteBook($book);
                $report['deleted_ids'][] = $book->id;
            } catch (\Exception $e) {
                $report['failed_ids'][] = [
                    'id' => $book->id,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return $report;
    }

    /**
     * Update the active status for multiple books.
     */
    public function bulkUpdateActiveStatus(array $ids, bool $isActive): void
    {
        Book::whereIn('id', $ids)->update([
            'is_active' => $isActive
        ]);
    }

    /**
     * Update the rental price for multiple books.
     */
    public function bulkUpdatePrice(array $ids, string $type, float $value): void
    {
        $query = Book::whereIn('id', $ids);

        match ($type) {
            'percentage' => $query->update([
                // e.g., daily_price * 1.10 (for +10%) or daily_price * 0.85 (for -15%)
                'daily_price' => DB::raw("daily_price * (1 + ({$value} / 100))")
            ]),

            'flat' => $query->update([
                // e.g., daily_price + 50 or daily_price - 20
                'daily_price' => DB::raw("daily_price + {$value}")
            ]),

            'fixed' => $query->update([
                // Just set a completely new price
                'daily_price' => $value
            ]),
        };

        // Protection: Ensure price never drops below zero due to discounts
        Book::whereIn('id', $ids)->where('daily_price', '<', 0)->update(['daily_price' => 0]);
    }

    /**
     * Generate a full streamed CSV response for exporting books.
     */
    public function exportBooksToCsv(array $ids): StreamedResponse
    {
        return new StreamedResponse(function () use ($ids) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility 
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Define Full CSV Headings 
            fputcsv($handle, [
                'ID',
                'Title',
                'Authors',
                'Categories',
                'Language',
                'Pages Count',
                'Publication Year',
                'ISBN',
                'Cover Image URL', 
                'Daily Price',
                'Available Copies',
                'Total Copies',
                'Status',
                'Description' 
            ], ';');

            // Stream records in chunks to prevent memory exhaustion
            Book::with(['authors', 'categories'])
                ->whereIn('id', $ids)
                ->chunk(100, function ($books) use ($handle) {
                    foreach ($books as $book) {
                        fputcsv($handle, [
                            $book->id,
                            $book->title,
                            $book->authors->pluck('name')->implode(', '),
                            $book->categories->pluck('name')->implode(', '),
                            $book->language,
                            $book->pages_count,
                            $book->publication_year,
                            $book->isbn,
                            $book->cover_image_url,
                            $book->daily_price,
                            $book->available_copies,
                            $book->total_copies,
                            $book->is_active ? '+' : '-',
                            $book->description,
                        ], ';');
                    }
                });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="books_full_export_' . now()->format('Y_m_d_His') . '.csv"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
