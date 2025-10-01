# Support Request Rate Limiting Documentation

## Overview

The support request system implements a comprehensive rate limiting strategy to prevent abuse while ensuring legitimate users can get help when needed.

## Rate Limiting Strategy

### 1. Business Logic Rate Limiting (Database-based)

-   **Daily Limit**: 3 support requests per day per user
-   **Pending Ticket Limit**: Only 1 pending ticket allowed at a time
-   **Reset Time**: Daily limit resets at midnight (start of next day)

**Benefits**:

-   Prevents support system overload
-   Encourages users to provide comprehensive information in each ticket
-   Allows proper resource allocation for support team

### 2. Technical Rate Limiting (Laravel's Built-in)

-   **Per Minute**: 5 requests per minute per user
-   **Per Hour**: 10 requests per hour per user
-   **Scope**: Applied to the POST `/api/support` endpoint

**Benefits**:

-   Prevents rapid-fire spam attempts
-   Protects against automated abuse
-   Ensures system stability

## Implementation Details

### AppServiceProvider Configuration

```php
RateLimiter::for('support', function (Request $request) {
    return [
        // Allow 5 requests per minute per user (to prevent spam/abuse)
        Limit::perMinute(5)->by($request->user()?->uuid ?: $request->ip()),
        // Allow 10 requests per hour per user (additional protection)
        Limit::perHour(10)->by($request->user()?->uuid ?: $request->ip()),
    ];
});
```

### Route Configuration

```php
Route::post('/support', 'createSupportMessage')->middleware('throttle:support');
```

### Database Logic in Controller

```php
// Check daily rate limit (3 support requests per day)
$todayStart = now()->startOfDay();
$todayEnd = now()->endOfDay();

$todayRequestsCount = SupportMessage::where('user_uuid', $user->uuid)
    ->whereBetween('created_at', [$todayStart, $todayEnd])
    ->count();

$dailyLimit = 3;
if ($todayRequestsCount >= $dailyLimit) {
    return response()->json([
        'message' => "You have reached your daily limit of {$dailyLimit} support requests. Please try again tomorrow.",
        'meta' => [
            'daily_limit' => $dailyLimit,
            'requests_today' => $todayRequestsCount,
            'reset_time' => now()->endOfDay()->diffForHumans(),
            'next_reset' => now()->addDay()->startOfDay()->toISOString()
        ]
    ], 429);
}
```

## API Endpoints

### 1. Create Support Message

**Endpoint**: `POST /api/support`
**Rate Limits**:

-   Technical: 5/minute, 10/hour
-   Business: 3/day, 1 pending at a time

**Response when limit exceeded**:

```json
{
    "message": "You have reached your daily limit of 3 support requests. Please try again tomorrow.",
    "meta": {
        "daily_limit": 3,
        "requests_today": 3,
        "reset_time": "in 8 hours",
        "next_reset": "2025-09-29T00:00:00.000000Z"
    }
}
```

### 2. Get Support Limits

**Endpoint**: `GET /api/support/limits`
**Purpose**: Check current usage without creating a ticket

**Response**:

```json
{
    "daily_limit": 3,
    "requests_today": 1,
    "remaining_requests": 2,
    "can_create_ticket": true,
    "has_pending_ticket": false,
    "reset_time": "2025-09-29T00:00:00.000000Z",
    "next_reset": "2025-09-30T00:00:00.000000Z"
}
```

### 3. Get Support Messages

**Endpoint**: `GET /api/support`
**Purpose**: Retrieve user's support ticket history

## Error Responses

### Daily Limit Exceeded (429)

```json
{
    "message": "You have reached your daily limit of 3 support requests. Please try again tomorrow.",
    "meta": {
        "daily_limit": 3,
        "requests_today": 3,
        "reset_time": "in 8 hours",
        "next_reset": "2025-09-29T00:00:00.000000Z"
    }
}
```

### Pending Ticket Exists (429)

```json
{
    "message": "You already have an open support ticket. Please wait for a response before creating a new one."
}
```

### Technical Rate Limit Exceeded (429)

Laravel's built-in response for exceeding 5/minute or 10/hour limits.

## Success Response

### Successful Ticket Creation (201)

```json
{
    "status": "success",
    "message": "Support ticket submitted successfully. A confirmation email has been sent to you.",
    "data": {
        "reference": "SUPPORT-2025-001234",
        "status": "Pending",
        "created_at": "2025-09-28T10:30:00.000000Z"
    },
    "meta": {
        "daily_limit": 3,
        "requests_today": 1,
        "remaining_requests": 2,
        "reset_time": "2025-09-29T00:00:00.000000Z"
    }
}
```

## Frontend Integration

### Before Creating Support Ticket

1. **Check Limits**: Call `GET /api/support/limits` to show user their current status
2. **Display Information**: Show remaining requests and reset time
3. **Conditional UI**: Disable form if limits are exceeded or pending ticket exists

### After API Calls

1. **Handle 429 Responses**: Show appropriate error messages with reset times
2. **Update UI**: Refresh limits after successful ticket creation
3. **User Guidance**: Encourage comprehensive initial reports to avoid hitting limits

## Best Practices for Users

### Encouraged Behavior

-   Provide detailed information in the initial support request
-   Include relevant screenshots or error messages
-   Check FAQ before creating tickets
-   Use clear, descriptive subjects

### Discouraged Behavior

-   Creating multiple tickets for the same issue
-   Submitting incomplete information requiring follow-ups
-   Using support for general questions answered in documentation

## Monitoring and Analytics

### Metrics to Track

-   Daily support request volumes
-   Users hitting rate limits
-   Average resolution time
-   Most common support topics

### Alerts

-   High rate limit hit rates (may indicate UX issues)
-   Unusual spike in support requests
-   System errors in rate limiting logic

## Configuration Options

### Adjustable Parameters

**In Controller** (`SupportController.php`):

```php
$dailyLimit = 3; // Can be increased if needed
```

**In AppServiceProvider** (`AppServiceProvider.php`):

```php
Limit::perMinute(5)  // Adjust minute limit
Limit::perHour(10)   // Adjust hourly limit
```

### Environment-Specific Settings

Consider different limits for different environments:

-   **Development**: Higher limits for testing
-   **Staging**: Production-like limits
-   **Production**: Strict limits for abuse prevention

## Security Considerations

### Rate Limiting by User UUID

-   Prevents shared IP issues (offices, public WiFi)
-   More accurate per-user limiting
-   Falls back to IP for unauthenticated requests

### Abuse Prevention

-   Multiple layers of protection (technical + business logic)
-   Clear error messages to legitimate users
-   Logging for suspicious activity

### Data Privacy

-   Rate limit data doesn't expose sensitive information
-   User-specific limits respect privacy boundaries

---

**Last Updated**: September 28, 2025
**Version**: 1.0
**Maintainer**: SupraCarer Development Team
