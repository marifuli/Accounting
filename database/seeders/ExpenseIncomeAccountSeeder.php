<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseIncomeAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $incomeCategories = [
            "💼 Salary",
            "🎁 Bonus",
            "💵 Cash Deposit",
            "🏦 Bank Transfer",
            "🛒 Business Sales",
            "💻 Freelance",
            "📈 Investment Return",
            "🏠 Rental Income",
            "💸 Refund/Reimbursement",
            "🎉 Gift/Donation Received"
        ];
        $expenseCategories = [
            "🏠 Rent",
            "⚡ Utilities",
            "🛒 Groceries",
            "🍽️ Dining Out",
            "🚕 Transport",
            "⛽ Fuel",
            "💊 Healthcare",
            "🎓 Education",
            "🎮 Entertainment",
            "💳 Loan/EMI Payment",
            "✈️ Travel",
            "📱 Subscriptions",
            "🛠️ Maintenance/Repairs",
            "👕 Clothing",
            "🎁 Gifts/Donations"
        ];
        foreach ($incomeCategories as $category) {
            \App\Models\ExpenseIncomeAccount::create([
                'name' => $category,
                'type' => 'income',
            ]);
        }
        foreach ($expenseCategories as $category) {
            \App\Models\ExpenseIncomeAccount::create([
                'name' => $category,
                'type' => 'expense',
            ]);
        }
    }
}
