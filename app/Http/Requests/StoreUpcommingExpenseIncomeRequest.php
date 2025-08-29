<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpcommingExpenseIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Make sure files are present on the "data" side for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->hasFile('attachments')) {
            $this->merge([
                'attachments' => array_values(
                    array_filter((array) $this->file('attachments'))
                ),
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

            // Files (multiple, optional)
            'attachments'   => ['nullable', 'array'],
            // 'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
        ];
    }
}
