<?php

namespace App\Http\Requests\Api\v1\Admin\Rental;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Http\Requests\BaseRequest;
use Carbon\Carbon;
use Closure;
use Illuminate\Validation\Rule;

class StoreRentalRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $maxRentalDays = 30;

        return [
            'user_id' => [
                'required',
                'string',
                'exists:users,id'
            ],

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
                    if (!$this->input('start_date')) {
                        return;
                    }

                    $startDate = Carbon::parse($this->input('start_date'));
                    $endDate = Carbon::parse($value);

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

            'status' => [
                'nullable',
                Rule::enum(RentalStatus::class)
            ],

            'payment_status' => [
                'nullable',
                Rule::enum(PaymentStatus::class)
            ],
        ];
    }
}