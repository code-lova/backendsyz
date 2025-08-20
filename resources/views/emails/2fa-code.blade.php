@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png"; // Place your logo in public/uploads/profile/logo.png
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Two-Factor Authentication Code</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; }
        .container { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #eee; padding: 32px; }
        .header, .footer { text-align: center; background: #f1f1f1; padding: 16px 0; }
        .code { font-size: 2em; font-weight: bold; color: #007bff; letter-spacing: 8px; margin: 24px 0; }
        .content { text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logoUrl }}" alt="Logo" style="height: 48px;">
        </div>
        <div class="content">
            <h2>Hello, {{ $user->name ?? 'User' }}</h2>
            <p>Your two-factor authentication code is:</p>
            <div class="code">{{ $code }}</div>
            <p>This code will expire in 10 minutes. If you did not request this, please ignore this email.</p>
        </div>
        <div class="footer">
            <small>&copy; {{ date('Y') }} Supracarer. All rights reserved.</small>
        </div>
    </div>
</body>
</html>
