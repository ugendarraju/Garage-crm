<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DatabaseSchemaAutoFixService
{
    private static bool $checked = false;

    /**
     * Automatically ensure all required workshop enhancement columns and tables exist.
     * Prevents 500 errors on production servers without manual artisan migrations.
     */
    public static function ensureSchemaIsUpToDate(): void
    {
        if (self::$checked) {
            return;
        }
        self::$checked = true;

        try {
            // 1. Ensure columns in 'garages' table
            if (Schema::hasTable('garages')) {
                $garageMissing = [];
                if (!Schema::hasColumn('garages', 'default_service_reminder_days')) {
                    $garageMissing[] = function ($table) { $table->integer('default_service_reminder_days')->default(90); };
                }
                if (!Schema::hasColumn('garages', 'google_review_url')) {
                    $garageMissing[] = function ($table) { $table->string('google_review_url', 500)->nullable(); };
                }
                if (!Schema::hasColumn('garages', 'slug')) {
                    $garageMissing[] = function ($table) { $table->string('slug')->nullable(); };
                }
                if (!Schema::hasColumn('garages', 'tagline')) {
                    $garageMissing[] = function ($table) { $table->string('tagline')->nullable(); };
                }
                if (!Schema::hasColumn('garages', 'logo_path')) {
                    $garageMissing[] = function ($table) { $table->string('logo_path')->nullable(); };
                }
                if (!Schema::hasColumn('garages', 'header_logo_size')) {
                    $garageMissing[] = function ($table) { $table->integer('header_logo_size')->default(32); };
                }
                if (!Schema::hasColumn('garages', 'gst_number')) {
                    $garageMissing[] = function ($table) { $table->string('gst_number')->nullable(); };
                }
                if (!Schema::hasColumn('garages', 'bank_details')) {
                    $garageMissing[] = function ($table) { $table->text('bank_details')->nullable(); };
                }
                if (!Schema::hasColumn('garages', 'upi_id')) {
                    $garageMissing[] = function ($table) { $table->string('upi_id')->nullable(); };
                }
                if (!Schema::hasColumn('garages', 'qr_code_path')) {
                    $garageMissing[] = function ($table) { $table->string('qr_code_path')->nullable(); };
                }

                if (!empty($garageMissing)) {
                    Schema::table('garages', function ($table) use ($garageMissing) {
                        foreach ($garageMissing as $callback) {
                            $callback($table);
                        }
                    });
                }
            }

            // 2. Ensure columns in 'job_cards' table
            if (Schema::hasTable('job_cards')) {
                $jobCardMissing = [];
                if (!Schema::hasColumn('job_cards', 'inspection_checklist')) {
                    $jobCardMissing[] = function ($table) { $table->longText('inspection_checklist')->nullable(); };
                }
                if (!Schema::hasColumn('job_cards', 'service_reminder_sent_at')) {
                    $jobCardMissing[] = function ($table) { $table->timestamp('service_reminder_sent_at')->nullable(); };
                }
                if (!Schema::hasColumn('job_cards', 'next_service_reminder')) {
                    $jobCardMissing[] = function ($table) { $table->date('next_service_reminder')->nullable(); };
                }
                if (!Schema::hasColumn('job_cards', 'vehicle_running_km')) {
                    $jobCardMissing[] = function ($table) { $table->string('vehicle_running_km')->nullable(); };
                }
                if (!Schema::hasColumn('job_cards', 'fuel_range')) {
                    $jobCardMissing[] = function ($table) { $table->string('fuel_range')->nullable(); };
                }
                if (!Schema::hasColumn('job_cards', 'priority')) {
                    $jobCardMissing[] = function ($table) { $table->string('priority', 50)->default('Medium'); };
                }

                if (!empty($jobCardMissing)) {
                    Schema::table('job_cards', function ($table) use ($jobCardMissing) {
                        foreach ($jobCardMissing as $callback) {
                            $callback($table);
                        }
                    });
                }
            }

            // 3. Ensure 'expenses' table exists
            if (!Schema::hasTable('expenses')) {
                Schema::create('expenses', function ($table) {
                    $table->id();
                    $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
                    $table->string('category')->default('other');
                    $table->string('title');
                    $table->decimal('amount', 12, 2);
                    $table->string('payment_method')->default('cash');
                    $table->date('expense_date');
                    $table->string('receipt_path')->nullable();
                    $table->text('notes')->nullable();
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                    $table->timestamps();

                    $table->index(['garage_id', 'expense_date']);
                    $table->index(['garage_id', 'category']);
                });
            }
        } catch (\Throwable $e) {
            Log::warning('DatabaseSchemaAutoFixService warning: ' . $e->getMessage());
        }
    }
}
