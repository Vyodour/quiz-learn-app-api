<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
            'payment_gateway' => 'required|string|in:midtrans,xendit,stripe',
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.exists' => 'Package option not found or unactive!',
            'payment_gateway.in' => 'Payment method not supported!',
        ];
    }
}
