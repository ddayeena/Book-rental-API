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
use Illuminate\Support\Facades\Log;

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
                'payment_method' => $data['payment_method'],
                'status'         => $data['status'] ?? RentalStatus::PENDING,
                'payment_status' => $data['payment_status'] ?? PaymentStatus::PENDING,
                'notes'          => $data['notes'] ?? null,
            ]);

            $book->decrement('available_copies');

            if ($rental->payment_method === PaymentMethod::PAY_ONLINE) {
                $checkoutUrl = $this->paymentService->generateCheckoutUrl($rental);

                $rental->checkout_url = $checkoutUrl;
            }
            return $rental;
        });
    }

    /**
     * Cancel a pending rental order.
     *
     * @param Rental $rental
     * @return Rental
     * @throws \Exception
     */
    public function cancelRental(Rental $rental): Rental
    {
        if ($rental->status !== RentalStatus::PENDING) {
            throw new \Exception(__('messages.not_canceled'));
        }

        $updateData = [
            'status' => RentalStatus::CANCELLED,
        ];

        if ($rental->payment_status === PaymentStatus::PAID) {
            $updateData['payment_status'] = PaymentStatus::REFUNDED;

            Log::warning("Rental {$rental->id} was cancelled by user AFTER payment. Manual refund required.");
        }

        $rental->update($updateData);

        return $rental;
    }

    /**
     * Update basic details of an existing rental.
     *
     * @param Rental $rental
     * @param array $data Validated request data
     * @return Rental
     * @throws Exception
     */
    public function updateRental(Rental $rental, array $data): Rental
    {
        // Updatе rental dates and recalculate price if either date is changed
        if (isset($data['start_date']) || isset($data['end_date'])) {
            if (in_array($rental->status, [RentalStatus::COMPLETED, RentalStatus::CANCELLED])) {
                throw new Exception(__('messages.cannot_update_closed_rental'));
            }

            // Cannot change dates (and price) if already paid
            if ($rental->payment_status === PaymentStatus::PAID) {
                throw new Exception(__('messages.cannot_change_dates_for_paid')); 
            }

            $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : $rental->start_date;
            $endDate = isset($data['end_date']) ? Carbon::parse($data['end_date']) : $rental->end_date;

            $days = $startDate->diffInDays($endDate);
            $days = $days === 0 ? 1 : $days;

            $rental->start_date = $startDate;
            $rental->end_date = $endDate;
            $rental->total_price = $days * $rental->daily_price;
        }

        // Update payment_method
        if (isset($data['payment_method']) && $data['payment_method'] !== $rental->payment_method->value) {

            // Cannot change payment method if already paid or refunded
            if ($rental->payment_status !== PaymentStatus::PENDING) {
                throw new Exception(__('messages.cannot_change_payment_method_for_paid'));
            }

            $rental->payment_method = $data['payment_method'];

            // If switched to online — generate checkout URL
            if ($rental->payment_method === PaymentMethod::PAY_ONLINE) {
                $rental->checkout_url = $this->paymentService->generateCheckoutUrl($rental);
            
            }
            // If switched to cash — remove checkout URL
            else {
                $rental->checkout_url = null;
            }
        }

        if (array_key_exists('notes', $data)) {
            $rental->notes = $data['notes'];
        }
        if (array_key_exists('late_fee', $data)) {
            $rental->late_fee = $data['late_fee'];
        }
        $rental->save();

        return $rental;
    }
}
