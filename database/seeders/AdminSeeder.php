<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(env('ADMIN_EMAIL') && env('ADMIN_PASSWORD')) {
            User::create([
                'first_name'        => 'Admin',
                'last_name'         => 'Super',
                'email'             => env('ADMIN_EMAIL'),
                'password'          => env('ADMIN_PASSWORD'),
                'role'              => UserRole::ADMIN->value,
                'email_verified_at' => now(),
            ]);
        }
    }
}
