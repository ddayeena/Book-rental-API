<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Enums\RentalStatus;
use App\Filters\Admin\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Admin\User\ChangeUserRoleRequest;
use App\Http\Requests\Api\v1\Admin\User\StoreUserRequest;
use App\Http\Requests\Api\v1\Admin\User\UpdateUserRequest;
use App\Http\Resources\Api\v1\Admin\User\UserListResource;
use App\Http\Resources\Api\v1\Admin\User\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(UserFilter $filter)
    {
        $users = User::filter($filter)->apiPaginate();
        return $this->respondWithPagination(UserListResource::collection($users));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return $this->success(
                new UserResource($user),
                __('messages.created'),
                201
            );
        } catch (\Exception $e) {
            return $this->error(__('messages.creation_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->loadCount([
            'rentals as total_rentals_count',
            'rentals as active_rentals_count' => function ($query) {
                $query->where('status', RentalStatus::ACTIVE);
            },
            'rentals as overdue_rentals_count' => function ($query) {
                $query->where('status', RentalStatus::OVERDUE);
            },
        ]);

        $user->loadSum([
            'rentals as current_penalties_sum' => function ($query) {
                $query->where('status', RentalStatus::OVERDUE);
            }
        ], 'late_fee');

        $user->load(['rentals' => function ($query) {
            $query->latest()->limit(10)->with('book');
        }]);

        return $this->success(new UserResource($user));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $updatedUser = $this->userService->updateUser($user, $request->validated());

            return $this->success(
                new UserResource($updatedUser),
                __('messages.updated'),
                200
            );
        } catch (\Exception $e) {
            return $this->error(__('messages.update_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        try {
            $this->userService->deleteUser($user, $request->user()->id);
            return $this->success(null, __('messages.deleted'), 200);
        } catch (\Exception $e) {
            return $this->error(__('messages.deletion_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(User $user)
    {
        try {
            $restoredUser = $this->userService->restoreUser($user);

            return $this->success(new UserResource($restoredUser), __('messages.updated'), 200);
        } catch (\Exception $e) {
            return $this->error(__('messages.update_failed'), 400, $e->getMessage());
        }
    }


    public function block(Request $request, User $user)
    {
        try {
            $updatedUser = $this->userService->blockUser($user, $request->user()->id);

            return $this->success(
                new UserResource($updatedUser),
                __('messages.user_blocked_successfully'),
                200
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Unblock the specified user.
     */
    public function unblock(User $user)
    {
        try {
            $updatedUser = $this->userService->unblockUser($user);

            return $this->success(
                new UserResource($updatedUser),
                __('messages.user_unblocked_successfully'),
                200
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Change the role of the specified user.
     */
    public function changeRole(ChangeUserRoleRequest $request, User $user)
    {
        try {
            $validated = $request->validated();

            $updatedUser = $this->userService->changeRole(
                $user,
                $validated['role'],
                $request->user()->id
            );

            return $this->success(
                new UserResource($updatedUser),
                __('messages.user_role_changed_successfully'),
                200
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
