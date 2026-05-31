<?php

namespace App\Services;

use App\Filters\Admin\BookFilter;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

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
                            $book->is_active ? 'Active' : 'Not Active',
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

    /**
     * Import books from a CSV file (insert new or update existing).
     */
    public function importBooksFromCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        // Skip UTF-8 BOM if it exists
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Skip the header row
        fgetcsv($handle, 0, ';');

        $importedCount = 0;
        $skippedCount = 0;

        // Wrap the whole process in a transaction for safety
        DB::transaction(function () use ($handle, &$importedCount, &$skippedCount) {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                // Check: if the row is empty or if the book title is missing, skip it
                if (empty($row[1])) {
                    $skippedCount++;
                    continue;
                }

                // Mapping columns according to our export:
                // 0:ID, 1:Title, 2:Authors, 3:Categories, 4:Language, 5:Pages Count,
                // 6:Publication Year, 7:ISBN, 8:Cover URL (skip), 9:Price, 10:Available, 11:Total, 12:Status, 13:Description

                $bookId = !empty($row[0]) ? $row[0] : (string) Str::ulid();

                $book = Book::updateOrCreate(
                    ['id' => $bookId],
                    [
                        'title'            => $row[1],
                        'language'         => $row[4] ?? 'uk',
                        'pages_count'      => (int) ($row[5] ?? 0),
                        'publication_year' => !empty($row[6]) ? (int) $row[6] : null,
                        'isbn'             => $row[7] ?? null,
                        'daily_price'      => (float) ($row[9] ?? 0.0),
                        'available_copies' => (int) ($row[10] ?? 0),
                        'total_copies'     => (int) ($row[11] ?? 0),
                        'is_active'        => isset($row[12]) ? ($row[12] === 'Active') : true,
                        'description'      => $row[13] ?? null,
                    ]
                );

                // Sync authors (comma-separated in the file)
                if (!empty($row[2])) {
                    $authorNames = explode(',', $row[2]);
                    $authorIds = [];
                    foreach ($authorNames as $name) {
                        $author = Author::firstOrCreate(['name' => trim($name)]);
                        $authorIds[] = $author->id;
                    }
                    $book->authors()->sync($authorIds);
                }

                // Sync categories (comma-separated in the file)
                if (!empty($row[3])) {
                    $categoryNames = explode(',', $row[3]);
                    $categoryIds = [];
                    foreach ($categoryNames as $name) {
                        $category = Category::firstOrCreate(['name' => trim($name)]);
                        $categoryIds[] = $category->id;
                    }
                    $book->categories()->sync($categoryIds);
                }

                $importedCount++;
            }
        });

        fclose($handle);

        return [
            'imported' => $importedCount,
            'skipped'  => $skippedCount,
        ];
    }

    /**
     * Get only soft-deleted books with pagination.
     */
    public function getTrashedBooks(BookFilter $filter)
    {
        return Book::onlyTrashed()->with(['authors', 'categories'])->filter($filter);
    }

    /**
     * Bulk restore soft-deleted books.
     */
    public function bulkRestoreBooks(array $ids): void
    {
        Book::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Bulk permanently delete books from database.
     */
    public function bulkForceDeleteBooks(array $ids): void
    {
        Book::onlyTrashed()->whereIn('id', $ids)->forceDelete();
    }

    /**
     * Get active books for public catalog with filtering and pagination.
     */
    public function getPublicBooks($filter)
    {
        return Book::active() 
            ->with(['authors', 'categories'])
            ->filter($filter);
    }

    /**
     * Get active book by ID for public view.
     */
    public function getPublicBookById(string $id): Book
    {
        return Book::active() 
            ->with(['authors', 'categories'])
            ->findOrFail($id);
    }

    /**
     * Get related active books based on shared categories.
     */
    public function getRelatedBooks(Book $book, int $limit = 4)
    {
        return Book::active()
            // Exclude the current book from recommendations to avoid recommending itself
            ->where('id', '!=', $book->id)
            // Find books that share at least one category with the current book
            ->whereHas('categories', function ($query) use ($book) {
                $query->whereIn('categories.id', $book->categories->pluck('id'));
            })
            ->with(['authors', 'categories'])
            // Randomize the order of related books to provide variety in recommendations
            ->inRandomOrder() 
            ->limit($limit)
            ->get();
    }
}
