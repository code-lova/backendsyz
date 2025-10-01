@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Support Ticket Submitted</title>
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
        .ticket-details {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .ticket-details h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 20px;
        }

        /* Desktop and tablet layout */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
            min-height: 44px;
            gap: 15px;
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            flex: 0 0 auto;
            min-width: 120px;
            max-width: 180px;
            font-size: 14px;
            line-height: 1.4;
            align-self: flex-start;
            padding-top: 2px;
        }

        .detail-value {
            color: #6c757d;
            text-align: right;
            flex: 1;
            font-size: 14px;
            line-height: 1.4;
            min-width: 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
            align-self: flex-start;
            padding-top: 2px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1;
            white-space: nowrap;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .priority-high {
            background-color: #f8d7da;
            color: #721c24;
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
        }
        .social-links a {
            color: #ecf0f1;
            text-decoration: none;
            margin: 0 10px;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        .social-links a:hover {
            opacity: 1;
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
            .ticket-details {
                padding: 15px;
                margin: 20px 0;
            }

            /* Responsive detail rows for medium screens */
            .detail-label {
                min-width: 100px;
                max-width: 140px;
                font-size: 13px;
            }
            .detail-value {
                font-size: 13px;
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
            .ticket-details {
                padding: 12px;
                margin: 15px 0;
            }

            /* Mobile layout - label takes more space, value floats right */
            .detail-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: nowrap;
                gap: 10px;
                padding: 12px 0;
                min-height: 40px;
            }

            .detail-label {
                flex: 1;
                min-width: 0;
                max-width: none;
                text-align: left;
                font-size: 13px;
                font-weight: 600;
                color: #343a40;
                line-height: 1.3;
                padding-right: 10px;
            }

            .detail-value {
                flex: 0 0 auto;
                text-align: right;
                background: none !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                font-size: 13px;
                color: #6c757d;
                font-weight: 500;
                line-height: 1.3;
                max-width: 60%;
                word-break: break-word;
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
            .ticket-details {
                padding: 10px;
                margin: 12px 0;
            }
            .detail-row {
                padding: 10px 0;
                gap: 8px;
            }
            .detail-label {
                font-size: 12px;
                padding-right: 8px;
            }
            .detail-value {
                font-size: 12px;
                max-width: 65%;
            }
            .status-badge {
                font-size: 9px;
                padding: 3px 6px;
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

        /* Handle very long values on mobile */
        @media (max-width: 480px) {
            .detail-value {
                overflow-wrap: break-word;
                word-wrap: break-word;
                hyphens: auto;
                -webkit-hyphens: auto;
                -moz-hyphens: auto;
            }
        }

        /* Email client specific fixes */
        @media screen and (max-width: 480px) {
            .detail-row[style] {
                display: flex !important;
                flex-direction: row !important;
            }
        }

        /* Special handling for status badges on mobile */
        @media (max-width: 480px) {
            .status-badge {
                white-space: nowrap;
                flex-shrink: 0;
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
                            Hello Admin,
                        </div>

                        <div class="message">
                            A new support ticket has been submitted by a health worker and requires your attention. Please review the details below and respond as soon as possible.
                        </div>

                        <div class="message">
                            This ticket has been automatically assigned to the support team for processing. The health worker has been notified that their request has been received.
                        </div>

                        <!-- Ticket Details -->
                        <div class="ticket-details">
                            <h3>🚨 New Support Ticket Details</h3>

                            <div class="detail-row">
                                <span class="detail-label">Reference Number:</span>
                                <span class="detail-value">{{ $reference }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value">
                                    <span class="status-badge status-pending">New Ticket</span>
                                </span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Submitted By:</span>
                                <span class="detail-value">{{ $user_name }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">User Email:</span>
                                <span class="detail-value">{{ $user_email }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Subject:</span>
                                <span class="detail-value">{{ $subject }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Message:</span>
                                <span class="detail-value">{{ $support_message }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Priority:</span>
                                <span class="detail-value">
                                    <span class="status-badge priority-high">High</span>
                                </span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Submitted At:</span>
                                <span class="detail-value">{{ date('F j, Y g:i A') }}</span>
                            </div>
                        </div>

                        <div class="cta-section">
                            <a href="https://www.supracarer.com/signin" class="btn">View Support Dashboard</a>
                        </div>

                        <div class="message">
                            <strong>Recommended Actions:</strong><br>
                            • Review the ticket details carefully<br>
                            • Respond within 4-6 hours during business hours<br>
                            • Update ticket status as you progress<br>
                            • Contact the health worker directly if needed for clarification
                        </div>

                        <div class="message">
                            This is a high-priority notification as it comes from a health worker who may need immediate assistance to provide care services.
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="footer">
                        <div class="footer-content">
                            <h4>SupraCarer</h4>
                            <p>Above and beyond care</p>
                            <p>Professional Healthcare Services Platform</p>

                            <div class="social-links">
                                <a href="#">📧 support@supracarer.com</a>
                                <a href="#">🌐 www.supracarer.com</a>
                                <a href="#">📞 +1 (555) 123-4567</a>
                            </div>
                        </div>

                        <div class="disclaimer">
                            <p>This is an automated message from SupraCarer. Please do not reply to this email.</p>
                            <p>Use the admin dashboard to respond to this support ticket and communicate with the health worker.</p>
                            <p>&copy; {{ date('Y') }} SupraCarer. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
