@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png";
    $isForHealthWorker = isset($is_for_healthworker) && $is_for_healthworker;
    $isForAdmin = isset($is_for_admin) && $is_for_admin;
    $isForClient = !$isForHealthWorker && !$isForAdmin;
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
        .status-processing {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-ongoing {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-done {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-rejected {
            background-color: #f5c6cb;
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
                        @if($isForHealthWorker)
                            <div class="greeting">
                                Hello {{ isset($healthworker_name) && $healthworker_name ? $healthworker_name : 'Health Worker' }},
                            </div>

                            <div class="message">
                                You have been assigned to an appointment with <strong>{{ isset($client_name) && $client_name ? $client_name : 'a client' }}</strong>. The appointment status has been updated to <strong>{{ $status }}</strong>.
                            </div>
                            <div class="message">
                                Please login to your account as soon as possible to confirm this request and review the appointment details.
                            </div>

                        @elseif($isForAdmin)
                            <div class="greeting">
                                Hello Admin,
                            </div>

                            @if(isset($healthworker_name) && $healthworker_name)
                                <div class="message">
                                    The appointment with reference number <strong>{{ $booking_reference }}</strong> that was assigned to <strong>{{ $healthworker_name }}</strong> from <strong>{{ isset($client_name) && $client_name ? $client_name : 'client' }}</strong> is now <strong>{{ $status }}</strong>.
                                </div>
                            @else
                                <div class="message">
                                    The appointment with reference number <strong>{{ $booking_reference }}</strong> from <strong>{{ isset($client_name) && $client_name ? $client_name : 'client' }}</strong> is now <strong>{{ $status }}</strong>.
                                </div>
                            @endif

                        @else
                            <div class="greeting">
                                Hello {{ isset($client_name) && $client_name ? $client_name : (isset($healthworker_name) && $healthworker_name ? $healthworker_name : 'User') }},
                            </div>

                            <div class="message">
                                Your appointment request <strong>{{ $booking_reference }}</strong> status has been updated to <strong>{{ $status }}</strong>.
                            </div>
                            @if(isset($healthworker_name) && $healthworker_name)
                                <div class="message">
                                    <strong>Health Worker:</strong> {{ $healthworker_name }}
                                </div>
                            @endif
                        @endif

                        @if(isset($health_worker_reassigned) && $health_worker_reassigned && isset($new_health_worker_name))
                            <div class="message">
                                <strong>Health Worker Reassignment:</strong> A new health worker has been reassigned to your booking and is now awaiting confirmation.
                            </div>
                            @if(isset($previous_health_worker_name))
                                <div class="message">
                                    <strong>Previous Health Worker:</strong> {{ $previous_health_worker_name }}<br>
                                    <strong>New Health Worker:</strong> {{ $new_health_worker_name }}
                                </div>
                            @else
                                <div class="message">
                                    <strong>New Health Worker:</strong> {{ $new_health_worker_name }}
                                </div>
                            @endif
                        @elseif(isset($health_worker_assigned) && $health_worker_assigned && isset($health_worker_name))
                            <div class="message">
                                <strong>Good news!</strong> A health worker has been assigned to your booking and is awaiting confirmation: <strong>{{ $health_worker_name }}</strong>
                            </div>
                        @endif

                        <!-- Booking Details -->
                        <div class="booking-details">
                            <h3>📋 Appointment Details</h3>

                            <div class="detail-row">
                                <span class="detail-label">Reference Number:</span>
                                <span class="detail-value">{{ $booking_reference }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value">
                                    @php
                                        $statusClass = 'status-processing'; // default
                                        switch(strtolower($status)) {
                                            case 'Confirmed':
                                                $statusClass = 'status-confirmed';
                                                break;
                                            case 'Processing':
                                                $statusClass = 'status-processing';
                                                break;
                                            case 'Ongoing':
                                                $statusClass = 'status-ongoing';
                                                break;
                                            case 'Done':
                                                $statusClass = 'status-done';
                                                break;
                                            case 'Cancelled':
                                                $statusClass = 'status-cancelled';
                                                break;
                                        }
                                    @endphp
                                    <span class="status-badge {{ $statusClass }}">{{ $status }}</span>
                                </span>
                            </div>

                            @if($isForHealthWorker && isset($client_name))
                            <div class="detail-row">
                                <span class="detail-label">Client Name:</span>
                                <span class="detail-value">{{ $client_name }}</span>
                            </div>
                            @endif

                            @if(isset($care_type))
                            <div class="detail-row">
                                <span class="detail-label">Care Type:</span>
                                <span class="detail-value">{{ $care_type }}</span>
                            </div>
                            @endif

                            @if(isset($start_date))
                            <div class="detail-row">
                                <span class="detail-label">Start Date:</span>
                                <span class="detail-value">{{ date('F j, Y', strtotime($start_date)) }}</span>
                            </div>
                            @endif

                            @if(isset($end_date))
                            <div class="detail-row">
                                <span class="detail-label">End Date:</span>
                                <span class="detail-value">{{ date('F j, Y', strtotime($end_date)) }}</span>
                            </div>
                            @endif

                            @if(isset($start_time) && isset($end_time))
                            <div class="detail-row">
                                <span class="detail-label">Time:</span>
                                <span class="detail-value">
                                    {{ date('g:i', strtotime($start_time)) }} {{ isset($start_time_period) ? $start_time_period : '' }} -
                                    {{ date('g:i', strtotime($end_time)) }} {{ isset($end_time_period) ? $end_time_period : '' }}
                                </span>
                            </div>
                            @elseif(isset($start_time))
                            <div class="detail-row">
                                <span class="detail-label">Start Time:</span>
                                <span class="detail-value">{{ date('g:i', strtotime($start_time)) }} {{ isset($start_time_period) ? $start_time_period : '' }}</span>
                            </div>
                            @endif

                            @if(isset($care_duration))
                            <div class="detail-row">
                                <span class="detail-label">Duration:</span>
                                <span class="detail-value">{{ $care_duration }}{{ isset($care_duration_value) ? ', ' . $care_duration_value . 'hrs' : '' }}</span>
                            </div>
                            @endif

                            @if(isset($health_worker_reassigned) && $health_worker_reassigned && isset($new_health_worker_name))
                            <div class="detail-row">
                                <span class="detail-label">New Health Worker:</span>
                                <span class="detail-value">{{ $new_health_worker_name }}</span>
                            </div>
                            @if(isset($previous_health_worker_name))
                            <div class="detail-row">
                                <span class="detail-label">Previous Health Worker:</span>
                                <span class="detail-value">{{ $previous_health_worker_name }}</span>
                            </div>
                            @endif
                            @elseif(isset($health_worker_assigned) && $health_worker_assigned && isset($health_worker_name))
                            <div class="detail-row">
                                <span class="detail-label">Assigned Health Worker:</span>
                                <span class="detail-value">{{ $health_worker_name }}</span>
                            </div>
                            @elseif(isset($healthworker_name) && $healthworker_name && !$isForHealthWorker)
                            <div class="detail-row">
                                <span class="detail-label">Health Worker:</span>
                                <span class="detail-value">{{ $healthworker_name }}</span>
                            </div>
                            @endif

                            @if(isset($accommodation))
                            <div class="detail-row">
                                <span class="detail-label">Accommodation:</span>
                                <span class="detail-value">{{ $accommodation }}</span>
                            </div>
                            @endif

                            @if(isset($meal))
                            <div class="detail-row">
                                <span class="detail-label">Meals:</span>
                                <span class="detail-value">{{ $meal }}{{ isset($num_of_meals) ? ' (' . $num_of_meals . ' meals)' : '' }}</span>
                            </div>
                            @endif

                            @if(isset($is_recurring) && $is_recurring === 'Yes')
                            <div class="detail-row">
                                <span class="detail-label">Recurring:</span>
                                <span class="detail-value">
                                    <span class="status-badge" style="background-color: #e3f2fd; color: #1565c0;">Yes - {{ $recurrence_type ?? 'N/A' }}</span>
                                </span>
                            </div>

                            @if(isset($recurrence_type) && $recurrence_type === 'Weekly' && isset($recurrence_days) && is_array($recurrence_days) && count($recurrence_days) > 0)
                            <div class="detail-row">
                                <span class="detail-label">Repeat Days:</span>
                                <span class="detail-value">{{ implode(', ', $recurrence_days) }}</span>
                            </div>
                            @endif

                            @if(isset($recurrence_end_type))
                            <div class="detail-row">
                                <span class="detail-label">Ends:</span>
                                <span class="detail-value">
                                    @if($recurrence_end_type === 'date' && isset($recurrence_end_date))
                                        On {{ date('F j, Y', strtotime($recurrence_end_date)) }}
                                    @elseif($recurrence_end_type === 'occurrences' && isset($recurrence_occurrences))
                                        After {{ $recurrence_occurrences }} occurrence{{ $recurrence_occurrences > 1 ? 's' : '' }}
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            @endif
                            @endif

                            <div class="detail-row">
                                <span class="detail-label">Updated On:</span>
                                <span class="detail-value">{{ isset($processed_at) ? date('F j, Y g:i A', strtotime($processed_at)) : (isset($completed_at) ? date('F j, Y g:i A', strtotime($completed_at)) : (isset($cancelled_at) ? date('F j, Y g:i A', strtotime($cancelled_at)) : date('F j, Y g:i A'))) }}</span>
                            </div>
                        </div>

                        @if($isForHealthWorker)
                            <div class="cta-section">
                                <a href="https://www.supracarer.com/healthworker/dashboard" class="btn">View Appointment Details</a>
                            </div>
                        @elseif($isForAdmin)
                            <div class="cta-section">
                                <a href="https://www.supracarer.com/admin/dashboard" class="btn">Manage Appointments</a>
                            </div>
                        @else
                            <div class="cta-section">
                                <a href="https://www.supracarer.com/client/dashboard" class="btn">View My Bookings</a>
                            </div>
                        @endif

                        <div class="message">
                            @if($isForHealthWorker)
                                If you have any questions about this appointment or need additional information, please contact our support team or
                                the client through the platform messaging system.
                            @elseif($isForAdmin)
                                You can monitor and manage this appointment through the admin dashboard.
                                If any issues arise, coordinate with the health worker or client as needed.
                            @else
                                If you have any questions about your appointment or need assistance,
                                please don't hesitate to contact our support team.
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
