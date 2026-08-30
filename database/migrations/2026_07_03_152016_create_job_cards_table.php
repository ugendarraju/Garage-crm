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
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained()->onDelete('cascade');
            $table->string('job_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['scheduled', 'listed', 'working', 'complete', 'completed', 'delivered'])->default('listed');
            $table->date('expected_delivery_date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('advance_payment', 10, 2)->default(0.00);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('gst_amount', 10, 2)->default(0.00); // flat GST
            $table->decimal('cgst_amount', 10, 2)->default(0.00); // dynamic GST
            $table->decimal('sgst_amount', 10, 2)->default(0.00); // dynamic GST
            $table->decimal('igst_amount', 10, 2)->default(0.00); // dynamic GST
            $table->decimal('final_total', 10, 2)->default(0.00);
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_cards');
    }
};
