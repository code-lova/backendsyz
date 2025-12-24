# Service Flyers API Documentation

## Overview

The Service Flyer system allows administrators to manage promotional images/banners that are displayed in client and health worker dashboards. The system supports CRUD operations, Cloudinary integration for image management, and separate API endpoints for different user roles.

## Features

-   **Admin CRUD Operations**: Complete Create, Read, Update, Delete functionality
-   **Cloudinary Integration**: Images are stored in Cloudinary with automatic cleanup
-   **Target Audience Control**: Flyers can be targeted to clients, health workers, or both
-   **Active/Inactive Status**: Control visibility of flyers
-   **Sort Ordering**: Custom ordering for flyer display
-   **Image Replacement**: Automatic deletion of old images when updated

## Database Schema

### `service_flyers` Table

```sql
- id (bigint, auto-increment)
- uuid (varchar, unique)
- title (varchar, 255)
- description (text, nullable)
- image_url (varchar) - Cloudinary URL
- image_public_id (varchar) - Cloudinary public ID for deletion
- target_audience (enum: 'client', 'healthworker', 'both')
- is_active (boolean, default: true)
- sort_order (integer, default: 0)
- created_by (uuid) - Foreign key to users table
- created_at (timestamp)
- updated_at (timestamp)
```

## API Endpoints

### Admin Endpoints (Requires admin authentication)

#### 1. List Service Flyers

```
GET /api/admin/service-flyers
```

**Query Parameters:**

-   `per_page` (optional): Number of items per page (default: 15)
-   `search` (optional): Search in title and description
-   `target_audience` (optional): Filter by audience (client, healthworker, both)
-   `status` (optional): Filter by status (active, inactive, all)

**Response:**

```json
{
    "message": "Service flyers retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "title": "Quality Healthcare Services",
                "description": "Professional healthcare services...",
                "image_url": "https://res.cloudinary.com/...",
                "image_public_id": "sample_flyer_1",
                "target_audience": "both",
                "is_active": true,
                "sort_order": 1,
                "created_at": "2024-12-21T12:00:00Z",
                "updated_at": "2024-12-21T12:00:00Z",
                "creator": {
                    "uuid": "admin-uuid",
                    "name": "Admin User",
                    "email": "admin@example.com"
                }
            }
        ],
        "total": 5,
        "per_page": 15,
        "last_page": 1
    }
}
```

#### 2. Get Specific Service Flyer

```
GET /api/admin/service-flyers/{uuid}
```

**Response:**

```json
{
    "message": "Service flyer retrieved successfully",
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "title": "Quality Healthcare Services",
        "description": "Professional healthcare services...",
        "image_url": "https://res.cloudinary.com/...",
        "image_public_id": "sample_flyer_1",
        "target_audience": "both",
        "is_active": true,
        "sort_order": 1,
        "created_at": "2024-12-21T12:00:00Z",
        "updated_at": "2024-12-21T12:00:00Z",
        "creator": {
            "uuid": "admin-uuid",
            "name": "Admin User",
            "email": "admin@example.com"
        }
    }
}
```

#### 3. Create Service Flyer

```
POST /api/admin/service-flyers
```

**Request Body:**

```json
{
    "title": "New Service Flyer",
    "description": "Description of the service flyer",
    "image_url": "https://res.cloudinary.com/your-cloud/image/upload/v1234567890/flyer.jpg",
    "image_public_id": "flyer_public_id",
    "target_audience": "both",
    "is_active": true,
    "sort_order": 1
}
```

**Validation Rules:**

-   `title`: required, string, max:255
-   `description`: nullable, string, max:1000
-   `image_url`: required, valid URL
-   `image_public_id`: required, string
-   `target_audience`: required, one of: client, healthworker, both
-   `is_active`: nullable, boolean
-   `sort_order`: nullable, integer, min:0

#### 4. Update Service Flyer

```
PUT /api/admin/service-flyers/{uuid}
```

**Request Body:** (All fields are optional)

```json
{
    "title": "Updated Title",
    "description": "Updated description",
    "image_url": "https://res.cloudinary.com/new-image.jpg",
    "image_public_id": "new_public_id",
    "target_audience": "client",
    "is_active": false,
    "sort_order": 2
}
```

**Note:** When updating image_url and image_public_id, the old image will be automatically deleted from Cloudinary.

#### 5. Delete Service Flyer

```
DELETE /api/admin/service-flyers/{uuid}
```

**Response:**

```json
{
    "message": "Service flyer deleted successfully"
}
```

**Note:** This will also delete the associated image from Cloudinary.

