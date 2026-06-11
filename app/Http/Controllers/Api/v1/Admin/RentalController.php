<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Filters\Admin\RentalFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Admin\Rental\IssueRentalRequest;
use App\Http\Requests\Api\v1\Admin\Rental\MarkRentalLostRequest;
use App\Http\Requests\Api\v1\Admin\Rental\ReturnRentalRequest;
use App\Http\Requests\Api\v1\Admin\Rental\StoreRentalRequest;
use App\Http\Requests\Api\v1\Admin\Rental\UpdateRentalRequest;
use App\Http\Resources\Api\v1\Admin\Rental\RentalListResource;
use App\Http\Resources\Api\v1\Admin\Rental\RentalResource;
use App\Models\Rental;
use App\Models\User;
use App\Services\RentalService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RentalController extends Controller
{
    public function __construct(
        protected RentalService $rentalService
    ) {}

    /**
     * Display a listing of all rentals for managers.
     */
    public function index(RentalFilter $filter)
    {
        $rentals = Rental::with(['book', 'user'])->filter($filter)->apiPaginate();
        return $this->respondWithPagination(RentalListResource::collection($rentals));
    }

    /**
     * Store a newly created rental order by admin/manager.
     */
    public function store(StoreRentalRequest $request)
    {
        try {
            $validated = $request->validated();

            $targetUser = User::findOrFail($validated['user_id']);

            $rental = $this->rentalService->createRental($targetUser, $validated);

            return $this->success(new RentalResource($rental), __('messages.created'), 201);
        } catch (\Exception $e) {
            return $this->error(__('messages.creation_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Rental $rental)
    {
        $rental->load(['book', 'user']);
        return $this->success(new RentalResource($rental), '', 200);
    }

    /**
     * Update the specified rental order details.
     */
    public function update(UpdateRentalRequest $request, Rental $rental)
    {
        try {
            $updatedRental = $this->rentalService->updateRental($rental, $request->validated());

            return $this->success(new RentalResource($updatedRental), __('messages.updated'), 200);
        } catch (\Exception $e) {
            return $this->error(__('messages.update_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Remove the specified rental from storage.
     */
    public function destroy(Rental $rental)
    {
        try {
            $this->rentalService->deleteRental($rental);
            return $this->success(null, __('messages.deleted'), 200);
        } catch (\Exception $e) {
            return $this->error(__('messages.delete_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted rental.
     */
    public function restore(string $id)
    {
        try {
            $rental = Rental::withTrashed()->findOrFail($id);

            $restoredRental = $this->rentalService->restoreRental($rental);

            return $this->success(new RentalResource($restoredRental), __('messages.updated'), 200);
        } catch (ModelNotFoundException $e) {
            return $this->error(__('messages.not_found'), 404);
        } catch (\Exception $e) {
            return $this->error(__('messages.update_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Mark the rental as active (physically issue the book).
     */
    public function issue(IssueRentalRequest $request, Rental $rental)
    {
        try {
            $validated = $request->validated();

            $updatedRental = $this->rentalService->issueRental($rental, $validated['notes'] ?? null);

            return $this->success(new RentalResource($updatedRental), __('messages.rental_issued'), 200);
        } catch (\Exception $e) {
            return $this->error(__('messages.issue_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Process book return and calculate late fees.
     */
    public function processReturn(ReturnRentalRequest $request, Rental $rental)
    {
        try {
            $validated = $request->validated();

            $updatedRental = $this->rentalService->returnRental($rental, $validated['notes'] ?? null);

            $message = __('messages.rental_returned');
            if ($updatedRental->late_fee > 0) {
                $message = __('messages.rental_returned_with_fee', ['fee' => $updatedRental->late_fee]);
            }

            return $this->success(new RentalResource($updatedRental), $message, 200);
        } catch (\Exception $e) {
            return $this->error(__('messages.return_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Mark the rental as lost and apply penalty.
     */
    public function markLost(MarkRentalLostRequest $request, Rental $rental)
    {
        try {
            $validated = $request->validated();

            $updatedRental = $this->rentalService->markAsLost(
                $rental, 
                $validated['notes'] ?? null,
                $validated['is_fee_paid'] ?? false 
            );
            return $this->success(
                new RentalResource($updatedRental),
                __('messages.rental_marked_lost', ['fee' => $updatedRental->late_fee]), 
                200
            );
        } catch (\Exception $e) {
            return $this->error(__('messages.lost_failed'), 400, $e->getMessage());
        }
    }
}
