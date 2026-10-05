<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_code' => [
                'required',
                'string',
                'max:50',
            ],
            'payment_reference' => [
                'required',
                'string',
                'max:255',
            ],
            'status' => [
                'required',
                Rule::in(['PAID']),
            ],
        ];
    }
}