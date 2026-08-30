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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('registration_number', 20)->unique();
            $table->string('chassis_number', 50)->nullable();
            $table->string('engine_number', 50)->nullable();
            $table->foreignId('brand_id')->constrained('vehicle_brands');
            $table->foreignId('model_id')->constrained('vehicle_models');
            $table->integer('year')->nullable();
            $table->enum('fuel_type', ['Petrol', 'Diesel', 'Electric', 'CNG', 'Hybrid'])->default('Petrol');
            $table->string('vehicle_type', 50)->default('Motorcycle');
            $table->string('color', 50)->nullable();
            $table->integer('odometer_reading')->default(0);
            $table->date('insurance_expiry')->nullable();
            $table->date('fitness_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
