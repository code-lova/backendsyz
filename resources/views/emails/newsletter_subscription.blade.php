@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to SupraCarer Newsletter</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 40px 20px;
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
        .header .welcome-icon {
            font-size: 48px;
            margin-bottom: 15px;
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
            margin-bottom: 25px;
            line-height: 1.8;
        }
        .benefits-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
        }
        .benefits-section h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
        }
        .benefit-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .benefit-icon {
            font-size: 24px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .benefit-text {
            flex: 1;
        }
        .benefit-text h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 15px;
        }
        .benefit-text p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.2s;
            font-size: 16px;
        }
        .btn:hover {
            transform: translateY(-2px);
            color: #ffffff !important;
        }
        .social-section {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        .social-section h4 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: #ffffff;
            border-radius: 50%;
            text-decoration: none;
            font-size: 18px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .social-icon:hover {
            transform: translateY(-2px);
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
        .footer-links {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(2, auto);
            justify-content: center;
            gap: 15px;
        }
        .footer-links a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 8px 12px;
            font-size: 14px;
        }
        .footer-links a:hover {
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
        .unsubscribe-link {
            color: #95a5a6;
            text-decoration: underline;
        }
        .unsubscribe-link:hover {
            color: #ecf0f1;
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
                padding: 30px 15px;
            }
            .content {
                padding: 20px 15px;
            }
            .footer {
                padding: 20px 15px;
            }
            .benefits-section {
                padding: 20px 15px;
                margin: 20px 0;
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
                padding: 25px 10px;
            }
            .header h1 {
                font-size: 22px;
            }
            .header .welcome-icon {
                font-size: 36px;
            }
            .content {
                padding: 15px 10px;
            }
            .footer {
                padding: 15px 10px;
            }
            .benefits-section {
                padding: 15px 10px;
                margin: 15px 0;
            }
            .benefit-item {
                padding: 8px;
            }
            .benefit-icon {
                font-size: 20px;
                margin-right: 10px;
            }
            .benefit-text h4 {
                font-size: 14px;
            }
            .benefit-text p {
                font-size: 13px;
            }
            .message {
                font-size: 14px;
            }
            .greeting {
                font-size: 16px;
            }
            .btn {
                padding: 12px 25px;
                font-size: 14px;
            }
        }

        @media (max-width: 360px) {
            .content {
                padding: 12px 8px;
            }
            .benefits-section {
                padding: 12px 8px;
            }
            .footer-links {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .footer-links a {
                display: block;
                width: 100%;
                max-width: 280px;
                text-align: center;
                margin: 0;
                font-size: 13px;
                padding: 10px 15px;
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
                        <div class="welcome-icon">🎉</div>
                        <img src="{{ $logoUrl }}" alt="SupraCarer Logo" style="height: 48px; max-width: 200px; margin-bottom: 15px;">
                        <h1>Welcome to Our Newsletter!</h1>
                        <div class="subtitle">You're now part of the SupraCarer community</div>
                    </div>

                    <!-- Content -->
                    <div class="content">
                        <div class="greeting">
                            Hello there! 👋
                        </div>

                        <div class="message">
                            Thank you for subscribing to the SupraCarer newsletter! We're thrilled to have you join our growing community of healthcare enthusiasts and professionals.
                        </div>

                        <div class="message">
                            You've taken the first step towards staying informed about the latest in healthcare services, industry insights, and exclusive updates from SupraCarer.
                        </div>

                        <!-- Benefits Section -->
                        <div class="benefits-section">
                            <h3>✨ What You'll Receive</h3>

                            <div class="benefit-item">
                                <span class="benefit-icon">📰</span>
                                <div class="benefit-text">
                                    <h4>Latest Healthcare News</h4>
                                    <p>Stay updated with the latest trends and developments in the healthcare industry.</p>
                                </div>
                            </div>

                            <div class="benefit-item">
                                <span class="benefit-icon">💡</span>
                                <div class="benefit-text">
                                    <h4>Expert Tips & Insights</h4>
                                    <p>Receive valuable advice from healthcare professionals and industry experts.</p>
                                </div>
                            </div>

                            <div class="benefit-item">
                                <span class="benefit-icon">🎁</span>
                                <div class="benefit-text">
                                    <h4>Exclusive Offers</h4>
                                    <p>Be the first to know about special promotions and exclusive discounts.</p>
                                </div>
                            </div>

                            <div class="benefit-item">
                                <span class="benefit-icon">🏥</span>
                                <div class="benefit-text">
                                    <h4>Service Updates</h4>
                                    <p>Get notified about new services, features, and improvements to our platform.</p>
                                </div>
                            </div>

                            <div class="benefit-item">
                                <span class="benefit-icon">📅</span>
                                <div class="benefit-text">
                                    <h4>Events & Webinars</h4>
                                    <p>Invitations to exclusive events, webinars, and community gatherings.</p>
                                </div>
                            </div>
                        </div>

                        <div class="cta-section">
                            <a href="https://www.supracarer.com" class="btn">Explore SupraCarer</a>
                        </div>

                        <div class="message">
                            We promise to only send you valuable content and never spam your inbox. 
                            Expect to hear from us about once or twice a month with carefully curated updates.
                        </div>

                        <div class="message">
                            <strong>Your subscription details:</strong><br>
                            📧 Email: {{ $email }}<br>
                            📅 Subscribed: {{ $subscribed_at }}
                        </div>
                    </div>

                    <!-- Social Section -->
                    <div class="social-section">
                        <h4>Connect With Us</h4>
                        <div class="social-icons">
                            <a href="https://facebook.com/supracarer" class="social-icon" title="Facebook">📘</a>
                            <a href="https://twitter.com/supracarer" class="social-icon" title="Twitter">🐦</a>
                            <a href="https://instagram.com/supracarer" class="social-icon" title="Instagram">📸</a>
                            <a href="https://linkedin.com/company/supracarer" class="social-icon" title="LinkedIn">💼</a>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="footer">
                        <div class="footer-content">
                            <h4>SupraCarer</h4>
                            <p>Above and beyond care</p>
                            <p>Professional Healthcare Services Platform</p>

                            <div class="footer-links">
                                <a href="mailto:support@supracarer.com">📧 support@supracarer.com</a>
                                <a href="https://www.supracarer.com">🌐 www.supracarer.com</a>
                                <a href="tel:+233549148087">📞 +(233) 549-148-087</a>
                            </div>
                        </div>

                        <div class="disclaimer">
                            <p>You're receiving this email because you subscribed to the SupraCarer newsletter.</p>
                            @if($unsubscribe_url)
                                <p>Changed your mind? <a href="{{ $unsubscribe_url }}" class="unsubscribe-link">Unsubscribe here</a></p>
                            @endif
                            <p>&copy; {{ date('Y') }} SupraCarer. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
