# Notification System Documentation

## Overview

The notification system provides real-time notifications for important events across the platform. It supports all user roles (admin, client, healthworker) and includes a bell icon notification system for the frontend.

## Features

-   **Real-time Notifications**: Instant notifications for important events
-   **Role-based Notifications**: Different notification types for each user role
-   **Bell Icon Support**: Unread count and latest notifications for UI
-   **Mark as Read**: Individual and bulk marking as read
-   **Pagination**: Efficient pagination for large notification lists
-   **Data Storage**: Additional JSON data storage for each notification

## Database Schema

### Notifications Table

```sql
- id (UUID, Primary Key)
- user_uuid (UUID, Foreign Key to users.uuid)
- type (String) - notification type identifier
- title (String) - notification title
- message (Text) - notification message
- data (JSON, Nullable) - additional metadata
- read_at (Timestamp, Nullable) - when notification was read
- created_at (Timestamp)
- updated_at (Timestamp)
```

### Indexes

-   `user_uuid` - for user-specific queries
-   `type` - for filtering by notification type
-   `read_at` - for unread filtering
-   `user_uuid, read_at` - composite for efficient unread counts
-   `created_at` - for chronological ordering

## API Endpoints

All notification endpoints require authentication (`auth:sanctum`) and are accessible to all authenticated users.

### 1. GET `/api/notifications`

Get paginated notifications for authenticated user.

**Parameters:**

-   `per_page` (optional): Number of notifications per page (5-50, default: 15)

**Response:**

```json
{
    "status": "Success",
    "message": "Notifications retrieved successfully.",
    "data": {
        "notifications": [...],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 25,
            "last_page": 2,
            "from": 1,
            "to": 15
        }
    }
}
```

### 2. GET `/api/notifications/latest`

Get latest notifications (unread first, then recent read ones) for bell icon.

**Parameters:**

-   `limit` (optional): Number of notifications to return (5-20, default: 10)

**Response:**

```json
{
    "status": "Success",
    "message": "Latest notifications retrieved successfully.",
    "data": {
        "notifications": [...],
        "unread_count": 3,
        "total_shown": 10
    }
}
```

### 3. GET `/api/notifications/unread-count`

Get unread notifications count for badge display.

**Response:**

```json
{
    "status": "Success",
    "message": "Unread count retrieved successfully.",
    "data": {
        "unread_count": 5
    }
}
```

### 4. PUT `/api/notifications/mark-as-read`

Mark a specific notification as read.

**Parameters:**

-   `notification_id` (required): UUID of notification to mark as read

**Response:**

```json
{
    "status": "Success",
    "message": "Notification marked as read successfully."
}
```

### 5. PUT `/api/notifications/mark-all-as-read`

Mark all notifications as read for authenticated user.

**Response:**

```json
{
    "status": "Success",
    "message": "All notifications marked as read successfully."
}
```

## Notification Types

### Client Notifications

#### 1. Booking Assignment (`booking_assigned`)

**Trigger**: When admin assigns a booking request to a health worker

-   **Title**: "Booking Assigned"
-   **Message**: "Your booking request #{booking_reference} has been assigned to {health_worker_name}."
-   **Data**: `booking_reference`, `health_worker_name`

#### 2. Support Reply (`support_reply`)

**Trigger**: When admin replies to client support ticket

-   **Title**: "Support Reply Received"
-   **Message**: "You have received a reply to your support ticket #{ticket_reference}."
-   **Data**: `ticket_reference`

### Health Worker Notifications

#### 1. New Booking Assignment (`new_booking_assigned`)

**Trigger**: When admin assigns a new booking request to health worker

-   **Title**: "New Booking Assignment"
-   **Message**: "You have been assigned a new booking #{booking_reference} from {client_name}."
-   **Data**: `booking_reference`, `client_name`

#### 2. Support Reply (`support_reply`)

**Trigger**: When admin replies to health worker support message

-   **Title**: "Support Reply Received"
-   **Message**: "You have received a reply to your support message #{support_reference}."
-   **Data**: `support_reference`

### Admin Notifications

#### 1. New Booking Request (`new_booking_request`)

**Trigger**: When client creates a new booking request

-   **Title**: "New Booking Request"
-   **Message**: "New booking request #{booking_reference} received from {client_name}."
-   **Data**: `booking_reference`, `client_name`

#### 2. New Client Support (`new_client_support`)

**Trigger**: When client creates a support ticket

-   **Title**: "New Client Support Message"
-   **Message**: "New support ticket #{ticket_reference} received from {client_name}."
-   **Data**: `ticket_reference`, `client_name`

