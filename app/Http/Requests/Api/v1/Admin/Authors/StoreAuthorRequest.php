<?php

namespace App\Http\Requests\Api\v1\Admin\Authors;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreAuthorRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    protected function prepareForValidation(): void
    {
        if ($this->has('name') && empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name)
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:authors,name'
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:authors,slug'
            ],
            'bio'  => ['nullable', 'string'],
        ];
    }
}
