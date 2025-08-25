<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expense_income_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->enum('currency', ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'INR', 'BRL', 'ZAR', 'BDT', 'other'])->default('USD');         // ISO 4217 currency code
            $table->enum('type', ['expense', 'income']);
            $table->foreignId('transaction_category_id')
                ->nullable()
                ->constrained('transaction_categories')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_income_accounts');
    }
};
