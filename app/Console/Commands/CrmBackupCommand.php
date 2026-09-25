<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CrmBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:backup {--garage= : The ID of the specific garage to backup} {--label= : A custom label or description for this backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a complete or single-garage backup archive with database records and file attachments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $garageId = $this->option('garage') ? (int) $this->option('garage') : null;
        $label = $this->option('label') ?: '';

        $this->info($garageId ? "Starting backup for Garage ID: {$garageId}..." : "Starting Full CRM System Backup...");

        try {
            $result = BackupService::createBackup($garageId, $label);
            $this->info("Backup completed successfully!");
            $this->line("Archive: <comment>{$result['filename']}</comment>");
            $this->line("Size:    <comment>" . round($result['size'] / 1024, 1) . " KB</comment>");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
