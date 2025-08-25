<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Booking Request</title>
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
        <h2>New Booking Request Received</h2>
        <p>Dear {{ $client_name }},</p>
        <p>Thank you for submitting your booking request. Below are your booking details:</p>
        <table class="details">
            <tr>
                <th>Booking Reference:</th>
                <td>{{ $booking_reference }}</td>
            </tr>
            <tr>
                <th>Client Name:</th>
                <td>{{ $client_name }}</td>
            </tr>
            <tr>
                <th>Client Email:</th>
                <td>{{ $client_email }}</td>
            </tr>
            <tr>
                <th>Start Date:</th>
                <td>{{ $start_date }}</td>
            </tr>
            <tr>
                <th>End Date:</th>
                <td>{{ $end_date }}</td>
            </tr>
            <!-- Add more booking details as needed -->
        </table>
        <p>We will review your request and get back to you shortly. If you have any questions, please reply to this email.</p>
        <div class="footer">
            <span class="brand">Supracarer</span> &mdash; Professional Care Services<br>
            This is an automated message. Please do not reply directly to this email.
        </div>
    </div>
</body>
</html>
