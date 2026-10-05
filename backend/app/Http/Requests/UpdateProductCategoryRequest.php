<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin'
            || $this->user()?->role === 'operator';
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_categories', 'name')
                    ->ignore($categoryId),
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}