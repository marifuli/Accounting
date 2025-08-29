<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUpcommingExpenseIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalize amount like "1,234.50" -> "1234.50"
        if ($this->has('amount')) {
            $this->merge([
                'amount' => is_string($this->amount)
                    ? preg_replace('/[,\s]+/', '', $this->amount)
                    : $this->amount,
            ]);
        }

        // Normalize to an array of UploadedFile so 'attachments.*' rules work
        if ($this->hasFile('attachments')) {
            $this->merge([
                'attachments' => array_values(array_filter((array) $this->file('attachments'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'eia_id'      => ['nullable', 'integer', 'exists:expense_income_accounts,id'],
            'date'        => ['required', 'date'],
            'type'        => ['required', Rule::in(['income', 'expense'])],

            // NEW
            'amount'   => ['required', 'numeric', 'gte:0', 'lte:999999999999.99'], // DECIMAL(15,2)
            'currency' => ['required', Rule::in([
                'USD','EUR','GBP','JPY','AUD','CAD','CHF','CNY','INR','BRL','ZAR','BDT','other'
            ])],

            'attachments'   => ['nullable', 'array'],
            // 'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:5120'],
        ];
    }
}
