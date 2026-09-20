<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Garage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PDO;
use Exception;

class InstallController extends Controller
{
    public function index()
    {
        if (file_exists(storage_path('installed'))) {
            return redirect('/');
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users') && \App\Models\User::count() > 0) {
                @file_put_contents(storage_path('installed'), 'installed');
                return redirect('/');
            }
        } catch (\Throwable $e) {}

        $requirements = [
            'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'XML Extension' => extension_loaded('xml'),
            'Ctype Extension' => extension_loaded('ctype'),
            'JSON Extension' => extension_loaded('json'),
            'Storage Writable' => is_writable(storage_path()),
            'Bootstrap/Cache Writable' => is_writable(base_path('bootstrap/cache')),
        ];

        $allRequirementsMet = !in_array(false, $requirements, true);

        return view('installer.index', compact('requirements', 'allRequirementsMet'));
    }

    public function setup(Request $request)
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        if (file_exists(storage_path('installed'))) {
            return redirect('/');
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users') && \App\Models\User::count() > 0) {
                @file_put_contents(storage_path('installed'), 'installed');
                return redirect('/');
            }
        } catch (\Throwable $e) {}

        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|string',
            'db_name' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'admin_name' => 'required|string|max:100',
            'admin_email' => 'required|email|max:150',
            'admin_password' => 'required|string|min:6',
            'app_name' => 'nullable|string|max:100',
            'app_url' => 'nullable|url',
        ]);

        $dbHost = $request->input('db_host');
        $dbPort = $request->input('db_port');
        $dbName = $request->input('db_name');
        $dbUser = $request->input('db_username');
        $dbPass = $request->input('db_password', '');
        $appName = $request->input('app_name', 'Garage CRM') ?: 'Garage CRM';
        $appUrl = $request->input('app_url') ?: $request->root();

        // 1. Test PDO connection
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Database Connection Failed: ' . $e->getMessage());
        }

        try {
            // Generate real cryptographic APP_KEY
            $appKey = 'base64:' . base64_encode(random_bytes(32));

            // 2. Write .env file
            $this->updateEnv([
                'APP_NAME' => $appName,
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_KEY' => $appKey,
                'APP_URL' => $appUrl,
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $dbHost,
                'DB_PORT' => $dbPort,
                'DB_DATABASE' => $dbName,
                'DB_USERNAME' => $dbUser,
                'DB_PASSWORD' => $dbPass,
                'SESSION_DRIVER' => 'file',
                'CACHE_STORE' => 'file',
                'QUEUE_CONNECTION' => 'sync',
            ]);

            config(['app.key' => $appKey]);

            // 3. Configure Laravel database connection dynamically
            config([
                'database.default' => 'mysql',
                'database.connections.mysql.host' => $dbHost,
                'database.connections.mysql.port' => $dbPort,
                'database.connections.mysql.database' => $dbName,
                'database.connections.mysql.username' => $dbUser,
                'database.connections.mysql.password' => $dbPass,
            ]);

            DB::purge('mysql');
            DB::reconnect('mysql');

            // 4. Run migrations (fresh to ensure clean schema on initial setup or retry)
            try {
                Artisan::call('migrate:fresh', ['--force' => true]);
            } catch (\Throwable $mfe) {
                Artisan::call('migrate', ['--force' => true]);
            }

            // 5. Run seeders if brands table is empty
            try {
                $brandsCount = DB::table('vehicle_brands')->count();
                if ($brandsCount === 0) {
                    Artisan::call('db:seed', ['--force' => true]);
                }
            } catch (\Throwable $se) {
                // Non-fatal if seeds already exist or optional
            }

            // 6. Create default garage if none exists
            if (\App\Models\Garage::count() === 0) {
                \App\Models\Garage::create([
                    'name' => $appName,
                    'is_active' => true,
                ]);
            }

            // 7. Create or update Super Admin user
            User::updateOrCreate(
                ['email' => $request->input('admin_email')],
                [
                    'name' => $request->input('admin_name'),
                    'role' => 'super_admin',
                    'password' => Hash::make($request->input('admin_password')),
                    'is_active' => true,
                ]
            );

            // 8. Create storage symlink
            try {
                Artisan::call('storage:link');
            } catch (\Throwable $e) {
                // Ignore if symlink exists
            }

            // 9. Clear cache
            try {
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
            } catch (\Throwable $e) {
                // Non-fatal
            }

            // 10. Mark application as installed
            file_put_contents(storage_path('installed'), json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '1.0.0',
            ]));

            return view('installer.index', [
                'success' => true,
                'adminEmail' => $request->input('admin_email'),
                'loginUrl' => url('/login'),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Installation Failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Installation Failed: ' . $e->getMessage());
        }
    }

    private function updateEnv(array $values): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                touch($envPath);
            }
        }

        $envContent = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $formattedValue = (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '"'))
                ? '"' . addcslashes($value, '"') . '"'
                : $value;

            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$formattedValue}", $envContent);
            } else {
                $envContent .= "\n{$key}={$formattedValue}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
