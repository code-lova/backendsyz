# Enhanced Booking Request Email Template

## Overview

The booking request email template (`resources/views/emails/booking_request.blade.php`) has been redesigned to provide a modern, responsive, and professional appearance that matches the booking confirmation template design.

## Key Features

### 🎨 **Modern Design**

-   Responsive layout that works on desktop, tablet, and mobile devices
-   Professional gradient header with SupraCarer branding
-   Clean typography and consistent spacing
-   Mobile-optimized with collapsible detail rows

### 📧 **Dual-Purpose Template**

The template automatically adapts content based on the recipient:

#### **Client View** (Default)

-   **Greeting**: "Hello [Client Name],"
-   **Message**: Thank you message with reassurance about request processing
-   **CTA Button**: "View My Bookings" → Client dashboard
-   **Content Focus**: Request confirmation and next steps

#### **Admin View** (`recipient_type = 'admin'`)

-   **Greeting**: "Hello Admin,"
-   **Message**: Alert about new booking request requiring assignment
-   **CTA Button**: "Review & Assign Health Worker" → Admin dashboard
-   **Content Focus**: Action required and client details
-   **Additional Info**: Client email address visible for admin

### 📋 **Comprehensive Booking Details**

The template displays all relevant booking information:

-   **Reference Number**: Unique booking identifier
-   **Status**: "Pending Review" with styled badge
-   **Client Information**: Name and (admin-only) email
-   **Care Details**: Type, duration, requesting for
-   **Schedule**: Start/End dates and times with periods (AM/PM)
-   **Services**: Accommodation, meals, special notes
-   **Timestamps**: Submission date and time

### 📱 **Responsive Design**

#### Desktop/Tablet (600px+)

-   Two-column detail layout (label | value)
-   Full-width content with generous padding
-   Hover effects on buttons

#### Mobile (480px and below)

-   Optimized single-column layout
-   Compressed spacing for better mobile viewing
-   Touch-friendly button sizes
-   Readable font sizes

#### Small Mobile (360px and below)

-   Ultra-compact layout
-   Minimal padding and spacing
-   Optimized for small screens

## Template Variables

### Required Variables

```php
[
    'booking_reference' => 'BOOK-2025-001234',
    'client_name' => 'John Doe',
    'client_email' => 'john.doe@example.com',
    'start_date' => '2025-10-01',
    'end_date' => '2025-10-05',
]
```

### Optional Variables

```php
[
    'recipient_type' => 'admin', // 'admin' for admin view, omit for client view
    'start_time' => '09:00:00',
    'end_time' => '17:00:00',
    'start_time_period' => 'AM',
    'end_time_period' => 'PM',
    'care_type' => 'Home Care',
    'care_duration' => 'Multi-day',
    'care_duration_value' => '8',
    'requesting_for' => 'Myself',
    'accommodation' => 'Stay-in',
    'meal' => 'Yes',
    'num_of_meals' => '3',
    'special_notes' => 'Patient requires assistance...',
]
```

## Usage in Controllers

### Updated BookingAppointmentController

```php
// Client email (default view)
$clientMailData = $baseMailData;
Mail::to($clientEmail)->send(new NewBookingRequest($clientMailData));

// Admin email (admin-specific view)
$adminMailData = array_merge($baseMailData, [
    'recipient_type' => 'admin'
]);
Mail::to($adminEmail)->send(new NewBookingRequest($adminMailData));
```

## Testing

### Test Command

A test command is available to preview both email templates:

```bash
# Test both client and admin templates
php artisan email:test-templates

# Test specific template
php artisan email:test-templates --type=client
php artisan email:test-templates --type=admin
```

Generated test files are saved to:

-   `storage/app/test_client_email.html`
-   `storage/app/test_admin_email.html`

### Manual Testing

You can also test the templates in a browser by opening the generated HTML files or by creating a test route:

```php
Route::get('/test-email/{type}', function($type) {
    $data = [
        'booking_reference' => 'TEST-2025-001',
        'client_name' => 'Test User',
        'client_email' => 'test@example.com',
        'start_date' => now()->addDays(7)->format('Y-m-d'),
        'end_date' => now()->addDays(10)->format('Y-m-d'),
        // ... other test data
    ];

    if ($type === 'admin') {
        $data['recipient_type'] = 'admin';
    }

    return view('emails.booking_request', $data);
});
```

## Email Client Compatibility

The template is optimized for:

-   ✅ Gmail (Web, Mobile, App)
-   ✅ Outlook (Web, Desktop, Mobile)
-   ✅ Apple Mail (macOS, iOS)
-   ✅ Yahoo Mail
-   ✅ Thunderbird
-   ✅ Mobile email clients (iOS Mail, Android Gmail)

### Email Client Specific Features

-   **Outlook**: Uses `mso-` prefixed CSS for better compatibility
-   **Gmail**: Responsive design with media queries
-   **Mobile Clients**: Touch-friendly buttons and readable text sizes
-   **Dark Mode**: Supports both light and dark mode viewing

## Customization

### Branding

-   Logo URL: Update `$logoUrl` variable in template
-   Colors: Modify CSS variables for brand colors
-   Fonts: Update font-family declarations

### Content

-   Messages: Edit the message blocks for different recipient types
-   CTA Buttons: Update button text and URLs
-   Footer: Modify footer content and contact information

### Styling

-   Layout: Adjust `.detail-row` styling for different layouts
-   Colors: Update status badge colors and gradients
-   Spacing: Modify padding and margin values

## Migration Notes

### From Old Template

The new template is backward compatible with existing mail data but provides enhanced features:

1. **Same Variables**: All existing variables are supported
2. **New Variables**: Additional optional variables for enhanced details
3. **Recipient Type**: New `recipient_type` variable for differentiated content
4. **Responsive**: Mobile-optimized layout with better email client support

### Performance

-   **Inline CSS**: All styles are inline for maximum email client compatibility
-   **Optimized Images**: Logo and images are optimized for email
-   **Clean HTML**: Semantic HTML structure for better rendering

## Troubleshooting

### Common Issues

1. **Images Not Loading**

    - Verify logo URL is publicly accessible
    - Check email client image blocking settings

2. **Layout Issues**

    - Test in multiple email clients
    - Verify inline CSS is properly applied

3. **Mobile Rendering**
    - Check viewport meta tag
    - Test responsive breakpoints

### Debug Mode

Enable debug mode to see template rendering errors:

```php
// In mail class or controller
try {
    return view('emails.booking_request', $data);
} catch (\Exception $e) {
    Log::error('Email template error: ' . $e->getMessage());
    throw $e;
}
```
