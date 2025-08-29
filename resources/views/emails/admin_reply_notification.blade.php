@php
    $logoUrl = "https://www.supracarer.com/assets/images/logo.png"; // Place your logo in public/uploads/profile/logo.png
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Support Team Response</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f7f7f7;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #94cbff, #99bdf3);
            color: white;
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 32px;
        }
        .ticket-info {
            background: #f8fafc;
            border-left: 4px solid #3182ce;
            padding: 16px;
            margin: 24px 0;
            border-radius: 4px;
        }
        .ticket-info h3 {
            margin: 0 0 8px 0;
            color: #2d3748;
            font-size: 16px;
        }
        .ticket-info p {
            margin: 4px 0;
            color: #4a5568;
        }
        .reply-content {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin: 24px 0;
        }
        .reply-content h4 {
            color: #2d3748;
            margin: 0 0 12px 0;
            font-size: 16px;
        }
        .reply-text {
            color: #4a5568;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .admin-signature {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            color: #718096;
            font-size: 14px;
        }
        .cta-section {
            text-align: center;
            margin: 32px 0;
        }
        .cta-button {
            display: inline-block;
            background: #3182ce;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .cta-button:hover {
            background: #2c5aa0;
        }
        .footer {
            background: #f8fafc;
            padding: 24px 32px;
            font-size: 14px;
            color: #718096;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .brand {
            font-weight: bold;
            color: #3182ce;
        }
        .note {
            background: #fef5e7;
            border: 1px solid #f6e05e;
            border-radius: 4px;
            padding: 12px;
            margin: 16px 0;
            color: #744210;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logoUrl }}" alt="Logo" style="height: 48px; margin-bottom: 10px;">
            <h1>🎧 Support Team Response</h1>
            <p style="margin: 8px 0 0 0; opacity: 0.9;">We've responded to your support ticket</p>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $client_name }}</strong>,</p>
            
            <p>Good news! Our support team has responded to your ticket. Here are the details:</p>
            
            <div class="ticket-info">
                <h3>📋 Ticket Information</h3>
                <p><strong>Reference:</strong> #{{ $ticket_reference }}</p>
                <p><strong>Subject:</strong> {{ $ticket_subject }}</p>
                <p><strong>Status:</strong> {{ $ticket_status }}</p>
                <p><strong>Response Date:</strong> {{ $response_date }}</p>
            </div>
            
            <div class="reply-content">
                <h4>💬 Our Response:</h4>
                <div class="reply-text">{{ $admin_reply }}</div>
                
                <div class="admin-signature">
                    <strong>{{ $admin_name }}</strong><br>
                    <em>Supracarer Support Team</em>
                </div>
            </div>
            
            @if($ticket_status === 'Open')
            <div class="note">
                <strong>💡 Need to continue the conversation?</strong> You can reply to this ticket to ask follow-up questions or provide additional information.
            </div>
            
            <div class="cta-section">
                <a href="#" class="cta-button">View & Reply to Ticket</a>
            </div>
            @else
            <div class="note">
                <strong>✅ Ticket Closed:</strong> This ticket has been marked as resolved. If you need further assistance, please create a new support ticket.
            </div>
            @endif
            
            <p>If you have any questions or need further assistance, please don't hesitate to reach out to our support team.</p>
            
            <p>Best regards,<br>
            <strong>The Supracarer Support Team</strong></p>
        </div>
        
        <div class="footer">
            <span class="brand">Supracarer</span> &mdash; Professional Care Services<br>
            This is an automated notification. Please use our support system to reply to this ticket.<br>
            <small>You're receiving this email because you have an active support ticket with us.</small>
        </div>
    </div>
</body>
</html>
