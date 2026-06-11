<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Filters\RentalFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\StoreRentalRequest;
use App\Http\Resources\Api\v1\Rental\RentalListResource;
use App\Http\Resources\Api\v1\Rental\RentalResource;
use App\Models\Rental;
use App\Services\PaymentService;
use App\Services\RentalService;
use Exception;
use Illuminate\Http\Request;

class RentalController extends Controller
{

    public function __construct(
        protected RentalService $rentalService,
        protected PaymentService $paymentService
    ) {}

    /**
     * Display a listing of the user's rentals.
     */
    public function index(Request $request, RentalFilter $filter)
    {
        $rentals = $request->user()->rentals()
            ->with('book')
            ->filter($filter)
            ->apiPaginate();

        return $this->respondWithPagination(RentalListResource::collection($rentals));
    }

    /**
     * Store a newly created rental order.
     */
    public function store(StoreRentalRequest $request)
    {
        try {
            $rental = $this->rentalService->createRental(
                $request->user(),
                $request->validated()
            );

            return $this->success(new RentalResource($rental), __('messages.created'), 201);
        } catch (Exception $e) {
            return $this->error(__('messages.creation_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Display the specific rental details.
     */
    public function show(string $id, Request $request)
    {
        // If the ID belongs to another user, Laravel will automatically throw a 404 Not Found.
        $rental = $request->user()->rentals()
            ->with('book')
            ->findOrFail($id);
        return $this->success(new RentalResource($rental), '', 200);
    }

    /**
     * Get all dictionaries/enums related to rentals for the frontend.
     */
    public function dictionaries()
    {
        $data = [
            'rental_statuses' => array_map(fn($status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ], RentalStatus::cases()),

            'payment_methods' => array_map(fn($method) => [
                'value' => $method->value,
                'label' => $method->label(),
            ], PaymentMethod::cases()),

            'payment_statuses' => array_map(fn($status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ], PaymentStatus::cases()),
        ];

        return $this->success($data, '', 200);
    }

    /**
     * Cancel a pending rental order.
     */
    public function cancel(string $id, Request $request)
    {
        try {
            $rental = $request->user()->rentals()->findOrFail($id);

            $rental = $this->rentalService->cancelRental($rental);

            $message = $rental->payment_status === PaymentStatus::REFUNDED
                ? __('messages.refund_initiated')
                : __('messages.canceled');

            return $this->success(new RentalResource($rental), $message, 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Generate checkout URL for unpaid late fee.
     */
    public function payDebt(Rental $rental)
    {
        if ($rental->user_id !== auth()->id()) {
            return $this->error(__('messages.unauthorized_action'), 403);
        }

        if (empty($rental->late_fee) || $rental->payment_status === PaymentStatus::PAID) {
            return $this->error(__('messages.no_unpaid_debt'), 400);
        }

        try {
            $checkoutUrl = $this->paymentService->generateDebtCheckoutUrl($rental);

            return $this->success([
                'checkout_url' => $checkoutUrl
            ], __('messages.debt_checkout_url_generated'), 200);
        } catch (\Exception $e) {
            return $this->error(__('messages.debt_payment_failed'), 500, $e->getMessage());
        }
    }
}
