# Health Worker Dashboard API Documentation

## Overview

The Health Worker Dashboard provides four key endpoints for retrieving dashboard statistics with proper security controls and minimal data exposure.

## Endpoints

### 1. GET `/api/healthworker/count-appointments`

**Purpose**: Get appointment counts by status and upcoming appointment statistics.

**Security**:

-   Requires authentication (`auth:sanctum`)
-   Requires health worker role
-   Only returns data for authenticated health worker

**Response Structure**:

```json
{
    "status": "Success",
    "message": "Appointment counts retrieved successfully.",
    "data": {
        "total_appointments": 25,
        "pending_appointments": 3,
        "confirmed_appointments": 5,
        "ongoing_appointments": 1,
        "completed_appointments": 14,
        "cancelled_appointments": 2,
        "upcoming_7_days": 4,
        "today_appointments": 1,
        "this_month_total": 8,
        "generated_at": "2025-09-14T..."
    }
}
```

### 2. GET `/api/healthworker/ratings-stats`

**Purpose**: Get rating statistics including current month average, overall average, and weekly trends.

**Security**:

-   Requires authentication (`auth:sanctum`)
-   Requires health worker role
-   Only returns ratings for authenticated health worker

**Response Structure**:

```json
{
    "status": "Success",
    "message": "Rating statistics retrieved successfully.",
    "data": {
        "overall": {
            "total_reviews": 45,
            "average_rating": 4.6,
            "highest_rating": 5,
            "lowest_rating": 3
        },
        "current_month": {
            "month": "September 2025",
            "total_reviews": 8,
            "average_rating": 4.7
        },
        "weekly_trends": [
            {
                "week": 1,
                "week_period": "Sep 1 - Sep 7",
                "reviews_count": 2,
                "average_rating": 4.5
            },
            {
                "week": 2,
                "week_period": "Sep 8 - Sep 14",
                "reviews_count": 3,
                "average_rating": 4.8
            }
        ],
        "rating_distribution": {
            "5": 20,
            "4": 18,
            "3": 5,
            "2": 1,
            "1": 1
        },
        "generated_at": "2025-09-14T..."
    }
}
```

### 3. GET `/api/healthworker/monthly-appointment-stats`

**Purpose**: Get monthly appointment statistics for line chart visualization.

**Parameters**:

-   `year` (optional): Year to get statistics for (default: current year)
-   `status` (optional): Filter by status - `all`, `confirmed`, `completed`, `cancelled` (default: `all`)

**Security**:

-   Requires authentication (`auth:sanctum`)
-   Requires health worker role
-   Only returns appointments for authenticated health worker

**Response Structure**:

```json
{
    "status": "Success",
    "message": "Monthly appointment statistics retrieved successfully.",
    "data": {
        "year": 2025,
        "status_filter": "all",
        "monthly_data": [
            {
                "month": 1,
                "month_name": "January",
                "appointment_count": 8,
                "completed_count": 6,
                "confirmed_count": 1,
                "ongoing_count": 1
            },
            {
                "month": 2,
                "month_name": "February",
                "appointment_count": 12,
                "completed_count": 10,
                "confirmed_count": 2,
                "ongoing_count": 0
            }
            // ... continues for all 12 months
        ],
        "summary": {
            "total_appointments": 95,
            "total_completed": 82,
            "total_confirmed": 8,
            "total_cancelled": 5,
            "completion_rate": 86.3
        },
        "peak_month": {
            "month": "August",
            "count": 15
        },
        "generated_at": "2025-09-14T..."
    }
}
```

## Security Features

1. **Authentication Required**: All endpoints require valid Sanctum token
2. **Role-Based Access**: Only health workers can access these endpoints
3. **Data Isolation**: Each health worker only sees their own data
4. **Minimal Data Exposure**: Only essential statistics are returned
5. **Input Validation**: Request parameters are validated
6. **Error Handling**: Graceful error handling with appropriate status codes
7. **Logging**: Comprehensive error logging for debugging

## Error Responses

```json
{
    "status": "Error",
    "message": "Unauthorized. Only health workers can access this endpoint."
}
```

```json
{
    "status": "Error",
    "message": "Failed to retrieve appointment statistics. Please try again."
}
```

### 4. GET `/api/healthworker/recent-appointments`

**Purpose**: Get the 3 most recent appointments with minimal client details for dashboard display.

**Security**:

-   Requires authentication (`auth:sanctum`)
-   Requires health worker role
-   Only returns appointments assigned to authenticated health worker

**Response Structure**:

```json
{
    "status": "Success",
    "message": "Recent appointments retrieved successfully.",
    "data": {
        "recent_appointments": [
            {
                "start_date": "2025-09-16",
                "user": {
                    "uuid": "client-uuid-456",
                    "image": "profile-image-url",
                    "name": "client-name"
                }
            },
            {
                "start_date": "2025-09-15",
                "user": {
                    "uuid": "client-uuid-789",
                    "image": "another-profile-image-url",
                    "name": "client-name"
                }
            },
            {
                "start_date": "2025-09-14",
                "user": {
                    "uuid": "client-uuid-101",
                    "image": "third-profile-image-url",
                    "name": "client-name"
                }
            }
        ],
        "showing_count": 3,
        "generated_at": "2025-09-14T..."
    }
}
```

**Error Response**:

```json
{
    "status": "Error",
    "message": "Failed to retrieve recent appointments. Please try again."
}
```

## Data Usage

-   **Appointment Counts**: Use for dashboard widgets showing current workload
-   **Rating Trends**: Use for line charts showing rating improvements over time
-   **Monthly Stats**: Use for line charts showing appointment volume trends
-   **Weekly Trends**: Use for detailed monthly performance analysis
-   **Recent Appointments**: Use for dashboard recent activity widget with minimal client details

## Performance Considerations

-   Queries are optimized with selective fields and proper indexing
-   Results include timestamps for cache management
-   Aggregated data reduces response size
-   Database queries use efficient SQL aggregation functions
-   Recent appointments limited to 3 records with minimal data fields for optimal performance
