<?php

namespace App\Http\Requests\Api\v1\Admin\Rental;

use App\Enums\PaymentMethod;
use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRentalRequest extends BaseRequest
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
            'start_date'     => [
                'sometimes',
                'date'
            ],
            'end_date'       => [
                'sometimes',
                'date',
                'after_or_equal:start_date'
            ],
            'notes'          => [
                'nullable',
                'string'
            ],
            'payment_method' => [
                'sometimes',
                Rule::enum(PaymentMethod::class)
            ],
            'late_fee' => [
                'sometimes',
                'numeric',
                'min:0'
            ],
        ];
    }
}
