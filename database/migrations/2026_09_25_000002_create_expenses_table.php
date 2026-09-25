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
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
                $table->string('category')->default('other'); // rent, utilities, food_tea, consumables, tools, salary_advance, maintenance, other
                $table->string('title');
                $table->decimal('amount', 12, 2);
                $table->string('payment_method')->default('cash'); // cash, upi, bank_transfer
                $table->date('expense_date');
                $table->string('receipt_path')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['garage_id', 'expense_date']);
                $table->index(['garage_id', 'category']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
