@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Unsubscribe - SupraCarer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            max-width: 500px;
            width: 100%;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .header {
            background: linear-gradient(135deg, #fafcfa 0%, #f0f4f8 100%);
            padding: 30px 20px;
        }
        .header img {
            height: 48px;
            max-width: 200px;
        }
        .content {
            padding: 40px 30px;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .icon.success {
            color: #28a745;
        }
        .icon.error {
            color: #dc3545;
        }
        h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .message {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 25px;
            line-height: 1.8;
        }
        .email-display {
            background-color: #f8f9fa;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            color: #495057;
            margin-bottom: 25px;
            display: inline-block;
        }
        .btn {
            display: inline-block;
            padding: 14px 35px;
            background: #052652;
            color: #ffffff;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.2s, background 0.2s;
            font-size: 16px;
        }
        .btn:hover {
            transform: translateY(-2px);
            background: #063670;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            font-size: 13px;
            color: #6c757d;
        }
        .footer a {
            color: #052652;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .content {
                padding: 30px 20px;
            }
            h1 {
                font-size: 20px;
            }
            .message {
                font-size: 14px;
            }
            .icon {
                font-size: 48px;
            }
            .btn {
                padding: 12px 25px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logoUrl }}" alt="SupraCarer Logo">
        </div>

        <div class="content">
            @if($success)
                <div class="icon success">✓</div>
                <h1>Unsubscribed Successfully</h1>
            @else
                <div class="icon error">✕</div>
                <h1>Oops! Something Went Wrong</h1>
            @endif

            <p class="message">{{ $message }}</p>

            @if(isset($email) && $success)
                <div class="email-display">
                    📧 {{ $email }}
                </div>
            @endif

            <div style="margin-top: 20px;">
                <a href="https://www.supracarer.com" class="btn">Visit SupraCarer</a>
            </div>

            @if($success)
                <p class="message" style="margin-top: 25px; font-size: 14px;">
                    Changed your mind? You can always <a href="https://www.supracarer.com" style="color: #052652;">resubscribe</a> to our newsletter.
                </p>
            @endif
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} SupraCarer. All rights reserved.</p>
            <p style="margin-top: 5px;">
                <a href="https://www.supracarer.com">www.supracarer.com</a> | 
                <a href="mailto:support@supracarer.com">support@supracarer.com</a>
            </p>
        </div>
    </div>
</body>
</html>
