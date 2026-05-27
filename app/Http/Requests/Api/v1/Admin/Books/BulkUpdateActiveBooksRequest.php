<?php

namespace App\Http\Requests\Api\v1\Admin\Books;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateActiveBooksRequest extends FormRequest
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
            'is_active' => [
                'required',
                'boolean'
            ],
        ];
    }
}
