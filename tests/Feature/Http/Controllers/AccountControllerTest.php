<?php

use App\Models\User;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

// ✅ Authenticate a demo user before each test (routes protected by auth)
beforeEach(function () {
    $this->user = User::factory()->create();   // uses default web guard
    $this->actingAs($this->user);
});

function validPayload(array $overrides = []): array
{
    // Minimal valid payload per StoreAccountRequest rules
    $base = [
        'code' => 'acc ' . Str::upper(Str::random(4)), // will normalize to UPPERCASE + hyphens
        'swift_code' => 'ABCDEFGH',                     // 8 alnum (valid)
        'name' => 'My Checking',
        'account_name' => 'Personal',
        'currency' => 'bdt',                           // will normalize to "BDT"
        'opening_balance' => '250.50',
        'current_balance' => null,                     // controller defaults to opening_balance
        'is_active' => true,
        'type' => 'bank',
        'card_type' => null,                           // required only if type=card
        'account_number' => null,
        'bank_name' => 'Sample Bank',
        'bank_routing_number' => 'RB01',
        'card_valid_from' => null,
        'card_expiry' => null,
        'card_cvv' => null,
        'card_pin' => null,
        'bank_iban' => null,
        'bank_address' => null,
        'description' => 'Test account',
    ];

    return array_merge($base, $overrides);
}

test('index shows Accounts/Index with paginated accounts', function () {
    // Create via HTTP to exercise requests + controller normalization
    for ($i = 1; $i <= 12; $i++) {
        $this->post(route('accounts.store'), validPayload([
            'code' => "acc $i",
            'name' => "Acc $i",
        ]))->assertRedirect(route('accounts.index'));
    }

    $this->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) =>
            $page->component('Accounts/Index')
                 ->has('accounts.data', 10)           // paginate(10)
                 ->where('accounts.meta.total', 12)
        );
});

test('create page renders Accounts/Create', function () {
    $this->get(route('accounts.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Accounts/Create'));
});

test('store creates an account, normalizes code/currency, and defaults current_balance to opening_balance', function () {
    $payload = validPayload([
        'code' => 'ab 123',          // -> "AB-123"
        'currency' => 'usd',         // -> "USD"
        'opening_balance' => '100.00',
        'current_balance' => null,   // defaults to opening_balance
    ]);

    $this->post(route('accounts.store'), $payload)
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('success', 'Account created successfully.');

    $this->assertDatabaseHas('accounts', [
        'code' => 'AB-123',
        'currency' => 'USD',
        'name' => 'My Checking',
        'opening_balance' => '100.00',
        'current_balance' => '100.00',
        'is_active' => 1,
        'type' => 'bank',
    ]);
});

test('store sets both balances to 0.00 when omitted', function () {
    $payload = validPayload([
        'opening_balance' => null,
        'current_balance' => null,
    ]);

    $this->post(route('accounts.store'), $payload)
        ->assertRedirect(route('accounts.index'));

    $acc = Account::latest('id')->first();
    expect($acc->opening_balance)->toEqual('0.00');
    expect($acc->current_balance)->toEqual('0.00');
});

test('store validates: type=card requires card_type', function () {
    $payload = validPayload(['type' => 'card', 'card_type' => null]);

    $this->from(route('accounts.create'))
        ->post(route('accounts.store'), $payload)
        ->assertSessionHasErrors(['card_type'])
        ->assertRedirect();
});

test('store validates swift_code format and currency enum', function () {
    // bad swift_code
    $badSwift = validPayload(['swift_code' => 'ABC']);
    $this->from(route('accounts.create'))
        ->post(route('accounts.store'), $badSwift)
        ->assertSessionHasErrors(['swift_code'])
        ->assertRedirect();

    // bad currency
    $badCurrency = validPayload(['currency' => 'XXX']);
    $this->from(route('accounts.create'))
        ->post(route('accounts.store'), $badCurrency)
        ->assertSessionHasErrors(['currency'])
        ->assertRedirect();
});

test('store validates decimal precision for balances (0-2)', function () {
    $payload = validPayload(['opening_balance' => '12.345']); // too many decimals
    $this->from(route('accounts.create'))
        ->post(route('accounts.store'), $payload)
        ->assertSessionHasErrors(['opening_balance'])
        ->assertRedirect();
});

