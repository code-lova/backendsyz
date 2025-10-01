# Email Templates Documentation - SupraCarer

## Overview

This document outlines the email template system implemented for SupraCarer, including best practices, design principles, and implementation details.

## Email Templates

### 1. Email Verification Template (`verify-code.blade.php`)

**Purpose**: Sent during user registration to verify email addresses.

**Features**:

-   Modern, responsive design matching the booking confirmation template
-   Prominent verification code display with enhanced styling
-   Clear expiration notice (10 minutes)
-   Mobile-first responsive design
-   Email client compatibility optimizations

**Usage**:

```php
Mail::to($user->email)->send(new VerifyEmailCodeMail($user));
```

**Design Elements**:

-   Gradient header with SupraCarer branding
-   Large, styled verification code with monospace font
-   Warning section for code expiration
-   Call-to-action button
-   Professional footer with contact information

### 2. Welcome Email Template (`welcome.blade.php`)

**Purpose**: Sent after successful email verification to welcome new users.

**Features**:

-   Role-specific content (client, healthworker, admin)
-   Personalized feature highlights based on user role
-   Next steps guidance
-   Modern card-based design
-   Comprehensive feature overview

**Usage**:

```php
Mail::to($user->email)->send(new WelcomeEmail($user));
```

**Role-Specific Features**:

#### For Clients:

-   Find healthcare professionals
-   Book appointments
-   Secure messaging
-   Track health records

#### For Health Workers:

-   Connect with clients
-   Manage schedule
-   Professional profile showcase
-   Practice growth tools

#### For Admins:

-   Platform management
-   Analytics and reports
-   Quality assurance tools

## Best Practices Implementation

### 1. Email Flow Strategy

**Registration Flow**:

1. User registers → Send verification email
2. User verifies email → Send welcome email + notify admins
3. User completes profile → Ready to use platform

**Benefits**:

-   Only verified users receive welcome emails
-   Reduces email deliverability issues
-   Ensures clean user database
-   Better user experience

### 2. Design Consistency

**Common Design Elements**:

-   Consistent header with SupraCarer branding
-   Professional color scheme (#052652, #f8f9fa)
-   Responsive design for all devices
-   Email client compatibility
-   Modern typography and spacing

### 3. Mobile Optimization

**Responsive Breakpoints**:

-   Desktop: 600px+
-   Tablet: 480-600px
-   Mobile: 360-480px
-   Small mobile: <360px

**Mobile Features**:

-   Scalable images
-   Touch-friendly buttons
-   Readable font sizes
-   Optimized layouts

### 4. Email Client Compatibility

**Supported Clients**:

-   Gmail (web, mobile)
-   Outlook (2016+, web, mobile)
-   Apple Mail
-   Yahoo Mail
-   Thunderbird

**Compatibility Features**:

-   Inline CSS for better rendering
-   Table-based layouts for Outlook
-   Fallback fonts
-   Progressive enhancement

## Implementation Details

### Mail Classes

#### VerifyEmailCodeMail

```php
<?php
namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

class VerifyEmailCodeMail extends Mailable
{
    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email - SupraCarer',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-code',
            with: [
                'name' => $this->user->name,
                'email_verification_code' => $this->user->email_verification_code,
            ]
        );
    }
}
```

#### WelcomeEmail

```php
<?php
namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to SupraCarer - Your Healthcare Journey Begins!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: ['user' => $this->user]
        );
    }
}
```

### AuthController Integration

**Registration Process**:

```php
// During registration - send verification email
Mail::to($user->email)->send(new VerifyEmailCodeMail($user));

// Notify admins about new registration
$this->notificationService->notifyAdminNewUserRegistration(
    $user->name,
    $user->role,
    $user->email
);
```

**Email Verification Process**:

```php
// After successful verification - send welcome email
Mail::to($user->email)->send(new WelcomeEmail($user));

// Notify admins about verification
$this->notificationService->notifyAdminUserEmailVerified(
    $user->name,
    $user->role,
    $user->email
);
```

### Notification System Integration

**New Notification Methods**:

-   `notifyAdminNewUserRegistration()` - Alerts admins of new registrations
-   `notifyAdminUserEmailVerified()` - Alerts admins of successful verifications

## Testing

### Test Command Usage

```bash
# Test verification email template
php artisan email:test-templates --type=verify

# Test welcome email templates (all roles)
php artisan email:test-templates --type=welcome

# Test all email templates
php artisan email:test-templates --type=all
```

### Generated Test Files

**Verification Email**:

-   `storage/app/test_verification_email.html`

**Welcome Emails**:

-   `storage/app/test_welcome_email_client.html`
-   `storage/app/test_welcome_email_healthworker.html`
-   `storage/app/test_welcome_email_admin.html`

## Email Deliverability

### Best Practices Implemented

1. **Content Quality**:

    - Professional design and copy
    - Clear value proposition
    - No spam trigger words

2. **Technical Implementation**:

    - Proper HTML structure
    - Alt text for images
    - Unsubscribe links in footer
    - Domain authentication (SPF, DKIM)

3. **User Experience**:
    - Clear call-to-action buttons
    - Mobile-responsive design
    - Fast loading times
    - Accessible content

## Security Considerations

### Email Security Features

1. **Verification Codes**:

    - 6-character alphanumeric codes
    - 10-minute expiration
    - Case-insensitive matching
    - Secure random generation

2. **Welcome Email Timing**:

    - Only sent after email verification
    - Prevents spam to unverified addresses
    - Ensures legitimate user engagement

3. **Content Security**:
    - No external resources in critical paths
    - Sanitized user data in templates
    - Proper escaping of dynamic content

## Maintenance

### Regular Tasks

1. **Template Updates**:

    - Review design consistency quarterly
    - Update branding elements as needed
    - Test across new email clients

2. **Performance Monitoring**:

    - Track email delivery rates
    - Monitor user engagement metrics
    - Analyze template rendering issues

3. **Content Review**:
    - Update copy for clarity
    - Ensure compliance with regulations
    - Refresh feature descriptions

## Customization

### Template Variables

**Verification Email**:

-   `$name` - User's full name
-   `$email_verification_code` - 6-digit verification code

**Welcome Email**:

-   `$user` - Complete user object
-   `$user->role` - User role (client, healthworker, admin)
-   `$user->name` - User's full name

### Styling Customization

**CSS Variables** (can be customized):

-   Primary color: `#052652`
-   Background color: `#f8f9fa`
-   Text color: `#333333`
-   Border radius: `10px`

### Adding New Templates

1. Create Blade template in `resources/views/emails/`
2. Create corresponding Mail class in `app/Mail/`
3. Add test method to `TestEmailTemplates` command
4. Update documentation

## Troubleshooting

### Common Issues

1. **Email Not Rendering Properly**:

    - Check email client compatibility
    - Validate HTML structure
    - Test inline CSS support

2. **Images Not Loading**:

    - Verify image URLs are accessible
    - Check HTTPS/HTTP consistency
    - Provide alt text fallbacks

3. **Mobile Display Issues**:

    - Test on actual devices
    - Validate responsive breakpoints
    - Check font sizes and touch targets

4. **Delivery Issues**:
    - Verify email configuration
    - Check spam folder
    - Monitor delivery logs

---

**Last Updated**: September 28, 2025
**Version**: 1.0
**Maintainer**: SupraCarer Development Team
