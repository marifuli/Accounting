<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        $now = now();

        $banks = [
            // State-owned commercial banks
            ['name' => 'Sonali Bank PLC',                       'code' => 'SONALI',     'is_mobile' => false],
            ['name' => 'Janata Bank PLC',                       'code' => 'JANATA',     'is_mobile' => false],
            ['name' => 'Agrani Bank PLC',                       'code' => 'AGRANI',     'is_mobile' => false],
            ['name' => 'Rupali Bank PLC',                       'code' => 'RUPALI',     'is_mobile' => false],

            // Specialized / development banks
            ['name' => 'Bangladesh Krishi Bank',                'code' => 'BKB',        'is_mobile' => false],
            ['name' => 'Rajshahi Krishi Unnayan Bank',          'code' => 'RAKUB',      'is_mobile' => false],
            ['name' => 'Bangladesh Development Bank',           'code' => 'BDBL',       'is_mobile' => false],
            ['name' => 'BASIC Bank',                            'code' => 'BASIC',      'is_mobile' => false],

            // Private commercial banks
            ['name' => 'Islami Bank Bangladesh PLC',            'code' => 'IBBL',       'is_mobile' => false],
            ['name' => 'Dutch-Bangla Bank PLC',                 'code' => 'DBBL',       'is_mobile' => false],
            ['name' => 'BRAC Bank PLC',                         'code' => 'BRAC',       'is_mobile' => false],
            ['name' => 'The City Bank PLC',                     'code' => 'CITY',       'is_mobile' => false],
            ['name' => 'Prime Bank PLC',                        'code' => 'PRIME',      'is_mobile' => false],
            ['name' => 'Eastern Bank PLC',                      'code' => 'EBL',        'is_mobile' => false],
            ['name' => 'AB Bank PLC',                           'code' => 'ABBL',       'is_mobile' => false],
            ['name' => 'Bank Asia PLC',                         'code' => 'BANKASIA',   'is_mobile' => false],
            ['name' => 'United Commercial Bank PLC',            'code' => 'UCB',        'is_mobile' => false],
            ['name' => 'Southeast Bank PLC',                    'code' => 'SEBL',       'is_mobile' => false],
            ['name' => 'Trust Bank PLC',                        'code' => 'TBL',        'is_mobile' => false],
            ['name' => 'Mercantile Bank PLC',                   'code' => 'MBL',        'is_mobile' => false],
            ['name' => 'NCC Bank PLC',                          'code' => 'NCC',        'is_mobile' => false],
            ['name' => 'One Bank PLC',                          'code' => 'ONE',        'is_mobile' => false],
            ['name' => 'EXIM Bank PLC',                         'code' => 'EXIM',       'is_mobile' => false],
            ['name' => 'Social Islami Bank PLC',                'code' => 'SIBL',       'is_mobile' => false],
            ['name' => 'Shahjalal Islami Bank PLC',             'code' => 'SJIBL',      'is_mobile' => false],
            ['name' => 'Al-Arafah Islami Bank PLC',             'code' => 'AIBL',       'is_mobile' => false],
            ['name' => 'Jamuna Bank PLC',                       'code' => 'JBL',        'is_mobile' => false],
            ['name' => 'Mutual Trust Bank PLC',                 'code' => 'MTB',        'is_mobile' => false],
            ['name' => 'Premier Bank PLC',                      'code' => 'PREMIER',    'is_mobile' => false],
            ['name' => 'IFIC Bank PLC',                         'code' => 'IFIC',       'is_mobile' => false],
            ['name' => 'Standard Bank PLC',                     'code' => 'SBL',        'is_mobile' => false],
            ['name' => 'NRB Commercial Bank PLC',               'code' => 'NRBC',       'is_mobile' => false],
            ['name' => 'NRB Bank PLC',                          'code' => 'NRB',        'is_mobile' => false],
            ['name' => 'Midland Bank PLC',                      'code' => 'MIDLAND',    'is_mobile' => false],
            ['name' => 'Meghna Bank PLC',                       'code' => 'MEGHNA',     'is_mobile' => false],
            ['name' => 'Modhumoti Bank PLC',                    'code' => 'MODHUMOTI',  'is_mobile' => false],
            ['name' => 'Community Bank Bangladesh PLC',         'code' => 'CBBL',       'is_mobile' => false],
            ['name' => 'Union Bank PLC',                        'code' => 'UNION',      'is_mobile' => false],

            // Foreign banks operating in BD
            ['name' => 'Standard Chartered Bank',               'code' => 'SCB',        'is_mobile' => false],
            ['name' => 'HSBC Bangladesh',                       'code' => 'HSBC',       'is_mobile' => false],
            ['name' => 'Citibank N.A. Bangladesh',              'code' => 'CITI',       'is_mobile' => false],

            // Mobile Financial Services (MFS)
            ['name' => 'bKash',                                 'code' => 'BKASH',      'is_mobile' => true],
            ['name' => 'Nagad',                                 'code' => 'NAGAD',      'is_mobile' => true],
            ['name' => 'Rocket',                                'code' => 'ROCKET',     'is_mobile' => true], // DBBL MFS
            ['name' => 'Upay',                                  'code' => 'UPAY',       'is_mobile' => true], // UCB MFS
            ['name' => 'mCash',                                 'code' => 'MCASH',      'is_mobile' => true], // IBBL MFS
            ['name' => 'OK Wallet',                             'code' => 'OKWL',       'is_mobile' => true], // ONE Bank MFS
            ['name' => 'tap (Trust Axiata Pay)',                'code' => 'TAP',        'is_mobile' => true], // PSP/MFS
        ];

        $payload = collect($banks)->map(fn ($b) => array_merge($b, [
            'is_active'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]))->all();

        DB::table('banks')->upsert(
            $payload,
            ['code'],                         // unique key
            ['name', 'is_active', 'is_mobile', 'updated_at'] // fields to update on conflict
        );
    }
}
