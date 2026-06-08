<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Filters\Admin\RentalFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Admin\Rental\StoreRentalRequest;
use App\Http\Requests\Api\v1\Admin\Rental\UpdateRentalRequest;
use App\Http\Resources\Api\v1\Admin\Rental\RentalListResource;
use App\Http\Resources\Api\v1\Admin\Rental\RentalResource;
use App\Models\Rental;
use App\Models\User;
use App\Services\RentalService;

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
}
