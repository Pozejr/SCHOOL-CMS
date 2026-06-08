# School CMS — API Documentation

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication

All protected endpoints require a valid session cookie. Authentication is handled via PHP sessions with CSRF token protection.

### Session Authentication
1. Call `POST /auth/login` to create a session
2. Session cookie is set automatically by the browser
3. Include `X-CSRF-Token` header for all mutating requests (POST, PUT, DELETE)

### Authentication Header
```
X-CSRF-Token: <token_from_login_or_csrf_endpoint>
```

## Response Format

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

### Paginated Response
```json
{
  "success": true,
  "data": [ ... ],
  "message": "Records retrieved",
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 42,
    "total_pages": 3
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message"
  }
}
```

### HTTP Status Codes
| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized (not authenticated) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests (rate limited) |
| 500 | Internal Server Error |

---

## 1. Authentication Endpoints

### POST /auth/login
Authenticate a user and create a session.

**Access:** Public

**Request:**
```json
{
  "username": "superadmin",
  "password": "Admin@123"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "superadmin",
    "email": "admin@schoolcms.com",
    "role": "super_admin",
    "first_name": "Super",
    "last_name": "Admin",
    "avatar": null,
    "csrf_token": "abc123..."
  },
  "message": "Login successful"
}
```

**Error (401):**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_FAILED",
    "message": "Invalid username or password"
  }
}
```

### POST /auth/logout
End the current session.

**Access:** Authenticated (super_admin, admin, editor)

**Headers:** `X-CSRF-Token: <token>`

**Response (200):**
```json
{
  "success": true,
  "data": null,
  "message": "Logged out successfully"
}
```

### GET /auth/me
Get the currently authenticated user.

**Access:** Authenticated (super_admin, admin, editor)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "superadmin",
    "email": "admin@schoolcms.com",
    "role": "super_admin",
    "first_name": "Super",
    "last_name": "Admin",
    "status": "active",
    "avatar": null,
    "last_login": "2025-01-15T10:30:00+00:00",
    "csrf_token": "abc123..."
  }
}
```

### GET /auth/csrf-token
Get a new CSRF token (initializes session).

**Access:** Public

**Response (200):**
```json
{
  "success": true,
  "data": {
    "csrf_token": "abc123..."
  }
}
```

---

## 2. Dashboard Endpoints

### GET /dashboard/stats
Get dashboard statistics.

**Access:** Authenticated (super_admin, admin)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_pages": 5,
    "published_pages": 4,
    "total_news": 3,
    "published_news": 2,
    "total_events": 2,
    "upcoming_events": 1,
    "total_users": 3,
    "total_files": 10
  }
}
```

### GET /dashboard/activity
Get recent activity log entries.

**Access:** Authenticated (super_admin, admin)

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | int | 10 | Number of entries to return |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "action": "login",
      "entity_type": "user",
      "entity_id": 1,
      "description": "User logged in",
      "ip_address": "127.0.0.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2025-01-15T10:30:00+00:00"
    }
  ]
}
```

---

## 3. Pages Endpoints

### GET /pages
List published pages (public).

**Access:** Public

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 15 | Items per page |

**Response (200):** Paginated list of published pages.

### GET /pages/admin/all
List all pages including drafts (admin).

**Access:** Authenticated (super_admin, admin)

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 15 | Items per page |

### GET /pages/slug/{slug}
Get a page by slug with full section data and SEO metadata.

