<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'amount'=>'required|numeric|min:0.5',
            'reason'=>'nullable|string|max:500',
        ];
    }
}
