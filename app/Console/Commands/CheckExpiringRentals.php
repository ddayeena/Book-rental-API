<?php

namespace App\Console\Commands;

use App\Enums\RentalStatus;
use App\Models\Rental;
use App\Notifications\RentalExpiringNotification;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:check-expiring-rentals')]
#[Description('Looks for rentals that are expiring tomorrow and sends notifications to users.')]
class CheckExpiringRentals extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $rentals = Rental::with(['user', 'book'])
            ->where('status', RentalStatus::ACTIVE) 
            ->whereDate('end_date', $tomorrow)
            ->get();

        $count = 0;

        foreach ($rentals as $rental) {
            try {
                $rental->user->notify(new RentalExpiringNotification($rental));
                $count++;
            } catch (\Exception $e) {
                $this->error("ERROR: " . $e->getMessage()); 
                
                Log::error("Error processing expiring rental {$rental->id}: " . $e->getMessage());
            }
        }

        $this->info("Command executed. Notifications sent to queue: {$count}");
    }
}