**Access:** Public

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "About Us",
    "slug": "about-us",
    "content": "...",
    "excerpt": "...",
    "status": "published",
    "meta_title": "About Us | School CMS",
    "meta_description": "Learn about our school...",
    "featured_image": "uploads/images/school.jpg",
    "sections": [
      {
        "id": 1,
        "section_type": "text",
        "title": "Our History",
        "content": "...",
        "content_html": "<p>...</p>",
        "image_url": null,
        "layout": "full",
        "display_order": 0
      }
    ],
    "seo": {
      "title": "About Us | School CMS",
      "description": "Learn about our school...",
      "canonical": "http://localhost:8000/page/about-us",
      "json_ld": { ... }
    }
  }
}
```

### GET /pages/{id}
Get a page by ID with raw section data (for editing).

**Access:** Public

### POST /pages
Create a new page.

**Access:** Authenticated (super_admin, admin)

**Headers:** `X-CSRF-Token: <token>`

**Request:**
```json
{
  "title": "New Page",
  "slug": "new-page",
  "content": "<p>Page content</p>",
  "status": "draft",
  "meta_title": "New Page | School CMS",
  "meta_description": "Description...",
  "featured_image": "uploads/images/photo.jpg",
  "template": "default",
  "sort_order": 1
}
```

**Response (201):**
```json
{
  "success": true,
  "data": { "id": 6, "title": "New Page", ... },
  "message": "Page created successfully"
}
```

### PUT /pages/{id}
Update an existing page.

**Access:** Authenticated (super_admin, admin)

**Headers:** `X-CSRF-Token: <token>`

**Request:** Same fields as POST.

### DELETE /pages/{id}
Delete a page (soft delete).

**Access:** Authenticated (super_admin, admin)

**Headers:** `X-CSRF-Token: <token>`

**Response (200):**
```json
{
  "success": true,
  "data": null,
  "message": "Page deleted successfully"
}
```

---

## 4. Page Sections Endpoints

### GET /pages/{page_id}/sections
List all sections for a page.

**Access:** Authenticated (super_admin, admin)

### POST /pages/{page_id}/sections
Create a new section.

**Access:** Authenticated (super_admin, admin)

**Request:**
```json
{
  "section_type": "text",
  "title": "Section Title",
  "content": "Section content",
  "image_url": "uploads/images/photo.jpg",
  "image_caption": "Photo caption",
  "layout": "full",
  "display_order": 0
}
```

### PUT /pages/sections/{id}
Update a section.

**Access:** Authenticated (super_admin, admin)

### DELETE /pages/sections/{id}
Delete a section.

**Access:** Authenticated (super_admin, admin)

### PUT /pages/{page_id}/sections/reorder
Reorder sections.

**Access:** Authenticated (super_admin, admin)

**Request:**
```json
{
  "section_ids": [3, 1, 2]
}
```

---

## 5. Templates Endpoints

### GET /templates
List all page templates.

**Access:** Authenticated (super_admin, admin)

### GET /templates/{slug}
Get a template by slug.

**Access:** Authenticated (super_admin, admin)

### POST /pages/{page_id}/apply-template
Apply a template to a page (creates sections from template).

**Access:** Authenticated (super_admin, admin)

**Request:**
```json
{
  "template_slug": "about"
}
```

---

## 6. News Endpoints

### GET /news
List published news articles (public).

**Access:** Public

**Query Parameters:** `page`, `per_page`

### GET /news/admin/all
List all news articles including drafts.

**Access:** Authenticated (super_admin, admin)

### GET /news/slug/{slug}
Get a news article by slug (public).

**Access:** Public

### GET /news/{id}
Get a news article by ID.

**Access:** Public

### POST /news
Create a news article.

**Access:** Authenticated (super_admin, admin)

**Request:**
```json
{
  "title": "Article Title",
  "slug": "article-title",
  "content": "<p>Full article content</p>",
  "excerpt": "Short summary",
  "featured_image": "uploads/images/photo.jpg",
  "status": "published"
}
```

### PUT /news/{id}
Update a news article.

**Access:** Authenticated (super_admin, admin)

### DELETE /news/{id}
Delete a news article.

**Access:** Authenticated (super_admin, admin)

---

## 7. Events Endpoints

### GET /events
List published events (public).

**Access:** Public

**Query Parameters:** `page`, `per_page`

### GET /events/admin/all
List all events including drafts.

**Access:** Authenticated (super_admin, admin)

### GET /events/slug/{slug}
Get an event by slug (public).

**Access:** Public

### GET /events/{id}
Get an event by ID.

**Access:** Public

### POST /events
Create an event.

**Access:** Authenticated (super_admin, admin)

**Request:**
```json
{
  "title": "Open Day 2025",
  "slug": "open-day-2025",
  "description": "<p>Event details</p>",
  "venue": "Main Hall",
  "event_date": "2025-03-15T09:00:00+03:00",
  "end_date": "2025-03-15T17:00:00+03:00",
  "featured_image": "uploads/images/event.jpg",
  "status": "published"
}
```

### PUT /events/{id}
Update an event.

**Access:** Authenticated (super_admin, admin)

### DELETE /events/{id}
Delete an event.

**Access:** Authenticated (super_admin, admin)

---

## 8. Files Endpoints

### GET /files
List uploaded files.

**Access:** Authenticated (super_admin, admin)

**Query Parameters:** `page`, `per_page`

### POST /files/upload
Upload a file.

**Access:** Authenticated (super_admin, admin)

**Content-Type:** `multipart/form-data`

**Form Fields:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `file` | File | Yes | The file to upload |
| `entity_type` | string | No | Associated entity (page, news, event) |
| `entity_id` | int | No | Associated entity ID |

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 11,
    "filename": "file_abc123_1705312000.jpg",
    "original_name": "photo.jpg",
    "file_path": "uploads/images/file_abc123_1705312000.jpg",
    "file_type": "image",
    "mime_type": "image/jpeg",
    "file_size": 245760,
    "uploaded_by": 1
  },
  "message": "File uploaded successfully"
}
```

### DELETE /files/{id}
Delete a file.

**Access:** Authenticated (super_admin, admin)

---

## 9. Users Endpoints

> All user endpoints require **super_admin** role.

### GET /users
List users.

**Access:** super_admin only

**Query Parameters:** `page`, `per_page`

### POST /users
Create a user.

**Access:** super_admin only

**Request:**
```json
{
  "username": "newuser",
  "email": "user@school.com",
  "password": "SecurePassword123",
  "first_name": "First",
  "last_name": "Last",
  "role": "editor",
  "status": "active"
}
```

### PUT /users/{id}
Update a user.

**Access:** super_admin only

**Request:** Same as POST. Leave `password` blank to keep unchanged.

### DELETE /users/{id}
Delete a user.

**Access:** super_admin only

---

## 10. Static File Serving

### GET /uploads/{path}
Serve uploaded files (images, documents).

**Access:** Public

**Features:**
- ETag-based caching (304 Not Modified)
- Last-Modified header
- Cache-Control: public, max-age=1 year, immutable
- Supports GET and HEAD methods

**Supported MIME Types:**
| Extension | MIME Type |
|-----------|-----------|
| `.jpg` | image/jpeg |
| `.jpeg` | image/jpeg |
| `.png` | image/png |
| `.gif` | image/gif |
| `.webp` | image/webp |
| `.pdf` | application/pdf |
| `.svg` | image/svg+xml |
