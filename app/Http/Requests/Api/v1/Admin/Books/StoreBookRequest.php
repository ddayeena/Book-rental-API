<?php

namespace App\Http\Requests\Api\v1\Admin\Books;

use App\Enums\BookLanguage;
use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreBookRequest extends BaseRequest
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
        return [
            'title' => [
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
                'required',
                new Enum(BookLanguage::class)
            ],
            'pages_count' => [
                'nullable',
                'integer',
                'min:1'
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:books,slug'
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'publication_year' => [
                'required',
                'integer',
                'digits:4'
            ],
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                'unique:books,isbn'
            ],
            'total_copies' => [
                'required',
                'integer',
                'min:1'
            ],
            'available_copies' => [
                'required',
                'integer',
                'min:0',
                'lte:total_copies'
            ],
            'daily_price' => [
                'required',
                'numeric',
                'min:0'
            ],
            'price' => [
                'required',
                'numeric',
                'min:0'
            ],
            
            'is_active' => [
                'required',
                'boolean'
            ],
            'categories' => [
                'required',
                'array',
                'min:1'
            ],
            'categories.*' => [
                'ulid',
                'exists:categories,id'
            ],
            'authors' => [
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