#### 6. Toggle Status

```
PATCH /api/admin/service-flyers/{uuid}/toggle-status
```

**Response:**

```json
{
    "message": "Service flyer status updated successfully",
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "is_active": false
        // ... other fields
    }
}
```

#### 7. Update Sort Order

```
PATCH /api/admin/service-flyers/sort-order
```

**Request Body:**

```json
{
    "flyers": [
        {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "sort_order": 1
        },
        {
            "uuid": "550e8400-e29b-41d4-a716-446655440001",
            "sort_order": 2
        }
    ]
}
```

### Client Endpoints (Requires client authentication)

#### 1. Get Active Service Flyers for Clients

```
GET /api/service-flyers
```

**Query Parameters:**

-   `limit` (optional): Number of flyers to return (default: 10, max: 50)

**Response:**

```json
{
    "message": "Service flyers retrieved successfully",
    "data": [
        {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "title": "Quality Healthcare Services",
            "description": "Professional healthcare services...",
            "image_url": "https://res.cloudinary.com/...",
            "sort_order": 1,
            "created_at": "2024-12-21T12:00:00Z"
        }
    ],
    "count": 3
}
```

#### 2. Get Specific Service Flyer

```
GET /api/service-flyers/{uuid}
```

### Health Worker Endpoints (Requires health worker authentication)

#### 1. Get Active Service Flyers for Health Workers

```
GET /api/service-flyers
```

**Query Parameters:**

-   `limit` (optional): Number of flyers to return (default: 10, max: 50)

**Response:** Same format as client endpoint but filtered for health workers.

#### 2. Get Specific Service Flyer

```
GET /api/service-flyers/{uuid}
```

## Error Responses

### Validation Error (422)

```json
{
    "message": "Validation failed",
    "errors": {
        "title": ["The title field is required."],
        "image_url": ["Please provide a valid image URL."]
    }
}
```

### Not Found (404)

```json
{
    "message": "Service flyer not found"
}
```

### Server Error (500)

```json
{
    "message": "Failed to create service flyer",
    "error": "Detailed error message (in development mode)"
}
```

## Image Management with Cloudinary

### Upload Process

1. Frontend uploads image to Cloudinary
2. Cloudinary returns `secure_url` and `public_id`
3. Frontend sends these values to the API
4. API stores both values in the database

### Update Process

1. When updating a flyer with new image data:
2. API automatically deletes the old image from Cloudinary
3. New image data is saved to the database

### Delete Process

1. When deleting a flyer:
2. API deletes the associated image from Cloudinary
3. Database record is removed

## Usage Examples

### Creating a Service Flyer

```javascript
// Frontend code example
const createFlyer = async (flyerData) => {
    const response = await fetch("/api/admin/service-flyers", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer " + token,
        },
        body: JSON.stringify(flyerData),
    });

    return await response.json();
};
```

### Fetching Client Flyers for Dashboard

```javascript
// Client dashboard flyers
const getClientFlyers = async () => {
    const response = await fetch("/api/service-flyers?limit=5", {
        headers: {
            Authorization: "Bearer " + clientToken,
        },
    });

    return await response.json();
};
```

## Security Notes

1. **Authentication Required**: All endpoints require proper authentication
2. **Role-Based Access**: Admin endpoints are restricted to admin users only
3. **Image Validation**: Only valid URLs are accepted for image_url field
4. **Rate Limiting**: Consider implementing rate limiting for image operations
5. **File Size**: Cloudinary handles file size limits based on your account settings

## Database Seeding

To seed sample data:

```bash
php artisan db:seed --class=ServiceFlyerSeeder
```

## Testing

### Manual Testing with Postman

1. Set up authentication tokens for admin, client, and health worker
2. Test all CRUD operations with the admin token
3. Test read-only operations with client and health worker tokens
4. Verify image deletion in Cloudinary when updating/deleting flyers

### Key Test Cases

-   Create flyer with valid data
-   Create flyer with invalid data (validation errors)
-   Update flyer with new image (old image should be deleted)
-   Delete flyer (image should be deleted from Cloudinary)
-   Toggle flyer status
-   Get flyers with different filters
-   Get client/health worker specific flyers

## Performance Considerations

1. **Database Indexes**: Indexes are created on `target_audience`, `is_active`, and `sort_order`
2. **Pagination**: Admin endpoint supports pagination for large datasets
3. **Image Optimization**: Consider using Cloudinary transformations for different screen sizes
4. **Caching**: Consider implementing Redis caching for frequently accessed flyers
5. **Lazy Loading**: Frontend should implement lazy loading for images
