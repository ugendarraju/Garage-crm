<?php

namespace App\Services;

use App\Models\ActionLog;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ActionLogService
{
    /**
     * Record an action log entry.
     *
     * @param string $action       Action verb (e.g. 'created', 'updated', 'deleted', 'status_changed', 'payment_received', 'impersonated', 'login', 'restored')
     * @param string $module       Module category (e.g. 'job_cards', 'inventory', 'purchases', 'sales', 'expenses', 'service_requests', 'staff', 'customers', 'settings', 'garages', 'auth')
     * @param string $description  Human-readable summary of what occurred
     * @param int|null $garageId   Specific garage ID (or defaults to active garage)
     * @param int|null $userId     Specific user ID (or defaults to authenticated user)
     * @param array|null $details  Additional payload or structured metadata
     * @param string|null $ip      Client IP address
     * @return ActionLog|null
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?int $garageId = null,
        ?int $userId = null,
        ?array $details = null,
        ?string $ip = null
    ): ?ActionLog {
        try {
            $user = null;
            if ($userId) {
                $user = User::find($userId);
            } elseif (auth()->check()) {
                $user = auth()->user();
                $userId = $user->id;
            }

            if ($garageId === null) {
                $garageId = Garage::getActiveGarageId();
                if ($garageId === null && $user && $user->garage_id) {
                    $garageId = $user->garage_id;
                }
            }

            $userName = $user ? $user->name : 'System';
            $userRole = $user ? $user->role : null;
            $ipAddress = $ip ?: (request() ? request()->ip() : null);

            return ActionLog::create([
                'garage_id' => $garageId,
                'user_id' => $userId,
                'user_name' => $userName,
                'user_role' => $userRole,
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'details' => $details,
                'ip_address' => $ipAddress,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ActionLogService failed to record action: ' . $e->getMessage(), [
                'action' => $action,
                'module' => $module,
                'description' => $description,
            ]);
            return null;
        }
    }
}
