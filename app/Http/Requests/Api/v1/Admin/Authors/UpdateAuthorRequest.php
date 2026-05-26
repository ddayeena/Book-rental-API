<?php

namespace App\Http\Requests\Api\v1\Admin\Authors;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateAuthorRequest extends BaseRequest
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
        if ($this->has('name') && empty($this->input('slug'))) {
            $this->merge([
                'slug' => Str::slug($this->input('name'))
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
        $author = $this->route('author');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('authors', 'name')->ignore($author)
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('authors', 'slug')->ignore($author)
            ],
            'bio' => ['nullable', 'string']
        ];
    }
}
