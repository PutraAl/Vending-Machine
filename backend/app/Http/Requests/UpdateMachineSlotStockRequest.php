<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMachineSlotStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['admin', 'operator'],
            true
        );
    }

    public function rules(): array
    {
        return [
            'stock' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}