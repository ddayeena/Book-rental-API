<?php

namespace App\Http\Requests\Api\v1\Admin\Categories;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UpdateCategoryRequest extends BaseRequest
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
        $categoryId = $this->route('category') ? $this->route('category')->id : null;

        return [
            'name' => [
                'sometimes', 
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId)
            ],
            'description' => [
                'nullable', 
                'string',
                'max:1000'
            ],
            'slug' => [
                'sometimes', 
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId) 
            ],
        ];
    }
}
