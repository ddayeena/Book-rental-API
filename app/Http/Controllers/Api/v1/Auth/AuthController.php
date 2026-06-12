<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Auth\LoginRequest;
use App\Http\Requests\Api\v1\Auth\RegisterRequest;
use App\Http\Requests\Api\v1\Auth\ResendVerifyCodeRequest;
use App\Http\Requests\Api\v1\Auth\VerifyEmailCodeRequest;
use App\Mail\VerifyEmailMail;
use App\Models\EmailVerifyToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());

        $code = random_int(100000, 999999);
        EmailVerifyToken::create([
            'email'      => $user->email,
            'token'      => $code,
            'created_at' => now()
        ]);

        Mail::to($user->email)->send(new VerifyEmailMail($code));

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ], __('auth.register_success'), 201);
    }

    public function verifyEmail(VerifyEmailCodeRequest $request)
    {
        $data = $request->validated();

        $token = EmailVerifyToken::where('email', $data['email'])
            ->where('token', $data['code'])
            ->first();

        if (!$token)
            return $this->error(__('auth.invalid_code'), 400);

        if ($token->created_at->diffInHours(now()) > 24) {
            return $this->error(__('auth.code_expired'), 400);
        }

        $user = User::where('email', $data['email'])->first();
        if ($user->email_verified_at !== null) {
            return $this->success([], __('auth.email_already_verified'), 200);
        }

        $user->update(['email_verified_at' => now()]);
        $token->delete();

        return $this->success([], __('auth.email_verified_success'), 200);
    }

    public function resendVerifyCode(ResendVerifyCodeRequest $request)
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if ($user->email_verified_at !== null) {
            return $this->success([], __('auth.email_already_verified'), 200);
        }

        $code = random_int(100000, 999999);

        EmailVerifyToken::updateOrCreate(
            ['email' => $user->email], 
            [
                'token'      => $code, 
                'created_at' => now()  
            ]
        );

        Mail::to($user->email)->send(new VerifyEmailMail($code));

        return $this->success([], __('auth.verify_code_resent'), 200);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error(__('auth.login_failed'), 401);
        }

        if ($user->is_blocked) {
            return $this->error(__('auth.account_blocked'), 403);
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
