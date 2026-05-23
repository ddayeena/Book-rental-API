<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Auth\SendPasswordResetCodeRequest;
use App\Http\Requests\Api\v1\Auth\VerifyCodeRequest;
use App\Http\Requests\Api\v1\Auth\ResetPasswordRequest; 
use App\Mail\ResetCodeMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    public function sendCode(SendPasswordResetCodeRequest $request)
    {
        try {
            $email = $request->validated('email');
            // Delete any existing tokens for this email
            PasswordResetToken::where('email', $email)->delete();

            $code = random_int(100000, 999999);

            PasswordResetToken::create([
                'email'      => $email,
                'token'      => $code,
                'created_at' => now()
            ]);

            Mail::to($email)->send(new ResetCodeMail($code));

            return $this->success([], __('auth.reset_send'), 200);
        } catch (\Throwable $th) {
            return $this->error(__('auth.reset_not_send'), 500);
        }
    }

    public function verifyCode(VerifyCodeRequest $request)
    {
        $data = $request->validated();

        $errorResponse = $this->validateResetToken($data['email'], $data['code']);
        if ($errorResponse) return $errorResponse;

        return $this->success([], __('auth.code_valid'), 200);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();

        $errorResponse = $this->validateResetToken($data['email'], $data['code']);
        if ($errorResponse) return $errorResponse;

        User::where('email', $data['email'])->update([
            'password' => Hash::make($data['password'])
        ]);

        // Delete the token after successful password reset
        PasswordResetToken::where('email', $data['email'])->delete();

        return $this->success([], __('auth.password_reset_success'), 200);
    }

    /**
     * Private helper to validate token existence and expiration
     */
    private function validateResetToken(string $email, string $code)
    {
        $token = PasswordResetToken::where('email', $email)
            ->where('token', $code)
            ->first();

        if (!$token) {
            return $this->error(__('auth.invalid_code'), 400);
        }

        if ($token->created_at->diffInMinutes(now()) > 15) {
            return $this->error(__('auth.code_expired'), 400);
        }

        return null; 
    }
}
