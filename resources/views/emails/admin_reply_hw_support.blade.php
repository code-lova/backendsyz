@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Response - {{ $supportMessage->reference }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333333;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #94cbff, #99bdf3);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .logo-section {
            margin-bottom: 20px;
        }
        .logo-placeholder {
            width: 80px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .message-info {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .message-info h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .message-info p {
            margin: 5px 0;
            color: #666;
        }
        .admin-reply {
            background-color: #e8f4fd;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .admin-reply h3 {
            margin: 0 0 15px 0;
            color: #1e40af;
            font-size: 16px;
        }
        .reply-content {
            color: #374151;
            line-height: 1.8;
            white-space: pre-wrap;
        }
        .reference-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 10px 15px;
            margin: 20px 0;
            font-size: 14px;
        }
        .reference-info strong {
            color: #856404;
        }
        .footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 25px 20px;
        }
        .footer p {
            margin: 5px 0;
            font-size: 14px;
        }
        .footer .contact-info {
            margin-top: 15px;
            font-size: 12px;
            opacity: 0.8;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-replied {
            background-color: #d4edda;
            color: #155724;
        }
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #ddd, transparent);
            margin: 25px 0;
        }
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 5px;
            }
            .content {
                padding: 20px;
            }
            .header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            <div class="logo-section">
                <img src="{{ $logoUrl }}" alt="Logo" style="height: 48px;">
            </div>
            <h1>Support Response</h1>
            <p>Your support request has been answered</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="greeting">
                Hello {{ $healthWorker->name ?? 'Health Worker' }},
            </div>

            <p>We have responded to your support request. Please find the details below:</p>

            <!-- Original Message Info -->
            <div class="message-info">
                <h3>📋 Your Original Request</h3>
                <p><strong>Subject:</strong> {{ $supportMessage->subject }}</p>
                <p><strong>Reference:</strong> {{ $supportMessage->reference }}</p>
                <div class="reply-content">{{ $supportMessage->message }}</div>
                <p><strong>Status:</strong> <span class="status-badge status-replied">{{ $supportMessage->status }}</span></p>
                <p><strong>Submitted:</strong> {{ $supportMessage->created_at->format('F j, Y \a\t g:i A') }}</p>
            </div>

            <!-- Admin Reply -->
            <div class="admin-reply">
                <h3>💬 Our Response</h3>
                <div class="reply-content">{{ $reply->admin_reply }}</div>
                <div class="divider"></div>
                <p style="margin: 0; font-size: 12px; color: #666;">
                    <strong>Response Reference:</strong> {{ $reply->reference }}<br>
                    <strong>Replied on:</strong> {{ $reply->created_at->format('F j, Y \a\t g:i A') }}
                </p>
            </div>

            <!-- Reference Information -->
            <div class="reference-info">
                <strong>📝 Note:</strong> Please keep this reference number for future correspondence: <strong>{{ $reply->reference }}</strong>
            </div>

            <p>If you need further assistance or have additional questions, please don't hesitate to contact our support team by replying to this email or submitting a new support request.</p>

            <p>Thank you for using our platform!</p>

            <p style="margin-top: 30px;">
                Best regards,<br>
                <strong>Support Team</strong><br>
                SupraCarer Platform
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>SupraCarer Support Team</strong></p>
            <p>Above and beyond care</p>
            <div class="contact-info">
                <p>This email was sent regarding your support request {{ $supportMessage->reference }}</p>
                <p>© {{ date('Y') }} SupraCarer. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
