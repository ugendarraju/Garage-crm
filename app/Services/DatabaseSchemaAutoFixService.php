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
                $garageCols = [
                    'default_service_reminder_days' => fn($t) => $t->integer('default_service_reminder_days')->default(90),
                    'google_review_url' => fn($t) => $t->string('google_review_url', 500)->nullable(),
                    'slug' => fn($t) => $t->string('slug')->nullable(),
                    'tagline' => fn($t) => $t->string('tagline')->nullable(),
                    'logo_path' => fn($t) => $t->string('logo_path')->nullable(),
                    'header_logo_size' => fn($t) => $t->integer('header_logo_size')->default(32),
                    'gst_number' => fn($t) => $t->string('gst_number')->nullable(),
                    'bank_details' => fn($t) => $t->text('bank_details')->nullable(),
                    'upi_id' => fn($t) => $t->string('upi_id')->nullable(),
                    'qr_code_path' => fn($t) => $t->string('qr_code_path')->nullable(),
                ];

                foreach ($garageCols as $col => $definition) {
                    try {
                        if (!Schema::hasColumn('garages', $col)) {
                            Schema::table('garages', function ($table) use ($definition) {
                                $definition($table);
                            });
                        }
                    } catch (\Throwable $e) {
                        Log::warning("AutoFix: Failed to add garages.{$col}: " . $e->getMessage());
                    }
                }
            }

            // 2. Ensure columns in 'job_cards' table
            if (Schema::hasTable('job_cards')) {
                $jobCardCols = [
                    'inspection_checklist' => fn($t) => $t->longText('inspection_checklist')->nullable(),
                    'service_reminder_sent_at' => fn($t) => $t->timestamp('service_reminder_sent_at')->nullable(),
                    'next_service_reminder' => fn($t) => $t->date('next_service_reminder')->nullable(),
                    'vehicle_running_km' => fn($t) => $t->string('vehicle_running_km')->nullable(),
                    'fuel_range' => fn($t) => $t->string('fuel_range')->nullable(),
                    'priority' => fn($t) => $t->string('priority', 50)->default('Medium'),
                ];

                foreach ($jobCardCols as $col => $definition) {
                    try {
                        if (!Schema::hasColumn('job_cards', $col)) {
                            Schema::table('job_cards', function ($table) use ($definition) {
                                $definition($table);
                            });
                        }
                    } catch (\Throwable $e) {
                        Log::warning("AutoFix: Failed to add job_cards.{$col}: " . $e->getMessage());
                    }
                }
            }

            // 3. Ensure 'expenses' table exists
            try {
                if (!Schema::hasTable('expenses')) {
                    Schema::create('expenses', function ($table) {
                        $table->id();
                        $table->unsignedBigInteger('garage_id')->index();
                        $table->string('category')->default('other');
                        $table->string('title');
                        $table->decimal('amount', 12, 2);
                        $table->string('payment_method')->default('cash');
                        $table->date('expense_date');
                        $table->string('receipt_path')->nullable();
                        $table->text('notes')->nullable();
                        $table->unsignedBigInteger('created_by')->nullable()->index();
                        $table->timestamps();

                        $table->index(['garage_id', 'expense_date']);
                        $table->index(['garage_id', 'category']);
                    });
                } else {
                    if (!Schema::hasColumn('expenses', 'receipt_path')) {
                        try {
                            Schema::table('expenses', function ($table) {
                                $table->string('receipt_path')->nullable();
                            });
                        } catch (\Throwable $e) {
                            Log::warning('AutoFix: Failed to add expenses.receipt_path: ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('AutoFix: Failed to ensure expenses table: ' . $e->getMessage());
            }

            // 4. Ensure 'action_logs' table exists
            try {
                if (!Schema::hasTable('action_logs')) {
                    Schema::create('action_logs', function ($table) {
                        $table->id();
                        $table->unsignedBigInteger('garage_id')->nullable()->index();
                        $table->unsignedBigInteger('user_id')->nullable()->index();
                        $table->string('user_name')->nullable();
                        $table->string('user_role')->nullable();
                        $table->string('action', 50)->index();
                        $table->string('module', 50)->index();
                        $table->text('description');
                        $table->json('details')->nullable();
                        $table->string('ip_address', 45)->nullable();
                        $table->timestamps();

                        $table->index(['garage_id', 'created_at']);
                        $table->index(['garage_id', 'module']);
                    });
                }
            } catch (\Throwable $e) {
                Log::warning('AutoFix: Failed to ensure action_logs table: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            Log::warning('DatabaseSchemaAutoFixService warning: ' . $e->getMessage());
        }
    }
}

