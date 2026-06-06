<?php

namespace App\Http\Requests\Api\v1;

use App\Enums\PaymentMethod;
use App\Http\Requests\BaseRequest;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreRentalRequest extends BaseRequest
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
        $maxRentalDays = 30;

        return [
            'book_id' => [
                'required',
                'string',
                'exists:books,id'
            ],

            'start_date' => [
                'required',
                'date',
                'after_or_equal:today'
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',

                function (string $attribute, mixed $value, Closure $fail) use ($maxRentalDays) {
                    // Only run this check if start_date is present in the request
                    if (!$this->input('start_date')) {
                        return;
                    }

                    $startDate = Carbon::parse($this->input('start_date'));
                    $endDate = Carbon::parse($value);

                    // If the difference exceeds our limit, fail the validation
                    if ($startDate->diffInDays($endDate) > $maxRentalDays) {
                       $fail(__('messages.rental_period_exceeded', ['days' => $maxRentalDays]));
                    }
                },
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'payment_method' => [
                'required',
                Rule::enum(PaymentMethod::class)
            ],

        ];
    }
}
