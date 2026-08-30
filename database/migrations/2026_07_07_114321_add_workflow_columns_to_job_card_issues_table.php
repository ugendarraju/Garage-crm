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
        Schema::table('job_card_issues', function (Blueprint $table) {
            $table->enum('reported_by', ['customer', 'mechanic'])->default('customer');
            $table->boolean('requires_consent')->default(false);
            $table->enum('consent_status', ['pending', 'approved', 'denied'])->nullable();
            $table->json('images')->nullable();
            $table->text('customer_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_card_issues', function (Blueprint $table) {
            $table->dropColumn([
                'reported_by',
                'requires_consent',
                'consent_status',
                'images',
                'customer_notes',
            ]);
        });
    }
};
