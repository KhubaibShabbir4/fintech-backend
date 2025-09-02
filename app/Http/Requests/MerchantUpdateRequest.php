<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantUpdateRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'business_name'=>'sometimes|required|string|max:255',
            'logo'=>'sometimes|nullable|image|max:2048',
            'bank_account_name'=>'sometimes|nullable|string|max:255',
            'bank_account_number'=>'sometimes|nullable|string|max:64',
            'bank_ifsc_swift'=>'sometimes|nullable|string|max:64',
            'payout_preferences'=>'sometimes|nullable|array',
        ];
    }
}