#### 3. New Health Worker Support (`new_healthworker_support`)

**Trigger**: When health worker sends a support message

-   **Title**: "New Health Worker Support Message"
-   **Message**: "New support message #{support_reference} received from {health_worker_name}."
-   **Data**: `support_reference`, `health_worker_name`

#### 4. New User Registration (`new_user_registration`)

**Trigger**: When a new user registers on the platform

-   **Title**: "New User Registration"
-   **Message**: "New {user_role} registered: {user_name} ({user_email})."
-   **Data**: `user_name`, `user_role`, `user_email`

## Integration Points

### Controllers with Notification Triggers

#### 1. AuthController

-   **Method**: `register()`
-   **Trigger**: `notifyAdminNewUserRegistration()` after successful user registration

#### 2. Admin/BookingRequests

-   **Method**: `processingBookingRequest()`
-   **Triggers**:
    -   `notifyClientBookingAssigned()` when health worker assigned
    -   `notifyHealthWorkerNewBooking()` when health worker assigned

#### 3. Client/BookingAppointmentController

-   **Method**: `create()`
-   **Trigger**: `notifyAdminNewBookingRequest()` after booking creation

#### 4. Client/SupportTicketController

-   **Method**: `createTicketMessage()`
-   **Trigger**: `notifyAdminNewClientSupport()` after ticket creation

#### 5. Healthworker/SupportController

-   **Method**: `createSupportMessage()`
-   **Trigger**: `notifyAdminNewHealthWorkerSupport()` after support message creation

#### 6. Admin/TicketSupport

-   **Method**: `replyToTicket()`
-   **Trigger**: `notifyClientSupportReply()` when admin replies to client ticket

#### 7. Admin/AdminController

-   **Method**: `replySupportMessage()`
-   **Trigger**: `notifyHealthWorkerSupportReply()` when admin replies to health worker support

## Usage Examples

### Frontend Bell Icon Implementation

```javascript
// Get unread count for badge
const unreadResponse = await fetch("/api/notifications/unread-count");
const {
    data: { unread_count },
} = await unreadResponse.json();

// Get latest notifications for dropdown
const latestResponse = await fetch("/api/notifications/latest?limit=10");
const {
    data: { notifications, unread_count },
} = await latestResponse.json();

// Mark notification as read
await fetch("/api/notifications/mark-as-read", {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ notification_id: "notification-uuid" }),
});

// Mark all as read
await fetch("/api/notifications/mark-all-as-read", { method: "PUT" });
```

### Service Usage in Controllers

```php
// Inject NotificationService in controller constructor
public function __construct(NotificationService $notificationService)
{
    $this->notificationService = $notificationService;
}

// Create notification
$this->notificationService->notifyClientBookingAssigned(
    $clientUuid,
    $bookingReference,
    $healthWorkerName
);
```

## Security Features

-   **Authentication Required**: All endpoints require valid authentication
-   **User Isolation**: Users can only access their own notifications
-   **Data Validation**: All inputs are validated and sanitized
-   **Role-based Access**: Notifications are filtered by user role
-   **SQL Injection Prevention**: Uses Eloquent ORM and parameterized queries

## Performance Considerations

-   **Database Indexes**: Optimized indexes for common query patterns
-   **Pagination**: Efficient pagination to handle large notification volumes
-   **Eager Loading**: Relationships loaded efficiently to prevent N+1 queries
-   **Selective Fields**: Only necessary fields loaded in API responses
-   **Cleanup**: Old notifications can be cleaned up using `deleteOldNotifications()` method

## Maintenance

### Cleanup Old Notifications

```php
// Delete notifications older than 30 days
$deletedCount = $notificationService->deleteOldNotifications(30);
```

### Monitor Notification Volume

```sql
-- Check notification counts by type
SELECT type, COUNT(*) as count
FROM notifications
GROUP BY type
ORDER BY count DESC;

-- Check unread notifications by user
SELECT user_uuid, COUNT(*) as unread_count
FROM notifications
WHERE read_at IS NULL
GROUP BY user_uuid
ORDER BY unread_count DESC;
```

## Error Handling

-   **Graceful Degradation**: Notification failures don't break main functionality
-   **Logging**: All notification operations are logged for debugging
-   **Validation**: Input validation prevents invalid notifications
-   **Foreign Key Constraints**: Ensures data integrity with user relationships
-   **Exception Handling**: Proper exception handling in all service methods

This notification system provides a comprehensive solution for keeping users informed about important platform events while maintaining security, performance, and scalability.
