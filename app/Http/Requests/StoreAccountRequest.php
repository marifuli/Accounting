<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow anyone who can hit the route; gate in controller/route middleware if needed
        return true;
    }

    public function rules(): array
    {
        // Card brands exactly as in your migration enum
        $cardTypes = [
            'visa','mastercard','amex','discover','unionpay','jcb','diners_club','maestro','visa_electron',
            'rupay','verve','troy','mir','elo','hipercard','bancontact','interac','dankort','bc_card',
            'mada','eftpos','cartes_bancaires','uzcard','humo','other',
        ];

        return [
            'code'                => ['required','string','max:255','unique:accounts,code'],
            'swift_code'          => ['nullable','string','max:11', 'regex:/^[A-Za-z0-9]{8}([A-Za-z0-9]{3})?$/'], // 8 or 11
            'name'                => ['required','string','max:255'],
            'account_name'        => ['nullable','string','max:255'],

            'is_active'           => ['boolean'],
            'type'                => ['required', Rule::in(['bank','card','mobile'])],

            // Required only when type=card; otherwise nullable
            'card_type'           => ['nullable', Rule::in($cardTypes)],
            'account_number'      => ['nullable','string','max:255','unique:accounts,account_number'],
            'bank_name'           => ['nullable','string','max:255'],
            'bank_routing_number' => ['nullable','string','max:255'],

            'card_valid_from'     => ['nullable','date'],
            'card_expiry'         => ['nullable','date','after_or_equal:card_valid_from'],
            'card_cvv'            => ['nullable','string','max:10'],
            'card_pin'            => ['nullable','string','max:20'],

            'bank_iban'           => ['nullable','string','max:34','unique:accounts,bank_iban'],
            'bank_address'        => ['nullable','string','max:255'],
            'description'         => ['nullable','string'],

            // Uncomment if you added this column:
            'balance'          => ['nullable','numeric'],
        ];
    }

    public function withValidator($validator)
    {
        // Make card fields required if type=card
        $validator->sometimes(['card_type'], 'required', fn() => $this->input('type') === 'card');
    }

    protected function prepareForValidation(): void
    {
        // Normalize inputs: uppercase code; convert empty strings to null for nullable fields
        $nullable = [
            'swift_code','account_name','card_type','account_number','bank_name','bank_routing_number',
            'card_valid_from','card_expiry','card_cvv','card_pin','bank_iban','bank_address','description',
        ];

        $data = $this->all();

        if (isset($data['code'])) {
            $data['code'] = strtoupper(str_replace(' ', '-', $data['code']));
        }

        foreach ($nullable as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        $this->merge($data);
    }

    public function messages(): array
    {
        return [
            'swift_code.regex' => 'SWIFT must be 8 or 11 alphanumeric characters.',
        ];
    }
}
