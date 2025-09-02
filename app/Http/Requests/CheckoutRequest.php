<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => 'required|exists:merchants,id',
            'amount' => 'required|numeric|min:1',
            'currency' => 'sometimes|string|size:3',
            'method' => 'required|in:card,upi,wallet',
            'customer.name' => 'nullable|string|max:255',
            'customer.email' => 'nullable|email|max:255',
            'customer.phone' => 'nullable|string|max:32',
            'cart' => 'nullable|array',
            'return_url_success' => 'nullable|url',
            'return_url_failure' => 'nullable|url',
        ];
    }
}
