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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->enum('type', ['inc', 'exp', 'asset']);

            // Relations
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('transaction_categories')
                  ->nullOnDelete();

            $table->foreignId('from_account_id')
                  ->nullable()
                  ->constrained('accounts')
                  ->nullOnDelete();

            $table->foreignId('to_account_id')
                  ->nullable()
                  ->constrained('accounts')
                  ->nullOnDelete();

            // Details
            $table->text('description')->nullable();

            // Multiple files stored as JSON array of paths
            $table->json('attachments')->nullable();

            // Amounts
            $table->decimal('send_total_amount', 15, 2)->default(0);
            $table->decimal('send_actual_amount', 15, 2)->default(0);
            $table->decimal('receive_total_amount', 15, 2)->default(0);
            $table->decimal('receive_actual_amount', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
