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
        Schema::create('upcomming_expense_income_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

        
            $table->foreignId('eia_id')
                ->nullable()
                ->constrained('expense_income_accounts')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->date('date');
            $table->enum('type', ['income', 'expense']);
            $table->json('attachments')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upcomming_expense_income_accounts');
    }
};
