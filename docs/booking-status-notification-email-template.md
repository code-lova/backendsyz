# Enhanced Booking Status Notification Email Template

## Overview

The booking status notification email template (`resources/views/emails/booking_status_notification.blade.php`) has been completely redesigned to match the modern, responsive design of the booking confirmation template. This template is used to notify clients, health workers, and administrators about changes in booking status.

## Key Features

### 🎨 **Modern Design**

-   Responsive layout optimized for desktop, tablet, and mobile devices
-   Professional gradient header with SupraCarer branding
-   Clean typography and consistent spacing
-   Mobile-first design with collapsible detail rows

### 📧 **Multi-Recipient Template**

The template automatically adapts content based on the recipient type:

#### **Client View** (Default)

-   **Greeting**: "Hello [Client Name],"
-   **Message**: Status update with context about their appointment
-   **CTA Button**: "View My Bookings" → Client dashboard
-   **Content Focus**: Personal appointment status and next steps

#### **Health Worker View** (`is_for_healthworker = true`)

-   **Greeting**: "Hello [Health Worker Name],"
-   **Message**: Assignment notification with client details
-   **CTA Button**: "View Appointment Details" → Health Worker dashboard
-   **Content Focus**: Assignment confirmation and client information
-   **Additional Info**: Full client details visible

#### **Admin View** (`is_for_admin = true`)

-   **Greeting**: "Hello Admin,"
-   **Message**: Administrative notification about appointment status changes
-   **CTA Button**: "Manage Appointments" → Admin dashboard
-   **Content Focus**: Overview of status change and involved parties

### 📋 **Comprehensive Status Information**

The template displays all relevant appointment information:

-   **Reference Number**: Unique booking identifier
-   **Status**: Color-coded status badges with appropriate styling
-   **Participant Details**: Client, health worker information based on recipient
-   **Schedule**: Start/End dates and times with periods (AM/PM)
-   **Care Details**: Type, duration, accommodation, meals
-   **Special Cases**: Health worker assignments, reassignments
-   **Timestamps**: Status update date and time

### 🎯 **Dynamic Status Badges**

Status badges are automatically styled based on the status value:

```php
- 'Confirmed' → Green badge (status-confirmed)
- 'Processing' → Yellow badge (status-processing)
- 'Ongoing' → Blue badge (status-ongoing)
- 'Done/Completed' → Teal badge (status-done)
- 'Cancelled' → Red badge (status-cancelled)
- 'Rejected' → Red badge (status-rejected)
```

### 📱 **Responsive Design**

#### Desktop/Tablet (600px+)

-   Two-column detail layout (label | value)
-   Full-width content with generous padding
-   Hover effects on buttons and badges

#### Mobile (480px and below)

-   Optimized single-column layout
-   Compressed spacing for better mobile viewing
-   Touch-friendly button sizes
-   Readable font sizes with proper contrast

#### Small Mobile (360px and below)

-   Ultra-compact layout
-   Minimal padding and spacing
-   Optimized for small screens
-   Status badges maintain readability

## Template Variables

### Required Variables

```php
[
    'booking_reference' => 'BOOK-2025-001234',
    'status' => 'Confirmed',
    'client_name' => 'John Doe',
]
```

### Recipient Type Variables

```php
// For health worker emails
['is_for_healthworker' => true]

// For admin emails
['is_for_admin' => true]

// For client emails (default - no flag needed)
```

### Optional Variables

```php
[
    'healthworker_name' => 'Dr. Sarah Smith',
    'start_date' => '2025-10-01',
    'end_date' => '2025-10-05',
    'start_time' => '09:00:00',
    'end_time' => '17:00:00',
    'start_time_period' => 'AM',
    'end_time_period' => 'PM',
    'care_type' => 'Personal Care',
    'care_duration' => 'Multi-day',
    'care_duration_value' => '8',
    'accommodation' => 'Stay-in',
    'meal' => 'Yes',
    'num_of_meals' => '3',
    'processed_at' => '2025-09-28 14:30:00',

    // Special case variables
    'health_worker_assigned' => true,
    'health_worker_name' => 'New Worker Name',
    'health_worker_reassigned' => true,
    'new_health_worker_name' => 'New Worker',
    'previous_health_worker_name' => 'Previous Worker',
]
```

## Usage Examples

### Client Status Update

```php
$clientData = [
    'booking_reference' => 'BOOK-2025-001234',
    'status' => 'Confirmed',
    'client_name' => 'Jane Doe',
    'healthworker_name' => 'Dr. Smith',
    'start_date' => '2025-10-15',
    'end_date' => '2025-10-18',
    'processed_at' => now()->format('Y-m-d H:i:s')
];

Mail::to($client->email)->send(new BookingStatusNotification($clientData));
```

### Health Worker Assignment

```php
$healthWorkerData = [
    'booking_reference' => 'BOOK-2025-001234',
    'status' => 'Processing',
    'client_name' => 'Jane Doe',
    'healthworker_name' => 'Dr. Smith',
    'is_for_healthworker' => true,
    'health_worker_assigned' => true,
    'start_date' => '2025-10-15',
    'end_date' => '2025-10-18',
    'start_time' => '09:00:00',
    'end_time' => '17:00:00',
    'start_time_period' => 'AM',
    'end_time_period' => 'PM',
];

Mail::to($healthWorker->email)->send(new BookingStatusNotification($healthWorkerData));
```

