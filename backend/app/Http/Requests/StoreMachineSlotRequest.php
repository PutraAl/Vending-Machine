<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

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
            'machine_id' => [
                'required',
                'exists:machines,id',
            ],

            'product_id' => [
                'required',
                'exists:products,id',
            ],

            'slot_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('machine_slots', 'slot_code')
                    ->where(
                        fn($query) =>
                        $query->where('machine_id', $this->machine_id)
                    ),
            ],

            'capacity' => [
                'required',
                'integer',
                'min:1',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
                'lte:capacity',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $stock = (int) $this->input('stock', 0);
                $capacity = (int) $this->input('capacity', 0);

                if ($stock > $capacity) {
                    $validator->errors()->add(
                        'stock',
                        'Stock cannot exceed capacity.'
                    );
                }
            },
        ];
    }
}
