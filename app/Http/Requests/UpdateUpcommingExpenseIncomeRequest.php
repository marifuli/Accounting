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

            'attachments'   => ['nullable', 'array'],
            // 'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:5120'],
        ];
    }
}
