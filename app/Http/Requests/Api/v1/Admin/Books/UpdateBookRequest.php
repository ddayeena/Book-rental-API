<?php

namespace App\Http\Requests\Api\v1\Admin\Books;

use App\Enums\BookLanguage;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule; // Обов'язково додаємо цей імпорт!
use Illuminate\Validation\Rules\Enum;

class UpdateBookRequest extends BaseRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $book = $this->route('book'); 

        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],
            'cover_image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,svg',
                'max:2048'
            ],
            'language' => [
                'sometimes',
                'required',
                new Enum(BookLanguage::class)
            ],
            'pages_count' => [
                'nullable',
                'integer',
                'min:1'
            ],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('books', 'slug')->ignore($book) 
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'publication_year' => [
                'sometimes',
                'required',
                'integer',
                'digits:4'
            ],
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('books', 'isbn')->ignore($book) 
            ],
            'total_copies' => [
                'sometimes',
                'required',
                'integer',
                'min:1'
            ],
            'available_copies' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'lte:total_copies'
            ],
            'daily_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0'
            ],
            'is_active' => [
                'sometimes',
                'required',
                'boolean'
            ],
            'categories' => [
                'sometimes',
                'required',
                'array',
                'min:1'
            ],
            'categories.*' => [
                'ulid',
                'exists:categories,id'
            ],
            'authors' => [
                'sometimes',
                'required',
                'array',
                'min:1'
            ],
            'authors.*' => [
                'ulid',
                'exists:authors,id'
            ],
        ];
    }
}