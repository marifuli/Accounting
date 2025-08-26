<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseIncomeAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Currencies exactly as in your migration enum
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'INR', 'BRL', 'ZAR', 'BDT', 'other'];


        return [
            'name'                      => ['required', 'string', 'max:255'],
            'current_balance'           => ['nullable', 'numeric', 'decimal:0,2'],
            'currency'                  => ['required', Rule::in($currencies)],
            'description'               => ['nullable', 'string'],
            'type'                      => ['required', Rule::in(['income', 'expense'])],
            'transaction_category_id'   => ['required', 'integer', 'exists:transaction_categories,id'],
        ];
    }


    public function messages(): array
    {
        return [
            'transaction_category_id.exists' => 'Selected parent category doesn’t exist or doesn’t match the chosen type.',
        ];
    }
}
