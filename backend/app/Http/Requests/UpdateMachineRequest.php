<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $machineId = $this->route('machine')?->id;

        return [
            'machine_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('machines', 'machine_code')
                    ->ignore($machineId),
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