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
            if (!Schema::hasColumn('garages', 'default_service_reminder_days')) {
                $table->integer('default_service_reminder_days')->default(90)->after('slug');
            }
            if (!Schema::hasColumn('garages', 'google_review_url')) {
                $table->string('google_review_url', 500)->nullable()->after('default_service_reminder_days');
            }
        });

        Schema::table('job_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('job_cards', 'inspection_checklist')) {
                $table->json('inspection_checklist')->nullable()->after('description');
            }
            if (!Schema::hasColumn('job_cards', 'service_reminder_sent_at')) {
                $table->timestamp('service_reminder_sent_at')->nullable()->after('next_service_reminder');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('garages', function (Blueprint $table) {
            if (Schema::hasColumn('garages', 'default_service_reminder_days')) {
                $table->dropColumn('default_service_reminder_days');
            }
            if (Schema::hasColumn('garages', 'google_review_url')) {
                $table->dropColumn('google_review_url');
            }
        });

        Schema::table('job_cards', function (Blueprint $table) {
            if (Schema::hasColumn('job_cards', 'inspection_checklist')) {
                $table->dropColumn('inspection_checklist');
            }
            if (Schema::hasColumn('job_cards', 'service_reminder_sent_at')) {
                $table->dropColumn('service_reminder_sent_at');
            }
        });
    }
};
