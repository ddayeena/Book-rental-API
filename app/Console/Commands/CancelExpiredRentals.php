<?php

namespace App\Console\Commands;

use App\Enums\RentalStatus;
use App\Models\Rental;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:cancel-expired-rentals')]
#[Description('Automatically cancel PENDING rentals older than specified days')]
class CancelExpiredRentals extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RentalService $rentalService)
    {
        $days = config('rental.auto_cancel_days', 5);
        $expiredDate = Carbon::now()->subDays($days);

        $rentals = Rental::where('status', RentalStatus::PENDING)
            ->where('created_at', '<=', $expiredDate)
            ->get();

        if ($rentals->isEmpty()) {
            $this->info('There are no pending orders to cancel.');
            return;
        }

        $count = 0;
        foreach ($rentals as $rental) {
            try {
                $rentalService->cancelRental(
                    $rental, 
                    __('messages.auto_cancel_reason', ['days' => $days])
                );
                $count++;
            } catch (\Exception $e) {
                Log::error("Auto-cancellation error {$rental->id}: " . $e->getMessage());
            }
        }

        $this->info("Successfully canceled {$count} pending orders.");
    }
}
