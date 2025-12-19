<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
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

        // Currencies exactly as in your migration enum
        $currencies = ['USD','EUR','GBP','JPY','AUD','CAD','CHF','CNY','INR','BRL','ZAR','BDT','other'];

        return [
            'code'                => ['required','string','max:255','unique:accounts,code'],
            'swift_code'          => ['nullable','string','max:11','regex:/^[A-Za-z0-9]{8}([A-Za-z0-9]{3})?$/'],
            'name'                => ['required','string','max:255'],
            'account_name'        => ['nullable','string','max:255'],

            'currency'            => ['required', Rule::in($currencies)],
            'opening_balance'     => ['nullable','numeric','decimal:0,2'],
            'current_balance'     => ['nullable','numeric','decimal:0,2'],

            'is_active'           => ['boolean'],
            'type'                => ['required', Rule::in(['bank','card','mobile'])],

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
            'app_password'         => ['nullable','string'],
            'app_pin'         => ['nullable','string'],
            'secret_note'         => ['nullable','string'],
        ];
    }

    public function withValidator($validator)
    {
        // Make card fields required if type=card (at least the brand)
        $validator->sometimes(['card_type'], 'required', fn () => $this->input('type') === 'card');
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'swift_code','account_name','card_type','account_number','bank_name','bank_routing_number',
            'card_valid_from','card_expiry','card_cvv','card_pin','bank_iban','bank_address','description',
        ];

        $data = $this->all();

        if (isset($data['code'])) {
            $data['code'] = strtoupper(str_replace(' ', '-', $data['code']));
        }
        if (isset($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
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
