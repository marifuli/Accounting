<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $accounts = [
            // --- Bank accounts ---
            [
                'code' => 'ACCT-SONALI-001',
                'swift_code' => 'BSONBDDH',
                'name' => 'Sonali Bank PLC',
                'account_name' => 'Ops Account',
                'is_active' => true,
                'type' => 'bank',
                'card_type' => null,
                'account_number' => '100000000001',
                'bank_name' => 'Sonali Bank PLC',
                'bank_routing_number' => '090100001',
                'card_valid_from' => null,
                'card_expiry' => null,
                'card_cvv' => null,
                'card_pin' => null,
                'bank_iban' => null,
                'bank_address' => 'Dhaka, Bangladesh',
                'description' => 'Primary ops account.',
            ],
            [
                'code' => 'ACCT-DBBL-001',
                'swift_code' => 'DBBLBDDH',
                'name' => 'Dutch-Bangla Bank Ltd.',
                'account_name' => 'Savings Account',
                'is_active' => true,
                'type' => 'bank',
                'card_type' => null,
                'account_number' => '100000000002',
                'bank_name' => 'Dutch-Bangla Bank Ltd.',
                'bank_routing_number' => '050100001',
                'card_valid_from' => null,
                'card_expiry' => null,
                'card_cvv' => null,
                'card_pin' => null,
                'bank_iban' => null,
                'bank_address' => 'Dhaka, Bangladesh',
                'description' => 'Primary savings account.',
            ],
        ];

        // Add timestamps and perform upsert on "code"
        $payload = array_map(function ($a) use ($now) {
            return array_merge($a, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $accounts);

        DB::table('accounts')->upsert(
            $payload,
            ['code'], // unique key
            [
                'swift_code',
                'name',
                'account_name',
                'is_active',
                'type',
                'card_type',
                'account_number',
                'bank_name',
                'bank_routing_number',
                'card_valid_from',
                'card_expiry',
                'card_cvv',
                'card_pin',
                'bank_iban',
                'bank_address',
                'description',
                'updated_at',
            ]
        );
    }
}