test('store enforces unique constraints (code, account_number, bank_iban)', function () {
    $this->post(route('accounts.store'), validPayload([
        'code' => 'DUP CODE',
        'account_number' => 'ACC-001',
        'bank_iban' => 'IBAN-001',
    ]))->assertRedirect(route('accounts.index'));

    $dup = validPayload([
        'code' => 'DUP CODE',        // same
        'account_number' => 'ACC-001',
        'bank_iban' => 'IBAN-001',
    ]);

    $this->from(route('accounts.create'))
        ->post(route('accounts.store'), $dup)
        ->assertSessionHasErrors(['code', 'account_number', 'bank_iban'])
        ->assertRedirect();
});

test('show page renders Accounts/Show with account prop', function () {
    $this->post(route('accounts.store'), validPayload(['code' => 'acc show']));
    $account = Account::first();

    $this->get(route('accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) =>
            $page->component('Accounts/Show')
                 ->where('account.id', $account->id)
        );
});

test('edit page renders Accounts/Edit with account prop', function () {
    $this->post(route('accounts.store'), validPayload(['code' => 'acc edit']));
    $account = Account::first();

    $this->get(route('accounts.edit', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) =>
            $page->component('Accounts/Edit')
                 ->where('account.id', $account->id)
        );
});

test('update modifies fields, normalizes code/currency, ignores unique on same record, and does not overwrite current_balance if omitted', function () {
    // Seed record
    $this->post(route('accounts.store'), validPayload([
        'code' => 'acc 001',
        'currency' => 'bdt',
        'opening_balance' => '250.50',
        'current_balance' => null,     // -> 250.50
        'account_number' => 'ACC-NO-1',
        'bank_iban' => 'IBAN-1',
    ]));
    $account = Account::first();
    expect($account->current_balance)->toEqual('250.50');

    // Update (omit current_balance; keep same unique values)
    $payload = [
        'code' => 'xy 999',            // -> "XY-999"
        'swift_code' => 'ABCDEFGH',
        'name' => 'Updated Name',
        'account_name' => 'Updated Personal',
        'currency' => 'eur',           // -> "EUR"
        'opening_balance' => '250.50',
        // no current_balance
        'is_active' => false,          // handled by UpdateAccountRequest prepareForValidation()
        'type' => 'bank',
        'account_number' => 'ACC-NO-1',// unique:ignore on same id
        'bank_iban' => 'IBAN-1',       // unique:ignore on same id
        'bank_name' => 'Updated Bank',
        'bank_routing_number' => 'RB02',
        'description' => 'Updated desc',
    ];

    $this->put(route('accounts.update', $account), $payload)
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('success', 'Account updated successfully.');

    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'code' => 'XY-999',
        'currency' => 'EUR',
        'name' => 'Updated Name',
        'is_active' => 0,
        'current_balance' => '250.50', // unchanged because omitted
    ]);
});

test('update validates card_type when switching to card', function () {
    $this->post(route('accounts.store'), validPayload(['code' => 'acc 777']));
    $account = Account::first();

    $this->from(route('accounts.edit', $account))
        ->put(route('accounts.update', $account), [
            'code' => 'acc 777',
            'swift_code' => 'ABCDEFGH',
            'name' => 'Now Card',
            'currency' => 'usd',
            'type' => 'card',          // requires card_type
            // 'card_type' omitted
        ])
        ->assertSessionHasErrors(['card_type'])
        ->assertRedirect();
});

test('update validates card_expiry after_or_equal:card_valid_from', function () {
    $this->post(route('accounts.store'), validPayload(['code' => 'acc card']));
    $account = Account::first();

    $this->from(route('accounts.edit', $account))
        ->put(route('accounts.update', $account), [
            'code' => 'acc card',
            'swift_code' => 'ABCDEFGH',
            'name' => 'Card Dates',
            'currency' => 'usd',
            'type' => 'card',
            'card_type' => 'visa',
            'card_valid_from' => '2025-12-31',
            'card_expiry' => '2025-01-01', // before valid_from
        ])
        ->assertSessionHasErrors(['card_expiry'])
        ->assertRedirect();
});

test('destroy deletes the account and redirects', function () {
    $this->post(route('accounts.store'), validPayload(['code' => 'acc del']));
    $account = Account::first();

    $this->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.index'));

    $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    // Controller flashes with key '204' (nonstandard), assert if you want:
    // ->assertSessionHas('204', 'Account deleted successfully.');
});

// (Optional) sanity check: guest is redirected to login (proves auth middleware)
// Remove if not desired.
test('guest cannot access index (redirect to login)', function () {
    auth()->logout();
    $this->get(route('accounts.index'))->assertRedirect(route('login'));
});
