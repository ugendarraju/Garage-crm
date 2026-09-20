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
            if (!Schema::hasColumn('garages', 'tagline')) {
                $table->string('tagline')->nullable()->after('name');
            }
            if (!Schema::hasColumn('garages', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('tagline');
            }
            if (!Schema::hasColumn('garages', 'header_logo_size')) {
                $table->integer('header_logo_size')->default(32)->after('logo_path');
            }
            if (!Schema::hasColumn('garages', 'gst_number')) {
                $table->string('gst_number')->nullable()->after('address');
            }
            if (!Schema::hasColumn('garages', 'bank_details')) {
                $table->text('bank_details')->nullable()->after('gst_number');
            }
            if (!Schema::hasColumn('garages', 'upi_id')) {
                $table->string('upi_id')->nullable()->after('bank_details');
            }
            if (!Schema::hasColumn('garages', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable()->after('upi_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('garages', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['tagline', 'logo_path', 'header_logo_size', 'gst_number', 'bank_details', 'upi_id', 'qr_code_path'] as $col) {
                if (Schema::hasColumn('garages', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
