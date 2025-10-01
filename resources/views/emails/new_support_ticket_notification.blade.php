<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Support Ticket</title>
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
        .alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .details {
            margin-bottom: 24px;
        }
        .details th {
            text-align: left;
            padding-right: 16px;
            color: #4a5568;
            vertical-align: top;
            padding-bottom: 8px;
        }
        .details td {
            color: #2d3748;
            padding-bottom: 8px;
        }
        .message-box {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 16px;
            margin: 16px 0;
            border-radius: 4px;
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
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 16px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🎫 New Support Ticket Created</h2>
        
        <div class="alert">
            <strong>Action Required:</strong> A new support ticket has been submitted and requires admin attention.
        </div>
        
        <table class="details">
            <tr>
                <th>Ticket Reference:</th>
                <td><strong>{{ $ticketData['reference'] }}</strong></td>
            </tr>
            <tr>
                <th>Subject:</th>
                <td>{{ $ticketData['subject'] }}</td>
            </tr>
            <tr>
                <th>Client Name:</th>
                <td>{{ $ticketData['client_name'] }}</td>
            </tr>
            <tr>
                <th>Client Email:</th>
                <td>{{ $ticketData['client_email'] }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">{{ $ticketData['status'] }}</span></td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td>{{ $ticketData['created_at'] }}</td>
            </tr>
        </table>
        
        <div class="message-box">
            <strong>Message:</strong><br>
            {{ $ticketData['message'] }}
        </div>
        
        <p>Please review and respond to this ticket as soon as possible.</p>
        
        <a href="{{ config('app.admin_url') }}/support-tickets/{{ $ticketData['uuid'] }}" class="btn">
            View Ticket in Admin Panel
        </a>
        
        <div class="footer">
            <span class="brand">Supracarer</span> &mdash; Professional Home Care Services<br>
            This is an automated notification. Please do not reply directly to this email.
        </div>
    </div>
</body>
</html>
