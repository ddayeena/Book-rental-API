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
        
        if (array_key_exists('late_fee', $data)) {
            $rental->late_fee = $data['late_fee'];
        }
        $rental->save();

        return $rental;
    }

    /**
     * Safely delete (soft delete) a rental order.
     *
     * @param Rental $rental
     * @return void
     * @throws Exception
     */
    public function deleteRental(Rental $rental): void
    {
        // Paid rentals should never be deleted to preserve financial records 
        if ($rental->payment_status === PaymentStatus::PAID) {
            throw new Exception(__('messages.cannot_delete_paid_rental'));
        }

        // Delete only if rental is in PENDING or CANCELLED status to prevent data loss of active rentals
        if (!in_array($rental->status, [RentalStatus::PENDING, RentalStatus::CANCELLED])) {
            throw new Exception(__('messages.cannot_delete_active_rental'));
        }

        DB::transaction(function () use ($rental) {
            // If the rental was pending, it means the book was reserved and not yet picked up. 
            // We need to return that reservation back to available stock.
            if ($rental->status === RentalStatus::PENDING) {
                $rental->book()->increment('available_copies');
            }

            $rental->delete();
        });
    }

    /**
     * Restore a soft-deleted rental.
     *
     * @param Rental $rental
     * @return Rental
     * @throws Exception
     */
    public function restoreRental(Rental $rental): Rental
    {
        // If rental was not soft-deleted, we should not restore it 
        if (!$rental->trashed()) {
            throw new Exception(__('messages.rental_not_trashed'));
        }

        return DB::transaction(function () use ($rental) {
            // When restoring, if it was a PENDING rental, we need to take the book back from the shelf
            if ($rental->status === RentalStatus::PENDING) {
                $book = $rental->book()->lockForUpdate()->first();

                // If rental was in the cart, but in the meantime all copies were rented out — block the restore action
                if ($book->available_copies <= 0) {
                    throw new Exception(__('messages.cannot_restore_no_copies'));
                }

                $book->decrement('available_copies');
            }

            $rental->restore();

            return $rental;
        });
    }

    /**
     * Issue the book to the client (Change status to ACTIVE).
     *
     * @param Rental $rental
     * @return Rental
     * @throws Exception
     */
    public function issueRental(Rental $rental, ?string $notes = null): Rental
    {
        // Book can only be issued if the rental is in PENDING status
        if ($rental->status !== RentalStatus::PENDING) {
            throw new Exception(__('messages.cannot_issue_rental_status'));
        }

        // If payment method is online but payment status is not PAID, we cannot issue the book
        if ($rental->payment_method === PaymentMethod::PAY_ONLINE && $rental->payment_status !== PaymentStatus::PAID) {
            throw new Exception(__('messages.cannot_issue_unpaid_rental'));
        }

        $updateData = ['status' => RentalStatus::ACTIVE];

        if ($notes) {
            $prefix = __('messages.note_prefix', [
                'date'   => now()->format('Y-m-d H:i'),
                'action' => __('messages.action_issue')
            ]);
            $updateData['notes'] = $rental->notes ? $rental->notes . "\n" . $prefix . $notes : $prefix . $notes;
        }

        $rental->update($updateData);

        return $rental;
    }

    /**
     * Process the return of the book (Calculate late fees and restock).
     *
     * @param Rental $rental
     * @return Rental
     * @throws Exception
     */
    public function returnRental(Rental $rental, ?string $notes = null): Rental
    {
        // Book can only be returned if the rental is currently ACTIVE
        if ($rental->status !== RentalStatus::ACTIVE) {
            throw new Exception(__('messages.cannot_return_inactive_rental'));
        }

        return DB::transaction(function () use ($rental, $notes) {
            $now = Carbon::now();
            $lateFee = 0;
            // Late fee
            if ($now->greaterThan($rental->end_date->endOfDay())) {

                $overdueDays = (int) $rental->end_date->startOfDay()->diffInDays($now->startOfDay());

                $multiplier = config('rental.penalty_multiplier');

                $penaltyRate = $rental->daily_price * $multiplier;
                $lateFee = $overdueDays * $penaltyRate;
            }

            $updateData = [
                'status'      => RentalStatus::COMPLETED,
                'returned_at' => $now,
                'late_fee'    => $lateFee > 0 ? $lateFee : null,
                'payment_status' => $rental->payment_method === PaymentMethod::PAY_ON_PICKUP 
                    ? PaymentStatus::PAID 
                    : $rental->payment_status
            ];

            if ($notes) {
                $prefix = __('messages.note_prefix', [
                    'date'   => now()->format('Y-m-d H:i'),
                    'action' => __('messages.action_return')
                ]);
                $updateData['notes'] = $rental->notes ? $rental->notes . "\n" . $prefix . $notes : $prefix . $notes;
            }

            $rental->update($updateData);
            $rental->book()->increment('available_copies');

            return $rental;
        });
    }
}
