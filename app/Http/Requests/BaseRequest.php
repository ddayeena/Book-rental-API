<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;

class BaseRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }
    
    protected function prepareForValidation(): void
    {
        // Look for the source text to generate slug (title for books, name for categories/authors)
        $sourceText = $this->title ?? $this->name;

        if (empty($this->slug) && !empty($sourceText)) {
            $this->merge([
                'slug' => Str::slug($sourceText),
            ]);
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->error('Validation error', 422, $validator->errors())
        );
    }
}
