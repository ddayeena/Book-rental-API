<?php

namespace App\Console\Commands;

use App\Enums\RentalStatus;
use App\Models\Rental;
use App\Notifications\RentalOverdueNotification;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:check-overdue-rentals')]
#[Description('Automatically mark active rentals as overdue if the end date has passed')]
class CheckOverdueRentals extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rentals = Rental::where('status', RentalStatus::ACTIVE)
            ->where('end_date', '<', Carbon::now()->startOfDay())
            ->get();

        if ($rentals->isEmpty()) {
            $this->info('No overdue rentals found.');
            return;
        }

        $count = 0;
        foreach ($rentals as $rental) {
            try {
                $rental->update([
                    'status' => RentalStatus::OVERDUE
                ]);

                $rental->user->notify(new RentalOverdueNotification($rental));

                $count++;
            } catch (\Exception $e) {
                Log::error("Error processing overdue rental {$rental->id}: " . $e->getMessage());
            }
        }

        $this->info("Successfully marked {$count} rentals as OVERDUE and notified users.");
    }
}