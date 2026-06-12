<?php

namespace App\Services;

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
}