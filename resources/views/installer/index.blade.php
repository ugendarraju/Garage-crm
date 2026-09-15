<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Wizard - Garage CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .container {
            width: 100%;
            max-width: 680px;
            background: #1e293b;
            border-radius: 16px;
            border: 1px solid #334155;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        .header {
            padding: 32px 32px 24px;
            background: #0f172a;
            border-bottom: 1px solid #334155;
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: #3b82f6;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 4px 10px;
            border-radius: 9999px;
            margin-bottom: 12px;
        }
        .title {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }
        .subtitle {
            font-size: 14px;
            color: #94a3b8;
        }
        .body {
            padding: 32px;
        }
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 13.5px;
            line-height: 1.5;
        }
        .alert-error {
            background: #450a0a;
            border: 1px solid #991b1b;
            color: #fca5a5;
        }
        .alert-success {
            background: #064e3b;
            border: 1px solid #065f46;
            color: #6ee7b7;
        }
        .section-title {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #38bdf8;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #cbd5e1;
            margin-bottom: 6px;
        }
        label .hint {
            font-size: 11.5px;
            color: #64748b;
            font-weight: normal;
        }
        input {
            width: 100%;
            padding: 11px 14px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            font-size: 14px;
            color: #ffffff;
            transition: all 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }
        .checklist {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 24px;
        }
        .check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            border-bottom: 1px solid #1e293b;
        }
        .check-item:last-child { border-bottom: none; }
        .status-ok { color: #4ade80; font-weight: 600; }
        .status-fail { color: #f87171; font-weight: 600; }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #0284c7;
            color: #ffffff;
            font-weight: 600;
            font-size: 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: #0369a1;
        }
        .btn-submit:disabled {
            background: #475569;
            cursor: not-allowed;
        }
        .success-box {
            text-align: center;
            padding: 24px 12px;
        }
        .success-icon {
            width: 64px;
            height: 64px;
            background: #065f46;
            color: #34d399;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }
        @media (max-width: 600px) {
            .grid-2 { grid-template-columns: 1fr; }
            .header, .body { padding: 24px 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <span class="badge">Automatic Setup</span>
        <h1 class="title">Garage CRM Installation</h1>
        <p class="subtitle">Complete the setup below to connect your database and launch your CRM.</p>
    </div>

    <div class="body">
        @if(isset($success) && $success)
            <div class="success-box">
                <div class="success-icon">&#10003;</div>
                <h2 style="font-size: 22px; margin-bottom: 10px; color: #ffffff;">Installation Completed!</h2>
                <p style="color: #94a3b8; font-size: 14px; margin-bottom: 24px;">
                    Your database has been migrated, vehicle brands seeded, and Super Admin account created.
                </p>
                <div style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 16px; margin-bottom: 24px; text-align: left; font-size: 13.5px;">
                    <div style="margin-bottom: 8px;"><strong style="color: #94a3b8;">Admin Email:</strong> <span style="color: #38bdf8;">{{ $adminEmail }}</span></div>
                    <div><strong style="color: #94a3b8;">Status:</strong> <span style="color: #4ade80;">Ready for Production</span></div>
                </div>
                <a href="{{ $loginUrl }}" class="btn-submit" style="display: block; text-decoration: none; text-align: center;">
                    Go to Login &rarr;
                </a>
            </div>
        @else

            @if(session('error'))
                <div class="alert alert-error">
                    <strong>Error:</strong> {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <ul style="padding-left: 18px;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(isset($requirements))
                <div class="section-title">1. System Checks</div>
                <div class="checklist">
                    @foreach($requirements as $item => $passed)
                        <div class="check-item">
                            <span>{{ $item }}</span>
                            @if($passed)
                                <span class="status-ok">&#10003; OK</span>
                            @else
                                <span class="status-fail">&#10007; Required</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('install.setup') }}" id="installForm">
                @csrf

                <div class="section-title">2. Database Details (MySQL)</div>
                <p style="color: #64748b; font-size: 12.5px; margin-bottom: 14px;">
                    Create a MySQL database in your Hostinger hPanel and paste its credentials below.
                </p>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Database Host <span class="hint">(Usually localhost or 127.0.0.1)</span></label>
                        <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Database Port</label>
                        <input type="text" name="db_port" value="{{ old('db_port', '3306') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Database Name <span class="hint">(e.g. u123456_garage)</span></label>
                    <input type="text" name="db_name" value="{{ old('db_name') }}" placeholder="u123456_garage" required>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Database Username</label>
                        <input type="text" name="db_username" value="{{ old('db_username') }}" placeholder="u123456_user" required>
                    </div>
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_password" placeholder="••••••••">
                    </div>
                </div>

                <div class="section-title" style="margin-top: 20px;">3. Admin Account & Application</div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Application Name</label>
                        <input type="text" name="app_name" value="{{ old('app_name', 'Garage CRM') }}">
                    </div>
                    <div class="form-group">
                        <label>Application URL</label>
                        <input type="url" name="app_url" value="{{ old('app_url', request()->root()) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Super Admin Name</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name', 'Administrator') }}" required>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" value="{{ old('admin_email', 'admin@garage.com') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Password <span class="hint">(min 6 chars)</span></label>
                        <input type="password" name="admin_password" value="{{ old('admin_password', 'password') }}" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    Install CRM Now
                </button>
            </form>
        @endif
    </div>
</div>

<script>
    const form = document.getElementById('installForm');
    if (form) {
        form.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerText = 'Installing & Migrating Database, please wait...';
        });
    }
</script>

</body>
</html>
