<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Services\BackupService;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends Controller
{
    private function authorizeSuperAdmin(): void
    {
        if (!auth()->check() || auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized access. Backups are reserved for Super Admins only.');
        }
    }

    public function index()
    {
        $this->authorizeSuperAdmin();

        $backups = BackupService::listBackups();
        $garages = Garage::select('id', 'name', 'slug')->orderBy('name')->get();

        $schedule = [
            'frequency' => SystemSettingService::get('backup_frequency', 'biweekly'),
            'retention' => (int) SystemSettingService::get('backup_retention', 5),
            'last_backup_at' => SystemSettingService::get('last_backup_at'),
        ];

        $totalBytes = array_sum(array_column($backups, 'size_bytes'));

        return Inertia::render('SuperAdmin/Backups/Index', [
            'backups' => $backups,
            'garages' => $garages,
            'schedule' => $schedule,
            'stats' => [
                'total_count' => count($backups),
                'total_size' => $this->formatBytes($totalBytes),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'garage_id' => 'nullable|exists:garages,id',
            'label' => 'nullable|string|max:100',
        ]);

        $garageId = $request->filled('garage_id') ? (int) $request->garage_id : null;
        $label = $request->input('label', '');

        try {
            $result = BackupService::createBackup($garageId, $label);
            return back()->with('success', "Backup archive '{$result['filename']}' generated successfully.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Backup failed: ' . $e->getMessage()]);
        }
    }

    public function download(string $filename)
    {
        $this->authorizeSuperAdmin();

        $path = BackupService::getBackupPath($filename);
        if (!$path) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function restore(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'filename' => 'required|string',
            'restore_type' => 'required|in:single_garage,full',
            'target_garage_id' => 'required_if:restore_type,single_garage|nullable|exists:garages,id',
        ]);

        $path = BackupService::getBackupPath($validated['filename']);
        if (!$path) {
            return back()->withErrors(['error' => 'Backup file does not exist.']);
        }

        try {
            if ($validated['restore_type'] === 'single_garage') {
                $targetId = (int) $validated['target_garage_id'];
                $result = BackupService::restoreGarage($path, $targetId);
                return back()->with('success', $result['message']);
            } else {
                $result = BackupService::restoreFull($path);
                return back()->with('success', $result['message']);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Restoration failed: ' . $e->getMessage()]);
        }
    }

    public function upload(Request $request)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'backup_file' => 'required|file|mimes:zip|max:102400', // max 100MB
        ]);

        $file = $request->file('backup_file');
        $filename = 'uploaded_' . date('Y-m-d_His') . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $file->getClientOriginalName());

        $file->move(BackupService::getBackupDirectory(), $filename);

        return back()->with('success', 'Backup file uploaded successfully. You can now restore or download it.');
    }

    public function destroy(string $filename)
    {
        $this->authorizeSuperAdmin();

        if (BackupService::deleteBackup($filename)) {
            return back()->with('success', 'Backup archive deleted.');
        }

        return back()->withErrors(['error' => 'Could not delete backup file.']);
    }

    public function updateSchedule(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'frequency' => 'required|in:weekly,biweekly,monthly,disabled',
            'retention' => 'required|integer|min:1|max:30',
        ]);

        SystemSettingService::setMany([
            'backup_frequency' => $validated['frequency'],
            'backup_retention' => (int) $validated['retention'],
        ]);

        return back()->with('success', 'Automated backup schedule settings saved.');
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
