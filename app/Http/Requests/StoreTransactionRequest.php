<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // ✅ Make files visible to the validator (same idea you used in the "upcoming" request)
    protected function prepareForValidation(): void
    {
        // normalize numeric strings (optional)
        foreach (['send_actual_amount','send_total_amount','receive_actual_amount','receive_total_amount'] as $k) {
            if ($this->has($k)) {
                $this->merge([
                    $k => is_string($this->$k)
                        ? preg_replace('/[,\s]+/', '', $this->$k)
                        : $this->$k,
                ]);
            }
        }

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
        $type = $this->input('type');

        $fromRule = 'exists:accounts,id';
        $toRule   = 'exists:accounts,id';

        if ($type === 'income') {
            $fromRule = 'exists:expense_income_accounts,id';
            $toRule   = 'exists:accounts,id';
        } elseif ($type === 'expense') {
            $fromRule = 'exists:accounts,id';
            $toRule   = 'exists:expense_income_accounts,id';
        }

        return [
            'type'                  => ['required', 'string', 'in:income,expense,asset'],
            'name'                  => ['required', 'string', 'max:255'],
            'date'                  => ['nullable', 'string', 'max:255'],
            'category_id'           => ['required', 'integer', 'exists:transaction_categories,id'],
            'from_account_id'       => ['required', 'integer', $fromRule],
            'send_actual_amount'    => ['required', 'numeric', 'min:0'],
            'send_total_amount'     => ['required', 'numeric', /* if fees can exceed actual, remove min:0 */],
            'to_account_id'         => ['required', 'integer', $toRule],
            'receive_actual_amount' => ['required', 'numeric', 'min:0'],
            'receive_total_amount'  => ['required', 'numeric', /* if fees can exceed actual, remove min:0 */],
            'description'           => ['nullable', 'string'],

            // ✅ Files
            'attachments'           => ['nullable', 'array'],
            // Fees
            'source_fees'           => ['nullable', 'array'],
            'source_fees.*.name'    => ['nullable', 'string', 'max:255', 'required_with:source_fees.*.amount'],
            'source_fees.*.amount'  => ['nullable', 'numeric', 'min:0', 'required_with:source_fees.*.name'],
            'dest_fees'             => ['nullable', 'array'],
            'dest_fees.*.name'      => ['nullable', 'string', 'max:255', 'required_with:dest_fees.*.amount'],
            'dest_fees.*.amount'    => ['nullable', 'numeric', 'min:0', 'required_with:dest_fees.*.name'],
        ];
    }
}
