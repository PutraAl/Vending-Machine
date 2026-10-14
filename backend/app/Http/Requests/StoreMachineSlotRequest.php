<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMachineSlotRequest extends FormRequest
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
            'product_id' => [
                'required',
                'exists:products,id',
            ],

            'slot_code' => [
                'required',
                'string',
                'max:20',
            ],

            'capacity' => [
                'required',
                'integer',
                'min:1',
            ],

            'current_qty' => [
                'required',
                'integer',
                'min:0',
                'lte:capacity',
            ],
        ];
    }
}