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
        <h2>New Support Ticket Submitted</h2>
        <p>A new support ticket has been submitted by a user:</p>
        <table class="details">
            <tr>
                <th>User Name:</th>
                <td>{{ $user_name }}</td>
            </tr>
            <tr>
                <th>User Email:</th>
                <td>{{ $user_email }}</td>
            </tr>
            <tr>
                <th>Subject:</th>
                <td>{{ $subject }}</td>
            </tr>
            <tr>
                <th>Message:</th>
                <td>{{ $message }}</td>
            </tr>
        </table>
        <p>Please review and respond to this ticket as soon as possible.</p>
        <div class="footer">
            <span class="brand">Supracarer</span> &mdash; Professional Care Services<br>
            This is an automated message. Please do not reply directly to this email.
        </div>
    </div>
</body>
</html>
