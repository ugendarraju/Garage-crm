<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\JobCardItem;
use App\Models\JobCardIssue;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Inventory;
use App\Models\PartCategory;
use App\Models\PartBrand;
use App\Models\Expense;
use App\Models\Coupon;
use App\Models\Setting;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BackupService
{
    protected static string $backupDir = 'backups';

    /**
     * Get absolute path to the backups directory.
     */
    public static function getBackupDirectory(): string
    {
        $dir = storage_path('app/' . self::$backupDir);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * List all available backup archives.
     */
    public static function listBackups(): array
    {
        $dir = self::getBackupDirectory();
        $files = glob($dir . '/*.zip') ?: [];
        $backups = [];

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $sizeBytes = filesize($filePath);
            $modifiedTime = filemtime($filePath);

            $manifest = self::readManifestFromZip($filePath);

            $backups[] = [
                'filename' => $filename,
                'created_at' => Carbon::createFromTimestamp($modifiedTime)->toDateTimeString(),
                'size_bytes' => $sizeBytes,
                'size_formatted' => self::formatBytes($sizeBytes),
                'type' => $manifest['type'] ?? (str_contains($filename, 'full') ? 'full' : 'single_garage'),
                'garages' => $manifest['garages'] ?? [],
                'crm_version' => $manifest['crm_version'] ?? '2.0',
            ];
        }

        // Sort latest first
        usort($backups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * Read the manifest.json from a zip archive.
     */
    protected static function readManifestFromZip(string $filePath): ?array
    {
        if (!class_exists('ZipArchive')) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $content = $zip->getFromName('manifest.json');
            $zip->close();
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * Format byte sizes into readable string.
     */
    protected static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Create a backup (full CRM or single garage).
     */
    public static function createBackup(?int $garageId = null, string $label = ''): array
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('PHP ZipArchive extension is not enabled on this server.');
        }

        $dir = self::getBackupDirectory();
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $targetGarages = $garageId ? Garage::where('id', $garageId)->get() : Garage::all();

        if ($targetGarages->isEmpty()) {
            throw new \Exception('No garage found to back up.');
        }

        $isFull = $garageId === null;
        $garageSlug = $isFull ? 'full' : ($targetGarages->first()->slug ?: Str::slug($targetGarages->first()->name ?: 'garage-' . $garageId));
        $filename = "backup-{$garageSlug}-{$timestamp}.zip";
        $zipFilePath = $dir . DIRECTORY_SEPARATOR . $filename;

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Cannot open zip archive at {$zipFilePath}");
        }

        $garagesMeta = [];

        // 1. Export each garage's database records and physical uploads
        foreach ($targetGarages as $garage) {
            $gSlug = $garage->slug ?: Str::slug($garage->name ?: 'garage-' . $garage->id);
            $garagesMeta[] = [
                'id' => $garage->id,
                'name' => $garage->name,
                'slug' => $gSlug,
            ];

            // Extract garage DB data
            $garageData = self::extractGarageData($garage);
            $zip->addFromString("garages/{$gSlug}/data.json", json_encode($garageData, JSON_PRETTY_PRINT));

            // Pack physical uploads
            $garageStorageFolder = storage_path("app/public/garages/{$gSlug}");
            if (is_dir($garageStorageFolder)) {
                self::addFolderToZip($garageStorageFolder, $zip, "garages/{$gSlug}/files");
            }
        }

        // 2. Export global system data if full backup
        if ($isFull) {
            $systemData = [
                'settings' => SystemSettingService::getAll(),
                'vehicle_brands' => VehicleBrand::all()->toArray(),
                'vehicle_models' => VehicleModel::all()->toArray(),
            ];
            $zip->addFromString("system.json", json_encode($systemData, JSON_PRETTY_PRINT));
        }

        // 3. Add manifest.json
        $manifest = [
            'type' => $isFull ? 'full' : 'single_garage',
            'created_at' => Carbon::now()->toDateTimeString(),
            'crm_version' => '2.0',
            'label' => $label ?: ($isFull ? 'Full System Backup' : $targetGarages->first()->name),
            'garages' => $garagesMeta,
        ];
        $zip->addFromString("manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

        $zip->close();

        // Update system settings record of last backup
        SystemSettingService::setMany([
            'last_backup_at' => Carbon::now()->toDateTimeString(),
        ]);

        // Prune old backups according to retention count
        $retention = (int) SystemSettingService::get('backup_retention', 5);
        self::pruneOldBackups($retention);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $zipFilePath,
            'size' => filesize($zipFilePath),
        ];
    }

    /**
     * Extract all relational records for a specific garage.
     */
    protected static function extractGarageData(Garage $garage): array
    {
        $id = $garage->id;

        // Job card IDs
        $jobCardIds = JobCard::where('garage_id', $id)->pluck('id');
        $purchaseInvoiceIds = PurchaseInvoice::where('garage_id', $id)->pluck('id');

        // All users associated with this garage (staff, owners, and customers)
        $customerUserIds = Customer::where('garage_id', $id)->pluck('user_id')->filter();
        $garageUserIds = User::where('garage_id', $id)->pluck('id');
        $allUserIds = $garageUserIds->merge($customerUserIds)->unique();

        return [
            'garage' => $garage->toArray(),
            'users' => User::whereIn('id', $allUserIds)->get()->toArray(),
            'customers' => Customer::where('garage_id', $id)->withTrashed()->get()->toArray(),
            'vehicles' => Vehicle::where('garage_id', $id)->get()->toArray(),
            'job_cards' => JobCard::where('garage_id', $id)->get()->toArray(),
            'job_card_items' => JobCardItem::whereIn('job_card_id', $jobCardIds)->get()->toArray(),
            'job_card_issues' => JobCardIssue::whereIn('job_card_id', $jobCardIds)->get()->toArray(),
            'job_card_mechanics' => DB::table('job_card_mechanic')->whereIn('job_card_id', $jobCardIds)->get()->map(fn($r) => (array)$r)->toArray(),
            'service_requests' => ServiceRequest::where('garage_id', $id)->get()->toArray(),
            'service_types' => ServiceType::where('garage_id', $id)->get()->toArray(),
            'suppliers' => Supplier::where('garage_id', $id)->get()->toArray(),
            'purchase_invoices' => PurchaseInvoice::where('garage_id', $id)->get()->toArray(),
            'purchase_invoice_items' => PurchaseInvoiceItem::whereIn('purchase_invoice_id', $purchaseInvoiceIds)->get()->toArray(),
            'inventory' => Inventory::where('garage_id', $id)->get()->toArray(),
            'part_categories' => PartCategory::where('garage_id', $id)->get()->toArray(),
            'part_brands' => PartBrand::where('garage_id', $id)->get()->toArray(),
            'expenses' => Expense::where('garage_id', $id)->get()->toArray(),
            'coupons' => Coupon::where('garage_id', $id)->get()->toArray(),
            'settings' => Setting::where('garage_id', $id)->get()->toArray(),
        ];
    }

    /**
     * Recursively add a folder to ZipArchive.
     */
    protected static function addFolderToZip(string $folder, ZipArchive $zip, string $zipPath): void
    {
        if (!is_dir($folder)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folder) + 1);
                $zip->addFile($filePath, $zipPath . '/' . str_replace('\\', '/', $relativePath));
            }
        }
    }

    /**
     * Restore only a specific garage from a backup zip archive.
     * All other garages remain 100% untouched.
     */
    public static function restoreGarage(string $zipFilePath, int $targetGarageId): array
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('PHP ZipArchive extension is missing.');
        }

        if (!file_exists($zipFilePath)) {
            throw new \Exception("Backup file not found at {$zipFilePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new \Exception("Failed opening backup zip file.");
        }

        $manifestContent = $zip->getFromName('manifest.json');
        if (!$manifestContent) {
            $zip->close();
            throw new \Exception("Invalid backup archive: missing manifest.json");
        }

        $manifest = json_decode($manifestContent, true);
        $garages = $manifest['garages'] ?? [];

        // Match the target garage in the backup
        $targetMeta = null;
        foreach ($garages as $gMeta) {
            if ((int)$gMeta['id'] === $targetGarageId) {
                $targetMeta = $gMeta;
                break;
            }
        }

        // If not matched by ID, but backup contains exactly 1 garage, use that one
        if (!$targetMeta && count($garages) === 1) {
            $targetMeta = $garages[0];
        }

        if (!$targetMeta) {
            $zip->close();
            throw new \Exception("Garage data not found inside this backup archive.");
        }

        $gSlug = $targetMeta['slug'];
        $dataContent = $zip->getFromName("garages/{$gSlug}/data.json");
        if (!$dataContent) {
            $zip->close();
            throw new \Exception("Missing data.json for garage {$targetMeta['name']}.");
        }

        $data = json_decode($dataContent, true);
        if (!is_array($data)) {
            $zip->close();
            throw new \Exception("Corrupted data.json in backup archive.");
        }

        // Target Garage record
        $targetGarage = Garage::findOrFail($targetGarageId);

        // Perform restore inside transactional block with FK safety
        DB::transaction(function () use ($targetGarageId, $data) {
            // Temporarily disable foreign key checks for safe isolated table repopulation
            $isMysql = DB::connection()->getDriverName() === 'mysql';
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            try {
                // 1. Clean existing records for this garage
                $jobCardIds = JobCard::where('garage_id', $targetGarageId)->pluck('id');
                JobCardIssue::whereIn('job_card_id', $jobCardIds)->delete();
                JobCardItem::whereIn('job_card_id', $jobCardIds)->delete();
                DB::table('job_card_mechanic')->whereIn('job_card_id', $jobCardIds)->delete();
                JobCard::where('garage_id', $targetGarageId)->delete();

                Vehicle::where('garage_id', $targetGarageId)->delete();
                Customer::where('garage_id', $targetGarageId)->delete();

                $purchaseIds = PurchaseInvoice::where('garage_id', $targetGarageId)->pluck('id');
                PurchaseInvoiceItem::whereIn('purchase_invoice_id', $purchaseIds)->delete();
                PurchaseInvoice::where('garage_id', $targetGarageId)->delete();
                Supplier::where('garage_id', $targetGarageId)->delete();

                Inventory::where('garage_id', $targetGarageId)->delete();
                Expense::where('garage_id', $targetGarageId)->delete();
                Coupon::where('garage_id', $targetGarageId)->delete();
                ServiceRequest::where('garage_id', $targetGarageId)->delete();
                ServiceType::where('garage_id', $targetGarageId)->delete();
                Setting::where('garage_id', $targetGarageId)->delete();

                // 2. Restore Garage Attributes
                if (!empty($data['garage'])) {
                    $gAttrs = $data['garage'];
                    unset($gAttrs['id'], $gAttrs['created_at'], $gAttrs['updated_at']);
                    Garage::where('id', $targetGarageId)->update($gAttrs);
                }

                // 3. Restore Users (update or create by email)
                if (!empty($data['users'])) {
                    foreach ($data['users'] as $u) {
                        $email = $u['email'] ?? null;
                        if (!$email) continue;
                        $u['garage_id'] = $targetGarageId;
                        User::updateOrCreate(['email' => $email], $u);
                    }
                }

                // 4. Restore Customers
                if (!empty($data['customers'])) {
                    foreach ($data['customers'] as $c) {
                        $c['garage_id'] = $targetGarageId;
                        Customer::create($c);
                    }
                }

                // 5. Restore Vehicles
                if (!empty($data['vehicles'])) {
                    foreach ($data['vehicles'] as $v) {
                        $v['garage_id'] = $targetGarageId;
                        Vehicle::create($v);
                    }
                }

                // 6. Restore Job Cards
                if (!empty($data['job_cards'])) {
                    foreach ($data['job_cards'] as $jc) {
                        $jc['garage_id'] = $targetGarageId;
                        JobCard::create($jc);
                    }
                }

                // 7. Restore Job Card Items
                if (!empty($data['job_card_items'])) {
                    foreach ($data['job_card_items'] as $jci) {
                        JobCardItem::create($jci);
                    }
                }

                // 8. Restore Job Card Issues
                if (!empty($data['job_card_issues'])) {
                    foreach ($data['job_card_issues'] as $jcis) {
                        JobCardIssue::create($jcis);
                    }
                }

                // 9. Restore Job Card Mechanics Pivot
                if (!empty($data['job_card_mechanics'])) {
                    foreach ($data['job_card_mechanics'] as $p) {
                        DB::table('job_card_mechanic')->insertOrIgnore($p);
                    }
                }

                // 10. Restore Suppliers
                if (!empty($data['suppliers'])) {
                    foreach ($data['suppliers'] as $s) {
                        $s['garage_id'] = $targetGarageId;
                        Supplier::create($s);
                    }
                }

                // 11. Restore Purchase Invoices & Items
                if (!empty($data['purchase_invoices'])) {
                    foreach ($data['purchase_invoices'] as $pi) {
                        $pi['garage_id'] = $targetGarageId;
                        PurchaseInvoice::create($pi);
                    }
                }
                if (!empty($data['purchase_invoice_items'])) {
                    foreach ($data['purchase_invoice_items'] as $pii) {
                        PurchaseInvoiceItem::create($pii);
                    }
                }

                // 12. Restore Inventory
                if (!empty($data['inventory'])) {
                    foreach ($data['inventory'] as $inv) {
                        $inv['garage_id'] = $targetGarageId;
                        Inventory::create($inv);
                    }
                }

                // 13. Restore Expenses
                if (!empty($data['expenses'])) {
                    foreach ($data['expenses'] as $exp) {
                        $exp['garage_id'] = $targetGarageId;
                        Expense::create($exp);
                    }
                }

                // 14. Restore Coupons, Service Requests, Service Types
                if (!empty($data['coupons'])) {
                    foreach ($data['coupons'] as $cp) {
                        $cp['garage_id'] = $targetGarageId;
                        Coupon::create($cp);
                    }
                }
                if (!empty($data['service_requests'])) {
                    foreach ($data['service_requests'] as $sr) {
                        $sr['garage_id'] = $targetGarageId;
                        ServiceRequest::create($sr);
                    }
                }
                if (!empty($data['service_types'])) {
                    foreach ($data['service_types'] as $st) {
                        $st['garage_id'] = $targetGarageId;
                        ServiceType::create($st);
                    }
                }
                if (!empty($data['settings'])) {
                    foreach ($data['settings'] as $stg) {
                        $stg['garage_id'] = $targetGarageId;
                        Setting::create($stg);
                    }
                }
            } finally {
                if ($isMysql) {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                }
            }
        });

        // 15. Extract uploads for this garage
        $targetUploadFolder = storage_path("app/public/garages/{$targetGarage->slug}");
        if (!is_dir($targetUploadFolder)) {
            @mkdir($targetUploadFolder, 0755, true);
        }

        $prefix = "garages/{$gSlug}/files/";
        $prefixLen = strlen($prefix);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (str_starts_with($entryName, $prefix)) {
                $relativeFilePath = substr($entryName, $prefixLen);
                if (empty($relativeFilePath) || str_ends_with($relativeFilePath, '/')) {
                    continue;
                }

                // Security: Prevent Zip Slip / Path Traversal
                if (str_contains($relativeFilePath, '..') || str_contains($relativeFilePath, "\0")) {
                    Log::warning("BackupService: Blocked path traversal attempt in zip entry: {$entryName}");
                    continue;
                }

                // Security: Disallow executable file extensions in uploaded asset restores
                $extension = strtolower(pathinfo($relativeFilePath, PATHINFO_EXTENSION));
                if (in_array($extension, ['php', 'phtml', 'phar', 'php5', 'php7', 'php8', 'htaccess', 'sh', 'exe', 'bat', 'cmd'])) {
                    Log::warning("BackupService: Blocked potentially executable file restore: {$entryName}");
                    continue;
                }

                $destination = $targetUploadFolder . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFilePath);
                $destDir = dirname($destination);
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }

                // Verify resolved canonical directory path starts with canonical target folder
                $realTarget = realpath($targetUploadFolder);
                $realDestDir = realpath($destDir);
                if (!$realTarget || !$realDestDir || !str_starts_with(str_replace('\\', '/', $realDestDir), str_replace('\\', '/', $realTarget))) {
                    Log::warning("BackupService: Blocked directory escape attempt in zip entry: {$entryName}");
                    continue;
                }

                $stream = $zip->getStream($entryName);
                if ($stream) {
                    file_put_contents($destination, stream_get_contents($stream));
                    fclose($stream);

                    // Hostinger shared hosting public/storage copy
                    try {
                        $publicDest = public_path("storage/garages/{$targetGarage->slug}/" . $relativeFilePath);
                        $publicDir = dirname($publicDest);
                        if (!is_dir($publicDir)) {
                            @mkdir($publicDir, 0755, true);
                        }
                        @copy($destination, $publicDest);
                    } catch (\Throwable $e) {}
                }
            }
        }

        $zip->close();

        return [
            'success' => true,
            'message' => "Garage '{$targetGarage->name}' restored successfully from backup.",
        ];
    }

    /**
     * Restore full CRM system (all garages and global reference data).
     */
    public static function restoreFull(string $zipFilePath): array
    {
        if (!file_exists($zipFilePath)) {
            throw new \Exception("Backup file not found.");
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new \Exception("Cannot open backup zip file.");
        }

        $manifestContent = $zip->getFromName('manifest.json');
        if (!$manifestContent) {
            $zip->close();
            throw new \Exception("Missing manifest.json.");
        }

        $manifest = json_decode($manifestContent, true);
        $garages = $manifest['garages'] ?? [];

        // Restore global system settings & models if present
        $systemContent = $zip->getFromName('system.json');
        if ($systemContent) {
            $sys = json_decode($systemContent, true);
            if (!empty($sys['settings'])) {
                SystemSettingService::setMany($sys['settings']);
            }
        }

        $zip->close();

        $restoredCount = 0;
        foreach ($garages as $gMeta) {
            try {
                // Find or recreate garage
                $garage = Garage::where('id', $gMeta['id'])->orWhere('slug', $gMeta['slug'])->first();
                if (!$garage) {
                    $garage = Garage::create([
                        'id' => $gMeta['id'],
                        'name' => $gMeta['name'],
                        'slug' => $gMeta['slug'],
                        'is_active' => true,
                    ]);
                }
                self::restoreGarage($zipFilePath, $garage->id);
                $restoredCount++;
            } catch (\Throwable $e) {
                Log::error("Failed restoring garage {$gMeta['name']}: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => "Full system restore completed ({$restoredCount} garages restored).",
        ];
    }

    /**
     * Prune older backups keeping only the most recent N files.
     */
    public static function pruneOldBackups(int $keepCount = 5): void
    {
        if ($keepCount < 1) return;

        $dir = self::getBackupDirectory();
        $files = glob($dir . '/*.zip') ?: [];

        if (count($files) <= $keepCount) {
            return;
        }

        // Sort oldest first
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

        $toDeleteCount = count($files) - $keepCount;
        for ($i = 0; $i < $toDeleteCount; $i++) {
            @unlink($files[$i]);
        }
    }

    /**
     * Delete a single backup file.
     */
    public static function deleteBackup(string $filename): bool
    {
        $dir = self::getBackupDirectory();
        $safeName = basename($filename);
        $filePath = $dir . DIRECTORY_SEPARATOR . $safeName;

        if (file_exists($filePath)) {
            return @unlink($filePath);
        }

        return false;
    }

    /**
     * Get path for downloading backup file safely.
     */
    public static function getBackupPath(string $filename): ?string
    {
        $dir = self::getBackupDirectory();
        $safeName = basename($filename);
        $filePath = $dir . DIRECTORY_SEPARATOR . $safeName;

        return file_exists($filePath) ? $filePath : null;
    }
}
