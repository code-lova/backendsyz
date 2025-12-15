@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png"; // Place your logo in public/uploads/profile/logo.png
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $emailData['template_info']['category_label'] ?? 'SupraCarer Notification' }}</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        /* Container styles */
        .email-wrapper {
            width: 100%;
            background-color: #f9fafb;
            padding: 20px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 15px;
        }

        /* Header styles */
        .email-header {
            background: {{ $categoryStyles['gradient'] }};
            padding: 30px 40px;
            text-align: center;
            color: #050612;
            border-radius: 12px 12px 0 0;
        }

        .logo-container {
            margin-bottom: 20px;
        }

        .logo-img {
            height: 48px;
        }

        .header-title {
            font-size: 20px;
            font-weight: 500;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #05092d;
        }

        .category-icon {
            font-size: 19px;
        }

        /* Content styles */
        .email-content {
            padding: 40px;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .message-content {
            font-size: 16px;
            line-height: 1.7;
            color: #374151;
            margin-bottom: 30px;
        }

        .message-content p {
            margin-bottom: 16px;
        }

        .message-content p:last-child {
            margin-bottom: 0;
        }

        /* Category-specific content styling */
        .category-badge {
            display: inline-block;
            background-color: {{ $categoryStyles['primary_color'] }};
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
            margin-top: 13px;
        }

        /* Button styles */
        .cta-container {
            text-align: center;
            margin: 30px 0;
        }

        .cta-button {
            display: inline-block;
            background: {{ $categoryStyles['gradient'] }};
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s ease;
        }

        .cta-button:hover {
            transform: translateY(-1px);
        }

        /* Footer styles */
        .email-footer {
            background-color: #f8fafc;
            padding: 30px 40px;
            border-top: 1px solid #e5e7eb;
        }

        .footer-content {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        .company-info {
            margin-bottom: 20px;
        }

        .company-name {
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }

        .company-tagline {
            font-style: italic;
            margin-bottom: 15px;
        }

        .contact-info {
            margin-bottom: 20px;
        }

        .contact-info a {
            color: {{ $categoryStyles['primary_color'] }};
            text-decoration: none;
        }

        .social-links {
            margin: 20px 0;
            display: grid;
            grid-template-columns: repeat(2, auto);
            justify-content: center;
            gap: 15px;
        }

        .social-links a {
            display: inline-block;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 13px;
            padding: 8px 12px;
        }

        .social-links a:hover {
            background-color: rgba(0, 0, 0, 0.2);
        }

        @media only screen and (max-width: 480px) {
            .social-links {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .social-links a {
                width: 100%;
                max-width: 280px;
                text-align: center;
                padding: 10px 15px;
            }
        }

        .unsubscribe-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
        }

        .unsubscribe-section a {
            color: #6b7280;
            text-decoration: underline;
        }

        /* Responsive design */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px;
            }

            .email-header,
            .email-content,
            .email-footer {
                padding: 20px;
            }

            .logo-img {
                height: 38px;
            }

            .header-title {
                font-size: 15px;
            }

            .message-content {
                font-size: 15px;
            }

            .cta-button {
                padding: 12px 24px;
                font-size: 15px;
            }
        }

        @media only screen and (max-width: 480px) {
            .email-header,
            .email-content,
            .email-footer {
                padding: 15px;
            }

            .header-title {
                font-size: 18px;
                flex-direction: column;
                gap: 5px;
            }

            .logo-img {
                height: 38px;
            }

            .category-icon {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header Section -->
            <header class="email-header">
                <div class="logo-container">
                   <img src="{{ $logoUrl }}" alt="SupraCarer Logo" style="height: 48px;">
                </div>
                <h1 class="header-title">
                    <span class="category-icon">{{ $categoryStyles['icon'] }}</span>
                    {{ $emailData['template_info']['category_label'] ?? 'Notification' }}
                </h1>
            </header>

            <!-- Main Content -->
            <main class="email-content">
                <!-- Category Badge -->
                <div class="category-badge">
                    {{ strtoupper($emailData['category']) }} EMAIL
                </div>

                <!-- Greeting -->
                <div class="greeting">
                    Hello {{ $user->name }},
                </div>

                <!-- Message Content -->
                <div class="message-content">
                    @if($emailData['template_info']['format_as_html'] ?? false)
                        {!! nl2br(e($processedMessage)) !!}
                    @else
                        {{ $processedMessage }}
                    @endif
                </div>

                <!-- Call to Action (if promotional) -->
                @if($emailData['category'] === 'promotional')
                <div class="cta-container">
                    <a href="#" class="cta-button">
                        Learn More
                    </a>
                </div>
                @endif
            </main>

            <!-- Footer Section -->
            <footer class="email-footer">
                <div class="footer-content">
                    <div class="company-info">
                        <div class="company-name">SupraCarer</div>
                        <div class="company-tagline">Professional Home Health Care Services</div>
                        <div>Connecting you with qualified healthcare professionals</div>
                    </div>

                    <div class="contact-info">
                        <div>📧 <a href="mailto:support@supracarer.com">support@supracarer.com</a></div>
                        <div>🌐 <a href="https://supracarer.com">www.supracarer.com</a></div>
                    </div>

                    <div class="social-links">
                        <a href="mailto:support@supracarer.com">📧 support@supracarer.com</a>
                        <a href="https://www.supracarer.com">🌐 www.supracarer.com</a>
                        <a href="tel:+233549148087">📞 +(233) 549-148-087</a>
                    </div>

                    <div class="unsubscribe-section">
                        <p>
                            You received this email because you are a registered user of SupraCarer.<br>
                            If you no longer wish to receive these emails, you can
                            <a href="#">unsubscribe here</a> or
                            <a href="#">update your email preferences</a>.
                        </p>
                        <p style="margin-top: 10px;">
                            © {{ date('Y') }} SupraCarer. All rights reserved.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
