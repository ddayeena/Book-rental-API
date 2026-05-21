<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Auth\LoginRequest;
use App\Http\Requests\Api\v1\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], __('auth.register_success'), 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error(__('auth.login_failed'), 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            ['access_token' => $token, 'token_type' => 'Bearer'],
            __('auth.login_success'),
            200
        );
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();
        return $this->success([], __('auth.logout_success'), 200);
    }
}
