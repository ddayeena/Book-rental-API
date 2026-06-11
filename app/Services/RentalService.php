<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Models\Book;
use App\Models\Rental;
use App\Models\User;
use App\Notifications\RentalCancelledNotification;
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
        // Check if the user has any unpaid late fees from previous rentals. 
        // If yes, block new rentals until they are settled.
        $hasDebts = Rental::where('user_id', $user->id)
            ->where('late_fee', '>', 0)
            ->where('payment_status', '!=', PaymentStatus::PAID)
            ->exists();

        if ($hasDebts) {
            throw new Exception(__('messages.user_has_unpaid_debts'));
        }

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
     * @param string|null $notes Optional cancellation reason or notes to be saved with the rental record.
     * @return Rental
     * @throws \Exception
     */
    public function cancelRental(Rental $rental, ?string $notes = null): Rental
    {
        if ($rental->status !== RentalStatus::PENDING) {
            throw new \Exception(__('messages.not_canceled'));
        }

        $updatedRental = DB::transaction(function () use ($rental, $notes) {
            $book = $rental->book()->lockForUpdate()->first();

            $updateData = [
                'status' => RentalStatus::CANCELLED,
            ];

            if ($rental->payment_status === PaymentStatus::PAID) {
                $updateData['payment_status'] = PaymentStatus::REFUNDED;
                Log::warning("Rental {$rental->id} was cancelled by user AFTER payment. Manual refund required.");
            }

            if ($notes) {
                $prefix = __('messages.note_prefix', [
                    'date'   => now()->format('Y-m-d H:i'),
                    'action' => 'Скасування'
                ]);
                $updateData['notes'] = $rental->notes ? $rental->notes . "\n" . $prefix . $notes : $prefix . $notes;
            }

            $rental->update($updateData);
            $book->increment('available_copies');

            return $rental;
        });

        $updatedRental->user->notify(new RentalCancelledNotification($updatedRental, $notes));

        return $updatedRental;
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
    public function returnRental(Rental $rental, ?string $notes = null, bool $isFeePaid = false): Rental
    {
        if ($rental->status !== RentalStatus::ACTIVE) {
            throw new Exception(__('messages.cannot_return_inactive'));
        }

        return DB::transaction(function () use ($rental, $notes, $isFeePaid) {
            $book = $rental->book()->lockForUpdate()->first();

            $totalPenalty = $this->calculateLateDaysFee($rental);

            $updateData = [
                'status'      => RentalStatus::COMPLETED,
                'returned_at' => now(),
            ];

            if ($totalPenalty > 0) {
                $updateData['late_fee'] = $totalPenalty;
                // If there is a late fee, we set payment status to PENDING until the fee is paid. 
                // If there is no fee or if client paid, we can mark it as PAID immediately.
                $updateData['payment_status'] = $isFeePaid ? PaymentStatus::PAID : PaymentStatus::PENDING;
            }

            if ($notes) {
                $prefix = __('messages.note_prefix', [
                    'date'   => now()->format('Y-m-d H:i'),
                    'action' => __('messages.action_returned')
                ]);
                $updateData['notes'] = $rental->notes ? $rental->notes . "\n" . $prefix . $notes : $prefix . $notes;
            }

            $rental->update($updateData);

            $book->increment('available_copies');

            return $rental;
        });
    }

    /**
     * Mark the rental as lost (Decrement total copies and calculate full penalty).
     */
    public function markAsLost(Rental $rental, ?string $notes = null, bool $isFeePaid = false): Rental
    {
        if ($rental->status !== RentalStatus::ACTIVE) {
            throw new Exception(__('messages.cannot_mark_lost_inactive'));
        }

        return DB::transaction(function () use ($rental, $notes, $isFeePaid) {
            $book = $rental->book()->lockForUpdate()->first();

            $bookValue = $book->price;
            $processingFee = config('rental.lost_processing_fee', 100);
            
            // Використовуємо наш новий метод
            $lateDaysFee = $this->calculateLateDaysFee($rental); 

            $totalPenalty = $bookValue + $processingFee + $lateDaysFee;

            $updateData = [
                'status'         => RentalStatus::LOST,
                'late_fee'       => $totalPenalty,
                'payment_status' => $isFeePaid ? PaymentStatus::PAID : PaymentStatus::PENDING,
            ];

            if ($notes) {
                $prefix = __('messages.note_prefix', [
                    'date'   => now()->format('Y-m-d H:i'),
                    'action' => __('messages.action_lost')
                ]);
                $updateData['notes'] = $rental->notes ? $rental->notes . "\n" . $prefix . $notes : $prefix . $notes;
            }

            $rental->update($updateData);
            $book->decrement('total_copies');

            return $rental;
        });
    }

    /**
     * Manually mark the rental payment as successful (Admin action for Cash/Terminal).
     */
    public function markAsPaid(Rental $rental, ?string $notes = null): Rental
    {
        if ($rental->payment_status === PaymentStatus::PAID) {
            throw new Exception(__('messages.rental_already_paid'));
        }

        return DB::transaction(function () use ($rental, $notes) {
            $updateData = [
                'payment_status' => PaymentStatus::PAID,
            ];

            $prefix = __('messages.note_prefix', [
                'date'   => now()->format('Y-m-d H:i'),
                'action' => __('messages.action_payment')
            ]);
            
            $customNote = $notes ?? __('messages.manual_payment_confirmed');
            $updateData['notes'] = $rental->notes ? $rental->notes . "\n" . $prefix . $customNote : $prefix . $customNote;

            $rental->update($updateData);

            return $rental;
        });
    }


    /**
     * Рахує штраф виключно за дні запізнення.
     */
    private function calculateLateDaysFee(Rental $rental): float
    {
        $lateDaysFee = 0;
        $now = Carbon::now();
        
        if ($now->greaterThan($rental->end_date->endOfDay())) {
            $overdueDays = (int) $rental->end_date->startOfDay()->diffInDays($now->startOfDay());
            $multiplier = config('rental.penalty_multiplier', 2);
            $lateDaysFee = $overdueDays * ($rental->daily_price * $multiplier);
        }
        
        return $lateDaysFee;
    }
}
