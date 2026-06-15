<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Enums\RentalStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\Admin\Books\BookListResource;
use App\Http\Resources\Api\v1\Admin\Review\ReviewResource;
use App\Models\Book;
use App\Models\Rental;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Base metrics for the admin dashboard
        $totalUsers = User::where('role', 'user')->count();
        $newUsersThisMonth = User::where('role', 'user')
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        $activeRentalsCount = Rental::where('status', RentalStatus::ACTIVE)->count();
        $overdueRentalsCount = Rental::where('status', RentalStatus::OVERDUE)->count();


        // Popular books based on the number of rentals 
        $popularBooks = Book::withCount('rentals')
            ->orderByDesc('rentals_count')
            ->take(10)
            ->get();


        $deficitBooksCount = Book::where('available_copies', 0)->count();

        // New reviews in the last 24 hours
        $newReviewsCount = Review::where('created_at', '>=', Carbon::now()->subHours(24))->count();
        
        // Reviews with low ratings (1 or 2 stars) for admin attention
        $problematicReviews = Review::with('user:id,first_name,last_name')
            ->whereIn('rating', [1, 2])
            ->latest()
            ->take(5)
            ->get();


        // Graph data for rentals created in the last 7 days, grouped by date
        $rentalsChartData = Rental::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->success([
            'metrics' => [
                'total_users'           => $totalUsers,
                'new_users_this_month'  => $newUsersThisMonth,
                'active_rentals'        => $activeRentalsCount,
                'overdue_rentals'       => $overdueRentalsCount,
                'deficit_books'         => $deficitBooksCount,
                'new_reviews_24h'       => $newReviewsCount,
            ],
            'content' => [
                'popular_books'         => BookListResource::collection($popularBooks),
                'problematic_reviews'   => ReviewResource::collection($problematicReviews),
            ],
            'chart' => [
                'rentals_last_7_days'   => $rentalsChartData,
            ],
        ]);
    }
}