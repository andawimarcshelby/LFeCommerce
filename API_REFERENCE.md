# API Reference

Complete API documentation for the High-Volume LMS Reporting Module.

**Base URL**: `http://localhost:8000/api` (development)  
**Authentication**: Laravel Sanctum (Bearer token)

---

## Authentication

### POST /auth/login

Authenticate user and receive access token.

**Request**:
```json
{
  "email": "admin@lms.test",
  "password": "password"
}
```

**Response** (200):
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@lms.test",
    "roles": ["admin"]
  }
}
```

**Errors**: 401 (Invalid credentials)

---

### POST /auth/register

Register new user account.

**Request**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response** (201): Same as login

**Errors**: 422 (Validation failed)

---

### POST /auth/logout

Invalidate current access token.

**Authentication**: Required  
**Response** (200):
```json
{
  "message": "Logged out successfully"
}
```

---

### GET /auth/me

Get current authenticated user.

**Authentication**: Required  
**Response** (200):
```json
{
  "id": 1,
  "name": "Admin User",
  "email": "admin@lms.test",
  "roles": ["admin"],
  "permissions": ["view-reports", "export-reports", "manage-users"]
}
```

---

## Reports

### POST /reports/preview

Generate fast preview of report data (< 1 second).

**Authentication**: Required  
**Request**:
```json
{
  "report_type": "detail",
  "filters": {
    "start_date": "2024-01-01",
    "end_date": "2024-12-31",
    "event_type": "page_view",
    "course_id": 5,
    "student_id": null
  },
  "limit": 100
}
```

**Response** (200):
```json
{
  "data": [
    {
      "id": 1001,
      "occurred_at": "2024-05-15T10:30:00Z",
      "student_name": "Jane Doe",
      "course_name": "CS101",
      "event_type": "page_view",
      "event_data": { "duration_seconds": 120 }
    }
  ],
  "meta": {
    "total": 5000,
    "showing": 100
  }
}
```

**Report Types**:
- `detail`: Individual event rows
- `summary`: Aggregated by course/student
- `top_n`: Top performers
- `per_student`: Student-specific activity

**Errors**: 422 (Invalid filters)

---

### POST /reports/exports

Queue async export job (PDF or Excel).

**Authentication**: Required  
**Rate Limit**: 10 per minute  
**Request**:
```json
{
  "format": "pdf",
  "report_type": "detail",
  "filters": {
    "start_date": "2024-01-01",
    "end_date": "2024-12-31"
  }
}
```

**Response** (201):
```json
{
  "job_id": 42,
  "status": "queued",
  "message": "Export job queued successfully"
}
```

**Formats**: `pdf`, `excel`

**Errors**: 429 (Rate limit exceeded), 422 (Invalid request)

---

### GET /reports/exports

List current user's export jobs.

**Authentication**: Required  
**Response** (200):
```json
{
  "data": [
    {
      "id": 42,
      "report_type": "detail",
      "format": "pdf",
      "status": "completed",
      "progress_percent": 100,
      "file_size": 2457600,
      "download_url": "http://localhost:8000/api/reports/download?signature=...",
      "created_at": "2024-12-10T10:00:00Z"
    }
  ]
}
```

**Statuses**: `queued`, `running`, `completed`, `failed`

---

### GET /reports/exports/{id}

Get specific export job status.

**Authentication**: Required  
**Response** (200):
```json
{
  "id": 42,
  "status": "running",
  "progress_percent": 45,
  "current_section": "Generating PDF pages",
  "processed_rows": 4500,
  "total_rows": 10000,
  "estimated_completion": "2024-12-10T10:15:00Z"
}
```

**Errors**: 404 (Job not found), 403 (Unauthorized)

---

### DELETE /reports/exports/{id}

Cancel/delete export job.

**Authentication**: Required  
**Response** (200):
```json
{
  "message": "Export job cancelled successfully"
}
```

**Errors**: 404 (Job not found), 409 (Job already completed)

---

### GET /reports/download

Download completed export file (signed URL).

**Authentication**: Required (via URL signature)  
**Query Params**: `job_id`, `signature`, `expires`  
**Response**: Binary file download (PDF/Excel)

**Errors**: 403 (Invalid/expired signature), 404 (File not found)

---

## Admin Routes

### GET /reports/exports/admin

View all users' export jobs (admin/viewer only).

**Authentication**: Required (Admin or Viewer role)  
**Query Params**:
- `status`: Filter by status (all, queued, running, completed, failed)
- `user_id`: Filter by user
- `format`: Filter by format (all, pdf, excel)
- `search`: Search by report type
- `page`: Page number

**Response** (200):
```json
{
  "data": [
    {
      "id": 42,
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "report_type": "detail",
      "format": "pdf",
      "status": "completed",
      "created_at": "2024-12-10T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "total": 248
  }
}
```

**Errors**: 403 (Unauthorized - requires admin/viewer role)

---

## Filter Presets

### GET /reports/presets

List user's saved filter presets.

**Authentication**: Required  
**Response** (200):
```json
{
  "data": [
    {
      "id": 1,
      "name": "Fall 2024 Page Views",
      "filters": {
        "start_date": "2024-09-01",
        "end_date": "2024-12-15",
        "event_type": "page_view"
      },
      "created_at": "2024-09-01T08:00:00Z"
    }
  ]
}
```

---

### POST /reports/presets

Save new filter preset.

**Authentication**: Required  
**Request**:
```json
{
  "name": "Fall 2024 Quiz Attempts",
  "filters": {
    "start_date": "2024-09-01",
    "end_date": "2024-12-15",
    "event_type": "quiz_attempt"
  }
}
```

**Response** (201):
```json
{
  "id": 2,
  "name": "Fall 2024 Quiz Attempts",
  "filters": {...},
  "created_at": "2024-12-10T10:00:00Z"
}
```

**Errors**: 422 (Validation failed)

---

### PUT /reports/presets/{id}

Update existing preset.

**Authentication**: Required  
**Request**: Same as POST  
**Response** (200): Updated preset object

**Errors**: 404 (Not found), 403 (Unauthorized)

---

### DELETE /reports/presets/{id}

Delete preset.

**Authentication**: Required  
**Response** (200):
```json
{
  "message": "Preset deleted successfully"
}
```

**Errors**: 404 (Not found), 403 (Unauthorized)

---

## Scheduled Reports

### GET /reports/schedules

List scheduled reports.

**Authentication**: Required  
**Response** (200):
```json
{
  "data": [
    {
      "id": 1,
      "name": "Weekly Activity Report",
      "report_type": "summary",
      "format": "pdf",
      "frequency": "weekly",
      "day_of_week": 1,
      "time": "09:00",
      "filters": {...},
      "is_enabled": true,
      "last_run_at": "2024-12-09T09:00:00Z",
      "next_run_at": "2024-12-16T09:00:00Z"
    }
  ]
}
```

**Frequencies**: `daily`, `weekly`, `monthly`

---

### POST /reports/schedules

Create scheduled report.

**Authentication**: Required  
**Request**:
```json
{
  "name": "Daily Engagement Report",
  "report_type": "summary",
  "format": "excel",
  "frequency": "daily",
  "time": "08:00",
  "filters": {
    "event_type": "page_view"
  }
}
```

**Response** (201): Created schedule object

**Errors**: 422 (Validation failed)

---

### PUT /reports/schedules/{id}

Update scheduled report.

**Authentication**: Required  
**Request**: Same as POST  
**Response** (200): Updated schedule

**Errors**: 404, 403, 422

---

### DELETE /reports/schedules/{id}

Delete scheduled report.

**Authentication**: Required  
**Response** (200):
```json
{
  "message": "Schedule deleted successfully"
}
```

---

### POST /reports/schedules/{id}/toggle

Enable/disable schedule.

**Authentication**: Required  
**Response** (200):
```json
{
  "id": 1,
  "is_enabled": false,
  "message": "Schedule disabled"
}
```

---

### POST /reports/schedules/{id}/trigger

Manually trigger scheduled report.

**Authentication**: Required  
**Response** (201):
```json
{
  "job_id": 123,
  "message": "Report generation triggered"
}
```

---

## Audit Logs

### GET /audit/logs

View audit logs (admin/viewer only).

**Authentication**: Required (Admin or Viewer role)  
**Query Params**:
- `start_date`, `end_date`: Date range
- `user_id`: Filter by user
- `action`: Filter by API route
- `status`: HTTP status code
- `search`: Search IP/user agent
- `page`: Page number

**Response** (200):
```json
{
  "data": [
    {
      "id": 1001,
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "action": "reports.preview",
      "ip_address": "192.168.1.100",
      "response_status": 200,
      "duration_ms": 245,
      "created_at": "202-12-10T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 1250
  }
}
```

**Errors**: 403 (Unauthorized)

---

### GET /audit/stats

Get audit statistics (admin/viewer only).

**Authentication**: Required (Admin or Viewer role)  
**Response** (200):
```json
{
  "total_logs": 125000,
  "today_logs": 450,
  "unique_users": 23,
  "avg_response_time": 287.5,
  "error_count": 34
}
```

---

## Error Codes

| Code | Meaning | Common Causes |
|------|---------|---------------|
| 400 | Bad Request | Malformed JSON |
| 401 | Unauthorized | Invalid/missing token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 422 | Validation Error | Invalid input data |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Internal error |

**Error Response Format**:
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| POST /reports/exports | 10 requests | 1 minute |
| All other endpoints | 60 requests | 1 minute |

**Rate Limit Headers**:
- `X-RateLimit-Limit`: Maximum requests
- `X-RateLimit-Remaining`: Requests remaining
- `X-RateLimit-Reset`: Unix timestamp when limit resets

---

## Pagination

Paginated responses include:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 50,
    "total": 500
  }
}
```

**Query Params**:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 50, max: 100)

---

## Authentication Flow

1. **Login** → Receive token
2. **Store token** in localStorage/cookie
3. **Add header** to all requests:
   ```
   Authorization: Bearer {token}
   ```
4. **Logout** → Token invalidated

**Token Expiry**: 24 hours (configurable)

---

**Last Updated**: December 10, 2025  
**API Version**: 1.0
