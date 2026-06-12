<?php

namespace App\Http\Requests\Api\v1\Admin\User;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class StoreUserRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'   => [
                'required',
                'string',
                'max:255'
            ],
            'last_name'    => [
                'required',
                'string',
                'max:255'
            ],
            'email'        => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                'unique:users,phone_number'
            ],
            'password'     => [
                'required',
                'string',
                Password::defaults()
            ],
            'role'         => [
                'required',
                'string',
                Rule::enum(UserRole::class)
            ]
        ];
    }
}
