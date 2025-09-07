<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // app/Http/Requests/UpdateTransactionRequest.php

    public function rules(): array
    {
        $typeInput = strtolower((string) $this->input('type'));
        $typeLong  = match ($typeInput) {
            'inc', 'income'  => 'income',
            'exp', 'expense' => 'expense',
            'ast', 'asset',  'asset' => 'asset',
            default          => 'asset',
        };

        $fromExists = $typeLong === 'income'
            ? Rule::exists('expense_income_accounts', 'id')
            : Rule::exists('accounts', 'id');

        $toExists = $typeLong === 'expense'
            ? Rule::exists('expense_income_accounts', 'id')
            : Rule::exists('accounts', 'id');

        return [
            'type'                  => ['required', 'string', 'in:income,expense,asset,inc,exp,ast'],

            'name'                  => ['required', 'string', 'max:255'],
            'category_id'           => ['required', 'integer', Rule::exists('transaction_categories', 'id')],

            'from_account_id'       => ['required', 'integer', 'different:to_account_id', $fromExists],
            'send_actual_amount'    => ['required', 'numeric', 'min:0'],
            // ✅ totals <= actual (fees subtract). Remove min:0 so negative totals are allowed if fees > actual.
            'send_total_amount'     => ['required', 'numeric', 'lte:send_actual_amount'],

            'to_account_id'         => ['required', 'integer', $toExists],
            'receive_actual_amount' => ['required', 'numeric', 'min:0'],
            'receive_total_amount'  => ['required', 'numeric', 'lte:receive_actual_amount'],

            'description'           => ['nullable', 'string'],

            'attachments'           => ['nullable', 'array'],
            'attachments.*'         => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],

            'source_fees'           => ['nullable', 'array'],
            'source_fees.*.name'    => ['nullable', 'string', 'max:255', 'required_with:source_fees.*.amount'],
            'source_fees.*.amount'  => ['nullable', 'numeric', 'min:0', 'required_with:source_fees.*.name'],

            'dest_fees'             => ['nullable', 'array'],
            'dest_fees.*.name'      => ['nullable', 'string', 'max:255', 'required_with:dest_fees.*.amount'],
            'dest_fees.*.amount'    => ['nullable', 'numeric', 'min:0', 'required_with:dest_fees.*.name'],
            'date'    => ['nullable', 'max:200'],
        ];
    }
}
