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
        Schema::table('garages', function (Blueprint $table) {
            $table->string('gst_number')->nullable();
            $table->text('bank_details')->nullable();
            $table->string('upi_id')->nullable();
            $table->string('qr_code_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('garages', function (Blueprint $table) {
            $table->dropColumn([
                'tagline', 'logo_path', 'address', 'gst_number', 
                'bank_details', 'upi_id', 'qr_code_path'
            ]);
        });
    }
};
