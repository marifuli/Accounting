<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
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
            'type'                  => ['required', 'string', 'in:income,expense,asset'],
            'name'                  => ['required', 'string', 'max:255'],
            'category_id'           => ['required', 'integer', 'exists:transaction_categories,id'],

            'from_account_id'       => ['required', 'integer', 'different:to_account_id', 'exists:accounts,id'],
            'send_actual_amount'    => ['required', 'numeric', 'min:0'],
            'send_total_amount'     => ['required', 'numeric', 'min:0', 'gte:send_actual_amount'],

            'to_account_id'         => ['required', 'integer', 'exists:accounts,id'],
            'receive_actual_amount' => ['required', 'numeric', 'min:0'],
            'receive_total_amount'  => ['required', 'numeric', 'min:0', 'gte:receive_actual_amount'],

            'description'           => ['nullable', 'string'],

            // attachments[] multiple files
            'attachments'           => ['nullable', 'array'],
            'attachments.*'         => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],

            // ---- FEES (arrays of objects) ----
            'source_fees'              => ['nullable', 'array'],
            'source_fees.*.name'       => ['nullable', 'string', 'max:255', 'required_with:source_fees.*.amount'],
            'source_fees.*.amount'     => ['nullable', 'numeric', 'min:0', 'required_with:source_fees.*.name'],

            'dest_fees'                => ['nullable', 'array'],
            'dest_fees.*.name'         => ['nullable', 'string', 'max:255', 'required_with:dest_fees.*.amount'],
            'dest_fees.*.amount'       => ['nullable', 'numeric', 'min:0', 'required_with:dest_fees.*.name'],
        ];
    }
}
