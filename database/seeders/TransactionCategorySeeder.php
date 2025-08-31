<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Food & Dining', 'type' => 'expense'],
            ['name' => 'Utilities', 'type' => 'expense'],
            ['name' => 'Rent', 'type' => 'expense'],
            ['name' => 'Transportation', 'type' => 'expense'],
            ['name' => 'Entertainment', 'type' => 'expense'],
            ['name' => 'Healthcare', 'type' => 'expense'],
            ['name' => 'Education', 'type' => 'expense'],
            ['name' => 'Shopping', 'type' => 'expense'],
            ['name' => 'Travel', 'type' => 'expense'],
            ['name' => 'Salary', 'type' => 'income'],
            ['name' => 'Business Income', 'type' => 'income'],
            ['name' => 'Investment Income', 'type' => 'income'],
            ['name' => 'Gifts & Donations', 'type' => 'income'],
            ['name' => 'Other Income', 'type' => 'income'],
        ];

        foreach ($categories as $category) {
            \App\Models\TransactionCategory::create(['name' => $category['name'], 'description' => $category['type']]);
        }
    }
}
