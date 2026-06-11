<?php

namespace App\Http\Requests\Api\v1\Admin\Rental;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class BulkExportRentalsRequest extends BaseRequest
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
            'ids'   => [
                'required',
                'array',
                'min:1'
            ],
            'ids.*' => [
                'ulid',
                'exists:rentals,id'
            ],
        ];
    }
}
