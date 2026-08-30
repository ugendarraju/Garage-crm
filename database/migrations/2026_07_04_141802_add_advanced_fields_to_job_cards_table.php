<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->enum('priority', ['Low', 'Medium', 'High'])->default('Medium')->after('status');
            $table->date('next_service_reminder')->nullable()->after('expected_delivery_date');
            $table->string('vehicle_running_km')->nullable()->after('next_service_reminder');
            $table->string('fuel_range')->nullable()->after('vehicle_running_km'); // Could be e.g. '1/4', '1/2', '3/4', 'Full'
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn(['priority', 'next_service_reminder', 'vehicle_running_km', 'fuel_range']);
        });
    }
};
