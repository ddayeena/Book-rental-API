<?php

namespace App\Http\Requests\Api\v1\Admin\User;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends BaseRequest
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
        $user = $this->route('user');

        return [
            'first_name'   => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],
            'last_name'    => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],
            'email'        => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id)
            ],
        ];
    }
}
