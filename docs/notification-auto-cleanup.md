# Notification Auto-Cleanup System

## Overview

The notification system includes automatic cleanup functionality to prevent database bloat and maintain optimal performance. The system provides multiple cleanup strategies and can be configured to run automatically via Laravel's scheduler.

## Cleanup Methods

### 1. Standard Cleanup

Deletes all notifications older than specified days (default: 30 days).

```bash
php artisan notifications:cleanup --days=30
```

### 2. Read Notifications Cleanup

Deletes only read notifications older than specified days (default: 7 days).

```bash
php artisan notifications:cleanup --read-days=7
```

### 3. Type-Specific Cleanup

Deletes notifications of a specific type older than specified days.

```bash
php artisan notifications:cleanup --type=booking_assigned --days=15
```

### 4. Auto Cleanup (Recommended)

Runs preset cleanup rules:

-   Deletes read notifications older than 7 days
-   Deletes all notifications older than 30 days

```bash
php artisan notifications:cleanup --auto
```

## Available Notification Types

-   `booking_assigned` - Client booking assignments
-   `support_reply` - Support ticket replies
-   `new_booking_assigned` - Health worker booking assignments
-   `new_booking_request` - Admin new booking notifications
-   `new_client_support` - Admin client support notifications
-   `client_support_reply` - Admin client reply notifications
-   `booking_confirmed` - Admin booking confirmation notifications
-   `booking_started` - Admin booking started notifications
-   `new_healthworker_support` - Admin health worker support notifications
-   `new_user_registration` - Admin user registration notifications

## Automatic Scheduling

The system is configured to run auto cleanup daily at 2:00 AM via Laravel's scheduler.

### Current Schedule

```php
// bootstrap/app.php
$schedule->command('notifications:cleanup --auto')->dailyAt('02:00');
```

### Cron Job Setup

Add this to your server's crontab to enable automatic scheduling:

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

For this project:

```bash
* * * * * cd /Users/ebizo/Applications/supracarer_project/backendsyz && php artisan schedule:run >> /dev/null 2>&1
```

## Manual Cleanup Options

### Check Current Schedule

```bash
php artisan schedule:list
```

### Run Scheduler Manually

```bash
php artisan schedule:run
```

### Custom Cleanup Examples

1. **Clean up only booking notifications older than 7 days:**

    ```bash
    php artisan notifications:cleanup --type=booking_assigned --days=7
    ```

2. **Clean up read notifications older than 3 days:**

    ```bash
    php artisan notifications:cleanup --read-days=3
    ```

3. **Aggressive cleanup - all notifications older than 14 days:**
    ```bash
    php artisan notifications:cleanup --days=14
    ```

## Configuration

### Default Settings

-   **All notifications**: 30 days retention
-   **Read notifications**: 7 days retention
-   **Auto cleanup**: Runs daily at 2:00 AM

### Customizing Retention Periods

You can modify the default retention periods in the `NotificationService`:

```php
// app/Services/NotificationService.php
public function autoCleanup(): void
{
    // Delete read notifications older than 7 days
    $this->deleteOldReadNotifications(7);

    // Delete all notifications older than 30 days
    $this->deleteOldNotifications(30);
}
```

### Changing Schedule Frequency

Update the schedule in `bootstrap/app.php`:

```php
// Run twice daily
$schedule->command('notifications:cleanup --auto')->twiceDaily(2, 14);

// Run weekly on Sundays
$schedule->command('notifications:cleanup --auto')->weekly()->sundays()->at('02:00');

// Run monthly
$schedule->command('notifications:cleanup --auto')->monthly();
```

## Monitoring

All cleanup operations are logged for monitoring:

```php
// Check logs for cleanup activity
tail -f storage/logs/laravel.log | grep -i "notification cleanup"
```

Log entries include:

-   Number of notifications deleted
-   Cleanup type and parameters
-   Timestamp and success/failure status

## Best Practices

1. **Use auto cleanup** for most scenarios
2. **Monitor logs** to ensure cleanup is working
3. **Adjust retention periods** based on business needs
4. **Test manually** before deploying to production
5. **Backup database** before running aggressive cleanups

## Troubleshooting

### Command Not Found

```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

### Schedule Not Running

1. Verify cron job is set up correctly
2. Check Laravel logs for errors
3. Test manual schedule run: `php artisan schedule:run`

### Database Locks

If you encounter database locks during cleanup:

```bash
# Run cleanup during low-traffic hours
# Consider chunked deletion for large datasets
```

## Performance Considerations

-   Cleanup runs during low-traffic hours (2:00 AM)
-   Large deletions are logged for monitoring
-   Database indexes on `created_at` and `read_at` optimize cleanup queries
-   Auto cleanup uses efficient batch operations
