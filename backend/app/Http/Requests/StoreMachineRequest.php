<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'machine_code' => [
                'required',
                'string',
                'max:50',
                'unique:machines,machine_code',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'location' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'ONLINE',
                    'OFFLINE',
                ]),
            ],
            'temperature_threshold' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }
}