### Admin Notification

```php
$adminData = [
    'booking_reference' => 'BOOK-2025-001234',
    'status' => 'Confirmed',
    'client_name' => 'Jane Doe',
    'healthworker_name' => 'Dr. Smith',
    'is_for_admin' => true,
    'processed_at' => now()->format('Y-m-d H:i:s')
];

Mail::to($adminEmail)->send(new BookingStatusNotification($adminData));
```

### Health Worker Reassignment

```php
$reassignmentData = [
    'booking_reference' => 'BOOK-2025-001234',
    'status' => 'Processing',
    'client_name' => 'Jane Doe',
    'health_worker_reassigned' => true,
    'previous_health_worker_name' => 'Dr. Smith',
    'new_health_worker_name' => 'Dr. Johnson',
    'start_date' => '2025-10-15'
];

Mail::to($client->email)->send(new BookingStatusNotification($reassignmentData));
```

## Testing

### Test Command

A comprehensive test command is available to preview all template variations:

```bash
# Test all templates (client, admin, health worker)
php artisan email:test-templates

# Test specific recipient type
php artisan email:test-templates --type=client
php artisan email:test-templates --type=admin
php artisan email:test-templates --type=healthworker
```

Generated test files are saved to:

-   `storage/app/test_booking_request_client.html`
-   `storage/app/test_booking_request_admin.html`
-   `storage/app/test_status_notification_client.html`
-   `storage/app/test_status_notification_admin.html`
-   `storage/app/test_status_notification_healthworker.html`

### Manual Testing

Create a test route for browser testing:

```php
Route::get('/test-status-email/{type}/{status}', function($type, $status) {
    $data = [
        'booking_reference' => 'TEST-2025-001',
        'status' => ucfirst($status),
        'client_name' => 'Test Client',
        'healthworker_name' => 'Test Health Worker',
        'start_date' => now()->addDays(7)->format('Y-m-d'),
        'end_date' => now()->addDays(10)->format('Y-m-d'),
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'start_time_period' => 'AM',
        'end_time_period' => 'PM',
        'processed_at' => now()->format('Y-m-d H:i:s')
    ];

    if ($type === 'admin') {
        $data['is_for_admin'] = true;
    } elseif ($type === 'healthworker') {
        $data['is_for_healthworker'] = true;
    }

    return view('emails.booking_status_notification', $data);
});
```

## Status Badge Styling

The template includes predefined styles for different status values:

```css
.status-confirmed {
    background: #d4edda;
    color: #155724;
}
.status-processing {
    background: #fff3cd;
    color: #856404;
}
.status-ongoing {
    background: #cce5ff;
    color: #004085;
}
.status-done {
    background: #d1ecf1;
    color: #0c5460;
}
.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}
.status-rejected {
    background: #f5c6cb;
    color: #721c24;
}
```

## Email Client Compatibility

The template is optimized for all major email clients:

-   ✅ Gmail (Web, Mobile, App)
-   ✅ Outlook (Web, Desktop, Mobile)
-   ✅ Apple Mail (macOS, iOS)
-   ✅ Yahoo Mail
-   ✅ Thunderbird
-   ✅ Mobile email clients

### Special Features

-   **Outlook Support**: MSO-prefixed CSS for better rendering
-   **Gmail Support**: Responsive design with media queries
-   **Mobile Clients**: Touch-friendly interface elements
-   **Dark Mode**: Compatible with both light and dark modes

## Customization

### Adding New Status Types

To add new status badges, update the PHP logic in the template:

```php
@php
switch(strtolower($status)) {
    case 'your_new_status':
        $statusClass = 'status-your-new-status';
        break;
    // ... existing cases
}
@endphp
```

Then add corresponding CSS:

```css
.status-your-new-status {
    background-color: #your-bg-color;
    color: #your-text-color;
}
```

### Branding Updates

-   **Logo**: Update `$logoUrl` variable
-   **Colors**: Modify CSS color values
-   **Typography**: Update font-family declarations

### Content Customization

-   **Messages**: Edit message blocks for different recipients
-   **CTA Buttons**: Update button text and destination URLs
-   **Footer**: Modify contact information and social links

## Migration Notes

### From Old Template

The enhanced template maintains backward compatibility:

1. **Existing Variables**: All current variables are supported
2. **New Features**: Additional optional variables for enhanced functionality
3. **Recipient Logic**: New recipient type flags for differentiated content
4. **Responsive Design**: Mobile-optimized layout with better rendering

### Performance Improvements

-   **Inline CSS**: All styles inline for maximum compatibility
-   **Optimized HTML**: Clean semantic structure
-   **Efficient Logic**: Streamlined template logic for faster rendering

## Troubleshooting

### Common Issues

1. **Status Badge Not Showing**

    - Verify status value matches expected cases
    - Check CSS class application in template

2. **Recipient Type Issues**

    - Ensure correct flag variables are set
    - Verify template logic conditions

3. **Time Format Issues**

    - Check date/time string formats
    - Verify timezone handling in application

4. **Mobile Rendering**
    - Test responsive breakpoints
    - Verify media query support in email client

### Debug Mode

Enable template debugging:

```php
try {
    $html = view('emails.booking_status_notification', $data)->render();
    return $html; // For browser testing
} catch (\Exception $e) {
    Log::error('Status notification template error: ' . $e->getMessage());
    dd($e); // For development debugging
}
```
