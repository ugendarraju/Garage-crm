<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account Details</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 0; }
        .wrapper { width: 100%; max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
        .header { background: linear-gradient(135deg, #ea580c, #c2410c); padding: 30px 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .header p { margin: 6px 0 0 0; font-size: 14px; opacity: 0.9; }
        .content { padding: 30px 24px; }
        .welcome-text { font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
        .card { background-color: #f1f5f9; border-radius: 12px; padding: 20px; margin: 24px 0; border: 1px solid #cbd5e1; }
        .card-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .card-row:last-child { border-bottom: none; }
        .label { color: #64748b; font-weight: 600; }
        .value { color: #0f172a; font-weight: 700; font-family: monospace; font-size: 15px; }
        .btn-container { text-align: center; margin: 30px 0 10px 0; }
        .btn { display: inline-block; background-color: #ea580c; color: #ffffff !important; padding: 14px 32px; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 15px; box-shadow: 0 4px 6px -1px rgba(234, 88, 12, 0.3); }
        .notice { background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 6px; font-size: 13px; color: #92400e; margin: 20px 0; }
        .footer { padding: 20px 24px; background-color: #f8fafc; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $garage?->name ?? 'PistonCRM Garage Portal' }}</h1>
            <p>{{ $garage?->tagline ?? 'Smart Garage Management' }}</p>
        </div>
        <div class="content">
            <p class="welcome-text">
                Hello <strong>{{ $user->name }}</strong>,<br>
                An account has been created for you at <strong>{{ $garage?->name ?? 'our garage' }}</strong>. You can now access your customer dashboard to view your vehicle service history, active job cards, invoices, and book future services online.
            </p>

            <div class="card">
                <div class="card-row">
                    <span class="label">Login Portal:</span>
                    <span class="value">{{ url('/login') }}</span>
                </div>
                <div class="card-row">
                    <span class="label">Username / Phone:</span>
                    <span class="value">{{ $user->phone }}</span>
                </div>
                @if($user->email && !str_contains($user->email, 'customer_'))
                <div class="card-row">
                    <span class="label">Email:</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                @endif
                <div class="card-row">
                    <span class="label">Temporary Password:</span>
                    <span class="value" style="color: #ea580c; font-size: 16px;">{{ $temporaryPassword }}</span>
                </div>
            </div>

            <div class="notice">
                🔒 <strong>First-Time Login Security:</strong> For your account security, you will be prompted to change your temporary password and set a new personal password when you first log in.
            </div>

            <div class="btn-container">
                <a href="{{ url('/login') }}" class="btn">Log In to Your Customer Portal</a>
            </div>
        </div>
        <div class="footer">
            <p>This email was sent to {{ $user->email }} by {{ $garage?->name ?? 'PistonCRM' }}.</p>
            @if($garage?->address)
                <p>{{ $garage->address }}</p>
            @endif
        </div>
    </div>
</body>
</html>
