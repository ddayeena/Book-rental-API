<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Models\Book;
use App\Models\Rental;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class RentalService
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Create a new rental order.
     *
     * @param User $user The client making the rental request.
     * @param array $data Validated request data (book_id, start_date, end_date).
     * @return Rental
     * @throws Exception
     */
    public function createRental(User $user, array $data): Rental
    {
        return DB::transaction(function () use ($user, $data) {
            
            // Find the requested book and lock the row to prevent race conditions
            $book = Book::where('id', $data['book_id'])->lockForUpdate()->firstOrFail();

            if ($book->available_copies <= 0) {
                throw new Exception(__('messages.book_unavailable'));
            }

            // Calculate the duration of the rental.
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
            
            // If the book is rented and returned on the same day, count it as a 1-day rental.
            $days = $startDate->diffInDays($endDate);
            $days = $days === 0 ? 1 : $days;

            // Calculate financial metrics.
            $dailyPrice = $book->daily_price ?? 0; 
            $totalPrice = $days * $dailyPrice;

            $rental = Rental::create([
                'user_id'        => $user->id,
                'book_id'        => $book->id,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'daily_price'    => $dailyPrice,
                'total_price'    => $totalPrice,
                'notes'          => $data['notes'] ?? null,
                'payment_method' => $data['payment_method'],
                'status'         => RentalStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
            ]);

            $book->decrement('available_copies');

            if ($rental->payment_method === PaymentMethod::PAY_ONLINE) {
                $checkoutUrl = $this->paymentService->generateCheckoutUrl($rental);
                
                $rental->checkout_url = $checkoutUrl;
            }
            return $rental;
        });
    }
}