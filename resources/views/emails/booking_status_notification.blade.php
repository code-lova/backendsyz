<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Status Update</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f7f7f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 32px;
        }
        h2 {
            color: #2d3748;
            margin-bottom: 16px;
        }
        .details {
            margin-bottom: 24px;
        }
        .details th {
            text-align: left;
            padding-right: 16px;
            color: #4a5568;
        }
        .details td {
            color: #2d3748;
        }
        .footer {
            margin-top: 32px;
            font-size: 14px;
            color: #718096;
            text-align: center;
        }
        .brand {
            font-weight: bold;
            color: #3182ce;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Appointment Status Update</h2>
        @if(isset($is_for_healthworker) && $is_for_healthworker)
            <p>Dear {{ isset($healthworker_name) && $healthworker_name ? $healthworker_name : 'Health Worker' }},</p>
            <p>You have been assigned to an appointment with {{ isset($client_name) && $client_name ? $client_name : 'client' }}. The appointment status has been updated to <strong>{{ $status }}</strong>.</p>
            <p>Please login to your account ASAP to confirm this request.</p>
            <p><strong>Appointment Details:</strong></p>
        @elseif(isset($is_for_admin) && $is_for_admin)
            <p>Dear Admin,</p>
            @if(isset($healthworker_name) && $healthworker_name)
                <p>The appointment with reference number <strong>{{ $booking_reference }}</strong> that was assigned to {{ $healthworker_name }} from {{ isset($client_name) && $client_name ? $client_name : 'client' }} is now <strong>{{ $status }}</strong>.</p>
            @else
                <p>The appointment with reference number <strong>{{ $booking_reference }}</strong> from {{ isset($client_name) && $client_name ? $client_name : 'client' }} is now <strong>{{ $status }}</strong>.</p>
            @endif
        @else
            <p>Dear {{ isset($client_name) && $client_name ? $client_name : (isset($healthworker_name) && $healthworker_name ? $healthworker_name : 'User') }},</p>
            <p>Your Appointment request <strong>{{ $booking_reference }}</strong> status has been updated to <strong>{{ $status }}</strong>.</p>
            @if(isset($healthworker_name) && $healthworker_name)
                <p><strong>With Health Worker:</strong> {{ $healthworker_name }}</p>
            @endif
        @endif
        
        @if(isset($health_worker_reassigned) && $health_worker_reassigned && isset($new_health_worker_name))
            <p><strong>Health Worker Reassignment:</strong> A new health worker has been reassigned to your booking, now awaiting confirmation.</p>
            @if(isset($previous_health_worker_name))
                <p>Previous health worker: <strong>{{ $previous_health_worker_name }}</strong></p>
            @endif
            <p>New health worker: <strong>{{ $new_health_worker_name }}</strong></p>
        @elseif(isset($health_worker_assigned) && $health_worker_assigned && isset($health_worker_name))
            <p><strong>Good news!</strong> A health worker has been assigned to your booking awaiting confirmation: <strong>{{ $health_worker_name }}</strong></p>
        @endif
        
        <table class="details">
            <tr>
                <th>Booking Reference:</th>
                <td>{{ $booking_reference }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>{{ $status }}</td>
            </tr>
            @if(isset($is_for_healthworker) && $is_for_healthworker)
            <tr>
                <th>Client Name:</th>
                <td>{{ $client_name }}</td>
            </tr>
            @endif
            @if(isset($start_date))
            <tr>
                <th>Start Date:</th>
                <td>{{ $start_date }}</td>
            </tr>
            @endif
            @if(isset($end_date))
            <tr>
                <th>End Date:</th>
                <td>{{ $end_date }}</td>
            </tr>
            @endif
            @if(isset($start_time))
            <tr>
                <th>Start Time:</th>
                <td>{{ $start_time }}</td>
            </tr>
            @endif
            @if(isset($end_time))
            <tr>
                <th>End Time:</th>
                <td>{{ $end_time }}</td>
            </tr>
            @endif
            @if(isset($health_worker_reassigned) && $health_worker_reassigned && isset($new_health_worker_name))
            <tr>
                <th>New Health Worker:</th>
                <td>{{ $new_health_worker_name }}</td>
            </tr>
            @elseif(isset($health_worker_assigned) && $health_worker_assigned && isset($health_worker_name))
            <tr>
                <th>Assigned Health Worker:</th>
                <td>{{ $health_worker_name }}</td>
            </tr>
            @elseif(isset($healthworker_name) && $healthworker_name && !isset($is_for_healthworker))
            <tr>
                <th>Health Worker:</th>
                <td>{{ $healthworker_name }}</td>
            </tr>
            @endif
            <tr>
                <th>Date:</th>
                <td>{{ $processed_at ?? $completed_at ?? $cancelled_at ?? now() }}</td>
            </tr>
        </table>
        <p>If you have any questions, please contact our support team.</p>
        <div class="footer">
            <span class="brand">Supracarer</span> &mdash; Professional Home Care Services<br>
            This is an automated message. Please do not reply directly to this email.
        </div>
    </div>
</body>
</html>
