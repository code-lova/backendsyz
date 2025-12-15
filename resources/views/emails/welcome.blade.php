@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to SupraCarer</title>
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
            background: linear-gradient(135deg, #052652 0%, #0e2157 100%);
            color: #ffffff;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 300;
            margin-bottom: 10px;
        }
        .header .subtitle {
            opacity: 0.9;
            font-size: 16px;
        }
        .welcome-hero {
            background: linear-gradient(135deg, #fafcfa 0%, #f0f4f8 100%);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }
        .welcome-hero h2 {
            color: #052652;
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 400;
        }
        .welcome-hero .tagline {
            color: #6c757d;
            font-size: 18px;
            font-style: italic;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        .message {
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.8;
        }
        .features-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 30px 20px;
            margin: 30px 0;
        }
        .features-section h3 {
            color: #052652;
            font-size: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .feature-icon {
            font-size: 24px;
            margin-right: 15px;
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e3f2fd;
            border-radius: 50%;
        }
        .feature-content h4 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .feature-content p {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }
        .next-steps {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .next-steps h3 {
            color: #856404;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .next-steps ul {
            color: #856404;
            margin: 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin-bottom: 8px;
            font-size: 14px;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #052652;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(5, 38, 82, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            color: #ffffff !important;
            box-shadow: 0 6px 16px rgba(5, 38, 82, 0.4);
        }
        .btn-secondary {
            display: inline-block;
            padding: 12px 30px;
            background: transparent;
            color: #052652 !important;
            text-decoration: none;
            border: 2px solid #052652;
            border-radius: 25px;
            font-weight: 600;
            margin-left: 15px;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: #052652;
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
            display: grid;
            grid-template-columns: repeat(2, auto);
            justify-content: center;
            gap: 15px;
        }
        .social-links a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 8px 12px;
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
                padding: 25px 15px;
            }
            .welcome-hero {
                padding: 25px 15px;
            }
            .content {
                padding: 20px 15px;
            }
            .footer {
                padding: 20px 15px;
            }
            .features-section {
                padding: 20px 15px;
                margin: 20px 0;
            }
            .feature-item {
                flex-direction: column;
                text-align: center;
            }
            .feature-icon {
                margin: 0 auto 10px auto;
            }
            .btn-secondary {
                display: block;
                margin: 15px 0 0 0;
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
                padding: 20px 10px;
            }
            .welcome-hero {
                padding: 20px 10px;
            }
            .content {
                padding: 15px 10px;
            }
            .footer {
                padding: 15px 10px;
            }
            .features-section {
                padding: 15px 10px;
                margin: 15px 0;
            }
            .header h1 {
                font-size: 24px;
            }
            .welcome-hero h2 {
                font-size: 22px;
            }
            .header .subtitle, .welcome-hero .tagline {
                font-size: 14px;
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
            .features-section {
                padding: 12px 8px;
                margin: 12px 0;
            }
            .social-links {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .social-links a {
                display: block;
                width: 100%;
                max-width: 280px;
                text-align: center;
                margin: 0;
                font-size: 13px;
                padding: 10px 15px;
            }
            .header h1 {
                font-size: 20px;
            }
            .welcome-hero h2 {
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
                        <h1>Welcome to SupraCarer!</h1>
                        <div class="subtitle">Professional Healthcare Platform</div>
                    </div>

                    <!-- Welcome Hero -->
                    <div class="welcome-hero">
                        <h2>🎉 Your Healthcare Journey Begins Here</h2>
                        <div class="tagline">"Above and beyond care"</div>
                    </div>

                    <!-- Content -->
                    <div class="content">
                        <div class="greeting">
                            Dear {{ $user->name }},
                        </div>

                        <div class="message">
                            Welcome to SupraCarer! We're thrilled to have you join our community, committed to delivering exceptional care.
                        </div>

                        <div class="message">
                            Your account has been successfully created and verified.
                            You now have access to our comprehensive healthcare platform designed to
                            connect families with qualified healthcare professionals.
                        </div>

                        <!-- Features Section -->
                        <div class="features-section">
                            <h3>What You Can Do on SupraCarer</h3>

                            @if($user->role === 'client')
                                <div class="feature-item">
                                    <div class="feature-icon">📅</div>
                                    <div class="feature-content">
                                        <h4>Book Appointments</h4>
                                        <p>Schedule appointments at your convenience and manage your healthcare calendar.</p>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">💬</div>
                                    <div class="feature-content">
                                        <h4>Secure Messaging</h4>
                                        <p>Communicate directly with your healthcare providers through our secure messaging system.</p>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">📊</div>
                                    <div class="feature-content">
                                        <h4>Track Your Health</h4>
                                        <p>Monitor your appointments, health records, and care progress all in one place.</p>
                                    </div>
                                </div>
                            @elseif($user->role === 'healthworker')
                                <div class="feature-item">
                                    <div class="feature-icon">👥</div>
                                    <div class="feature-content">
                                        <h4>Connect with Families</h4>
                                        <p>Build meaningful relationships with families who need your professional healthcare services.</p>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">📅</div>
                                    <div class="feature-content">
                                        <h4>Manage Your Schedule</h4>
                                        <p>Control your availability and manage appointments efficiently through our scheduling system.</p>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">💼</div>
                                    <div class="feature-content">
                                        <h4>Professional Profile</h4>
                                        <p>Showcase your qualifications, specializations, and experience to potential clients.</p>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">📈</div>
                                    <div class="feature-content">
                                        <h4>Grow Your Practice</h4>
                                        <p>Expand your client base and grow your healthcare practice with our platform tools.</p>
                                    </div>
                                </div>
                            @else
                                <div class="feature-item">
                                    <div class="feature-icon">⚙️</div>
                                    <div class="feature-content">
                                        <h4>Platform Management</h4>
                                        <p>Oversee platform operations, user management, and ensure quality healthcare services.</p>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">📊</div>
                                    <div class="feature-content">
                                        <h4>Analytics & Reports</h4>
                                        <p>Access comprehensive reports and analytics to monitor platform performance.</p>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">🛡️</div>
                                    <div class="feature-content">
                                        <h4>Quality Assurance</h4>
                                        <p>Maintain high standards of care and ensure compliance with healthcare regulations.</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Next Steps -->
                        <div class="next-steps">
                            <h3>🚀 Get Started Today</h3>
                            <ul>
                                @if($user->role === 'client')
                                    <li>Complete your profile with information and preferences</li>
                                    <li>Book your first appointment</li>
                                    <li>Explore our platform features and support resources</li>
                                @elseif($user->role === 'healthworker')
                                    <li>Complete your professional profile and upload credentials</li>
                                    <li>Set your availability and service preferences</li>
                                    <li>Review platform policies and best practices</li>
                                    <li>Start connecting with families in need of your services</li>
                                @else
                                    <li>Familiarize yourself with the admin dashboard</li>
                                    <li>Review platform settings and configurations</li>
                                    <li>Monitor user activity and platform health</li>
                                    <li>Ensure all systems are operating smoothly</li>
                                @endif
                            </ul>
                        </div>

                        <div class="cta-section">
                            <a href="https://www.supracarer.com/signin" class="btn">Access Your Dashboard</a>
                            <a href="https://www.supracarer.com/contact-us" class="btn-secondary">Help Center</a>
                        </div>

                        <div class="message">
                            If you have any questions or need assistance getting started,
                            our support team is here to help. Don't hesitate to reach out!
                        </div>

                        <div class="message">
                            Thank you for choosing SupraCarer.
                            We're excited to be part of your healthcare journey!
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
