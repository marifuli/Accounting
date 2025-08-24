<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateUpcommingExpenseIncomeRequest extends FormRequest
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
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'eia_id'      => ['nullable', 'integer', 'exists:expense_income_accounts,id'],
            'date'        => ['required', 'date'],
            'type'        => ['required', Rule::in(['income', 'expense'])],

            // Optional multiple files
            'attachments'   => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'], // 5MB each
        ];
    }
}
