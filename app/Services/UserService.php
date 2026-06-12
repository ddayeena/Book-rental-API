<?php

namespace App\Services;

use App\Enums\RentalStatus;
use App\Models\User;
use App\Notifications\UserCreatedByAdminNotification;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Create a new user (usually triggered by Admin).
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $plainPassword = $data['password'];

            $data['password'] = Hash::make($plainPassword);
            $data['is_blocked'] = false;
            $data['email_verified_at'] = now();

            $user = User::create($data);

            $user->notify(new UserCreatedByAdminNotification($plainPassword));

            return $user;
        });
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    public function blockUser(User $user, string $currentAdminId): User
    {
        if ($user->id === $currentAdminId) {
            throw new Exception(__('messages.cannot_block_self'));
        }

        return DB::transaction(function () use ($user) {
            $user->update(['is_blocked' => true]);

            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            return $user;
        });
    }

    /**
     * Unblock a user.
     */
    public function unblockUser(User $user): User
    {
        $user->update(['is_blocked' => false]);
        
        return $user;
    }

    /**
     * Change user's role.
     */
    public function changeRole(User $user, string $newRole, string $currentAdminId): User
    {
        if ($user->id === $currentAdminId) {
            throw new Exception(__('messages.cannot_change_own_role'));
        }

        $user->update(['role' => $newRole]);

        return $user;
    }

    /**
     * Soft delete a user with business rule validations.
     */
    public function deleteUser(User $user, string $currentAdminId): void
    {
        if ($user->id === $currentAdminId) {
            throw new Exception(__('messages.cannot_delete_self'));
        }

        // If the user has active rentals, we should not allow deletion
        $hasActiveRentals = $user->rentals()
            ->whereIn('status', [
                RentalStatus::PENDING,
                RentalStatus::ACTIVE,
                RentalStatus::OVERDUE,
            ])->exists();

        if ($hasActiveRentals) {
            throw new Exception(__('messages.cannot_delete_user_with_active_rentals'));
        }

        // Delete all tokens (sessions) for the user to ensure they are logged out
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $user->delete();
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restoreUser(User $user): User
    {
        $user->restore();

        return $user;
    }
}