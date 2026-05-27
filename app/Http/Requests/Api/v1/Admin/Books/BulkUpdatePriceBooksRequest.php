<?php

namespace App\Http\Requests\Api\v1\Admin\Books;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class BulkUpdatePriceBooksRequest extends BaseRequest
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
            'ids' => [
                'required',
                'array',
                'min:1'
            ],
            'ids.*' => [
                'ulid',
                'exists:books,id'
            ],
            'type' => [
                'required',
                'string',
                'in:fixed,percentage,flat' 
            ],
            'value' => [
                'required',
                'numeric',
                // If type is fixed, price cannot be negative. For adjustments, it can be negative (discount).
                'callback' => function ($attribute, $value, $fail) {
                    if ($this->input('type') === 'fixed' && $value < 0) {
                        $fail(__('messages.error_below_zero'));
                    }
                }
            ],
        ];
    }
}
