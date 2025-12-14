@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        /* Email client reset */
        #outlook a { padding: 0; }
        .ReadMsgBody { width: 100%; }
        .ExternalClass { width: 100%; }
        .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        #bodyTable { margin: 0; padding: 0; width: 100% !important; line-height: 100% !important; }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            width: 100%;
        }
        #bodyTable {
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .email-container {
            background-color: #ffffff;
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #fafcfa 0%, #f0f4f8 100%);
            color: #052652;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .header .subtitle {
            margin-top: 10px;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .message {
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .verification-section {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 30px 20px;
            margin: 25px 0;
            border-radius: 5px;
            text-align: center;
        }
        .verification-section h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .verification-code {
            display: inline-block;
            background: linear-gradient(135deg, #052652 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 20px 40px;
            border-radius: 10px;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(5, 38, 82, 0.3);
            font-family: 'Courier New', monospace;
        }
        .expiry-notice {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
            font-size: 14px;
            font-weight: 600;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #052652;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            color: #ffffff !important;
        }
        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 30px 20px;
            text-align: center;
        }
        .footer-content {
            margin-bottom: 20px;
        }
        .footer h4 {
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: 300;
        }
        .footer p {
            margin: 5px 0;
            opacity: 0.8;
            font-size: 14px;
        }
        .social-links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .social-links a {
            color: #ecf0f1;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s;
            padding: 8px 12px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            font-size: 14px;
        }
        .social-links a:hover {
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.2);
        }
        .disclaimer {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #34495e;
        }

        /* Enhanced responsive design */
        @media (max-width: 600px) {
            #bodyTable td {
                padding: 10px !important;
            }
            .email-container {
                margin: 0;
                border-radius: 5px;
                max-width: 100%;
            }
            .header {
                padding: 20px 15px;
            }
            .content {
                padding: 20px 15px;
            }
            .footer {
                padding: 20px 15px;
            }
            .verification-section {
                padding: 20px 15px;
                margin: 20px 0;
            }
            .verification-code {
                font-size: 24px;
                letter-spacing: 4px;
                padding: 15px 30px;
            }
        }

        @media (max-width: 480px) {
            #bodyTable td {
                padding: 5px !important;
            }
            .email-container {
                border-radius: 0;
            }
            .header {
                padding: 15px 10px;
            }
            .content {
                padding: 15px 10px;
            }
            .footer {
                padding: 15px 10px;
            }
            .verification-section {
                padding: 15px 10px;
                margin: 15px 0;
            }
            .verification-code {
                font-size: 20px;
                letter-spacing: 2px;
                padding: 12px 20px;
            }
            .header h1 {
                font-size: 20px;
            }
            .header .subtitle {
                font-size: 13px;
            }
            .message {
                font-size: 14px;
            }
            .greeting {
                font-size: 16px;
            }
        }

        @media (max-width: 360px) {
            .content {
                padding: 12px 8px;
            }
            .verification-section {
                padding: 12px 8px;
                margin: 12px 0;
            }
            .verification-code {
                font-size: 18px;
                letter-spacing: 1px;
                padding: 10px 15px;
            }
            .social-links a {
                display: block;
                margin: 5px 0;
                font-size: 13px;
            }
            .header h1 {
                font-size: 18px;
            }
            .message {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" id="bodyTable">
        <tr>
            <td style="padding: 20px; background-color: #f8f9fa;">
                <div class="email-container">
                    <!-- Header -->
                    <div class="header">
                        <img src="{{ $logoUrl }}" alt="SupraCarer Logo" style="height: 48px; max-width: 200px;">
                        <div class="subtitle">Professional Healthcare Platform</div>
                    </div>

                    <!-- Content -->
                    <div class="content">
                        <div class="greeting">
                            Hello {{ $name }},
                        </div>

                        <div class="message">
                            Welcome to SupraCarer! We're excited to have you join our professional healthcare platform.
                        </div>

                        <div class="message">
                            To complete your registration and secure your account, please verify your email address using the verification code below:
                        </div>

                        <!-- Verification Code Section -->
                        <div class="verification-section">
                            <h3>🔐 Email Verification Code</h3>

                            <div class="verification-code">
                                {{ $email_verification_code }}
                            </div>

                            <div class="expiry-notice">
                                ⏰ This verification code will expire in 10 minutes
                            </div>
                        </div>

                        <div class="message">
                            Simply enter this code in the verification field to activate your account.
                        </div>

                        <div class="message">
                            If you didn't create an account with SupraCarer, please ignore this email. Your email address will not be used for any further communications.
                        </div>

                        <div class="message">
                            If you have any questions or need assistance, our support team is here to help you get started.
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="footer">
                        <div class="footer-content">
                            <h4>SupraCarer</h4>
                            <p>Above and beyond care</p>
                            <p>Professional Healthcare Services Platform</p>

                            <div class="social-links">
                                <a href="mailto:support@supracarer.com">📧 support@supracarer.com</a>
                                <a href="https://www.supracarer.com">🌐 www.supracarer.com</a>
                                <a href="tel:+233549148087">📞 +(233) 549-148-087</a>
                            </div>
                        </div>

                        <div class="disclaimer">
                            <p>This is an automated message from SupraCarer. Please do not reply to this email.</p>
                            <p>If you have any questions, please contact our support team through the platform or visit our help center.</p>
                            <p>&copy; {{ date('Y') }} SupraCarer. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>




