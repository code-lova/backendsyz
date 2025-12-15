@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png";
    $statusClass = match(strtolower($newStatus)) {
        'ongoing' => 'status-ongoing',
        'completed', 'done' => 'status-completed',
        'cancelled' => 'status-cancelled',
        default => 'status-confirmed'
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status Update</title>
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
        .booking-details {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .booking-details h3 {
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
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-ongoing {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-cancelled {
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
            display: grid;
            grid-template-columns: repeat(2, auto);
            justify-content: center;
            gap: 15px;
        }
        .social-links a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 8px 12px;
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
            .booking-details {
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
            .booking-details {
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
            .booking-details {
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
                            Hello {{ $recipient->name }},
                        </div>

                        @if($recipientType === 'admin')
                            <div class="message">
                                We're updating you on the status of appointment <strong>{{ $appointment->booking_reference }}</strong>. The health worker <strong>{{ $appointment->healthWorker->name }}</strong> has updated the appointment status to <strong>{{ ucfirst(strtolower($newStatus)) }}</strong>.
                            </div>
                            @if(strtolower($newStatus) === 'ongoing')
                                <div class="message">
                                    The health worker has arrived at the client's location and the care service has commenced. The client <strong>{{ $appointment->user->name }}</strong> is now receiving care.
                                </div>
                            @endif
                        @else
                            <div class="message">
                                Your appointment <strong>{{ $appointment->booking_reference }}</strong> status has been updated to <strong>{{ ucfirst(strtolower($newStatus)) }}</strong>.
                            </div>
                            @if(strtolower($newStatus) === 'ongoing')
                               <div class="message">
                                    You have successfully started the care service.
                                    Please ensure you provide the best possible care to your client. Once the service is completed,
                                    kindly ask your client to provide feedback and a rating for the care received,
                                    then update the service status accordingly.
                                </div>
                            @endif
                        @endif

                        <!-- Booking Details -->
                        <div class="booking-details">
                            <h3>📋 Appointment Details</h3>

                            <div class="detail-row">
                                <span class="detail-label">Reference Number:</span>
                                <span class="detail-value">{{ $appointment->booking_reference }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value">
                                    <span class="status-badge {{ $statusClass }}">{{ ucfirst(strtolower($newStatus)) }}</span>
                                </span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Client Name:</span>
                                <span class="detail-value">{{ $appointment->user->name }}</span>
                            </div>

                            @if($recipientType === 'admin')
                            <div class="detail-row">
                                <span class="detail-label">Health Worker:</span>
                                <span class="detail-value">{{ $appointment->healthWorker->name }}</span>
                            </div>
                            @endif

                            <div class="detail-row">
                                <span class="detail-label">Care Type:</span>
                                <span class="detail-value">{{ $appointment->care_type }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Date:</span>
                                <span class="detail-value">{{ date('F j, Y', strtotime($appointment->start_date)) }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Time:</span>
                                <span class="detail-value">{{ date('g:i', strtotime($appointment->start_time)) }} {{ $appointment->start_time_period }} - {{ date('g:i', strtotime($appointment->end_time)) }} {{ $appointment->end_time_period }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Location:</span>
                                <span class="detail-value">{{ $appointment->user->address }}, {{ $appointment->user->region }}, {{ $appointment->user->country }}</span>
                            </div>

                            @if($appointment->recurrence && $appointment->recurrence->is_recurring === 'Yes')
                            <div class="detail-row">
                                <span class="detail-label">Recurring:</span>
                                <span class="detail-value">
                                    <span class="status-badge" style="background-color: #e3f2fd; color: #1565c0;">Yes - {{ $appointment->recurrence->recurrence_type ?? 'N/A' }}</span>
                                </span>
                            </div>

                            @if($appointment->recurrence->recurrence_type === 'Weekly' && $appointment->recurrence->recurrence_days && count($appointment->recurrence->recurrence_days) > 0)
                            <div class="detail-row">
                                <span class="detail-label">Repeat Days:</span>
                                <span class="detail-value">{{ implode(', ', $appointment->recurrence->recurrence_days) }}</span>
                            </div>
                            @endif

                            @if($appointment->recurrence->recurrence_end_type)
                            <div class="detail-row">
                                <span class="detail-label">Ends:</span>
                                <span class="detail-value">
                                    @if($appointment->recurrence->recurrence_end_type === 'date' && $appointment->recurrence->recurrence_end_date)
                                        On {{ date('F j, Y', strtotime($appointment->recurrence->recurrence_end_date)) }}
                                    @elseif($appointment->recurrence->recurrence_end_type === 'occurrences' && $appointment->recurrence->recurrence_occurrences)
                                        After {{ $appointment->recurrence->recurrence_occurrences }} occurrence{{ $appointment->recurrence->recurrence_occurrences > 1 ? 's' : '' }}
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            @endif
                            @endif

                            <div class="detail-row">
                                <span class="detail-label">Updated At:</span>
                                <span class="detail-value">{{ date('F j, Y g:i A') }}</span>
                            </div>
                        </div>

                        @if($recipientType === 'healthworker')
                            <div class="cta-section">
                                <a href="https://www.supracarer.com/signin" class="btn">View Appointment Details</a>
                            </div>
                        @endif

                        <div class="message">
                            @if($recipientType === 'admin')
                                You can monitor the progress of this appointment through the admin dashboard. If you need to make any changes or have concerns, please coordinate with the health worker directly.
                            @else
                                If you have any questions or need assistance during the care service, please contact our support team through the platform.
                            @endif
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
