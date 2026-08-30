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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained()->onDelete('cascade');
            $table->string('part_name', 255);
            $table->string('part_code', 100)->unique()->nullable();
            $table->string('category', 100)->nullable();
            $table->integer('current_stock')->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('current_price', 10, 2);
            $table->string('barcode', 100)->nullable()->index();
            $table->string('location', 100)->nullable();
            $table->decimal('gst_rate', 5, 2)->default(18.00); // Specific GST for this part
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
