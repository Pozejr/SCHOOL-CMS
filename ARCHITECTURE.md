# SCHOOL WEBSITE CMS — TECHNICAL ARCHITECTURE BLUEPRINT

## 1. EXECUTIVE SUMMARY

This document defines the complete technical architecture for a production-ready School Website Content Management System (CMS). The system enables non-technical school staff to manage website content through a secure admin dashboard while presenting a responsive, SEO-friendly public website.

**Key Design Decisions:**
- Pure PHP 8.3+ REST API backend (no framework) with MVC architecture
- React 18+ SPA frontend with Tailwind CSS
- PostgreSQL 16+ for data persistence
- Session-based authentication with role-based access control
- API-driven architecture separating frontend from backend completely
- Zero Docker/Kubernetes — direct server deployment

**System Users:**
| Role | Capabilities |
|------|-------------|
| Super Admin | Full system access, user management, all content |
| Admin | Content management, file uploads, all modules |
| Editor | Create/edit content, file uploads, limited settings |

---

## 2. SYSTEM ARCHITECTURE

### High-Level Architecture

```
┌─────────────────────────────────────────────────────┐
│                    CLIENT LAYER                      │
│  ┌─────────────────┐    ┌─────────────────────────┐ │
│  │  Public Website  │    │    Admin Dashboard SPA   │ │
│  │  (React SPA)     │    │    (React SPA)           │ │
│  └────────┬────────┘    └───────────┬─────────────┘ │
└───────────┼─────────────────────────┼───────────────┘
            │                         │
            │        HTTPS/REST       │
            ▼                         ▼
┌─────────────────────────────────────────────────────┐
│                   WEB SERVER (Nginx)                 │
│  ┌─────────────────────────────────────────────────┐│
│  │  Reverse Proxy → PHP-FPM / Static Files          ││
│  └─────────────────────────────────────────────────┘│
└──────────────────────────┬──────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────┐
│                  APPLICATION LAYER                    │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐            │
│  │ Middleware│ │ Router   │ │ Controller│            │
│  │ Pipeline  │ │          │ │           │            │
│  └────┬─────┘ └────┬─────┘ └─────┬─────┘            │
│       │             │             │                   │
│       ▼             ▼             ▼                   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐            │
│  │ Auth MW  │ │ Service  │ │  Model    │            │
│  │ CSRF MW  │ │ Layer    │ │  Layer    │            │
│  │ Rate MW  │ │          │ │  (PDO)    │            │
│  │ CORS MW  │ │          │ │           │            │
│  └──────────┘ └──────────┘ └──────────┘            │
└──────────────────────────┬──────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────┐
│                    DATA LAYER                        │
│  ┌─────────────────────────────────────────────────┐│
│  │           PostgreSQL Database                    ││
│  └─────────────────────────────────────────────────┘│
│  ┌─────────────────────────────────────────────────┐│
│  │           File Storage (uploads/)                ││
│  └─────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────┘
```

### Request Lifecycle

```
HTTP Request
    → Nginx (reverse proxy)
    → PHP-FPM
    → public/index.php (entry point)
    → Bootstrap (config, session, error handling)
    → Middleware Pipeline (CORS → Rate Limit → CSRF → Auth → Role)
    → Router (match route)
    → Controller (handle request)
    → Service (business logic)
    → Model (database query via PDO)
    → Response (JSON)
    → HTTP Response sent
```

---

## 3. FOLDER STRUCTURE

### Backend

```
backend/
├── api/                        # API version endpoints
│   └── v1/
│       ├── auth.php            # Auth route definitions
│       ├── pages.php           # Page route definitions
│       ├── news.php            # News route definitions
│       ├── events.php          # Event route definitions
│       ├── files.php           # File route definitions
│       ├── dashboard.php       # Dashboard route definitions
│       └── users.php           # User management routes
├── config/
│   ├── app.php                 # Application configuration
│   ├── database.php            # Database configuration
│   ├── cors.php                # CORS configuration
│   ├── upload.php              # Upload configuration
│   └── mail.php                # Email configuration
├── controllers/
│   ├── AuthController.php      # Authentication endpoints
│   ├── PageController.php      # Page CRUD endpoints
│   ├── NewsController.php      # News CRUD endpoints
│   ├── EventController.php     # Event CRUD endpoints
│   ├── FileController.php      # File management endpoints
│   ├── DashboardController.php # Dashboard data endpoints
│   └── UserController.php      # User management endpoints
├── models/
│   ├── BaseModel.php           # Abstract base model
│   ├── User.php                # User entity
│   ├── Page.php                # Page entity
│   ├── News.php                # News entity
│   ├── Event.php               # Event entity
│   ├── File.php                # File entity
│   └── ActivityLog.php         # Activity log entity
├── middleware/
│   ├── AuthMiddleware.php      # Session authentication
│   ├── RoleMiddleware.php      # Role-based authorization
│   ├── CsrfMiddleware.php      # CSRF token validation
│   ├── CorsMiddleware.php      # CORS handling
│   └── RateLimitMiddleware.php # Rate limiting
├── services/
│   ├── AuthService.php         # Authentication logic
│   ├── PageService.php         # Page business logic
│   ├── NewsService.php         # News business logic
│   ├── EventService.php        # Event business logic
│   ├── FileService.php         # File management logic
│   ├── DashboardService.php    # Dashboard aggregation logic
│   └── UserService.php         # User management logic
├── helpers/
│   ├── Response.php            # JSON response helper
│   ├── Validator.php           # Input validation helper
│   ├── SlugGenerator.php       # URL slug generation
│   ├── SessionManager.php      # Session utilities
│   ├── Logger.php              # Logging utility
│   ├── Security.php            # Security utilities
│   └── FileSystem.php          # File system utilities
├── routes/
│   └── api.php                 # Route registration
├── database/
│   ├── migrations/
│   │   ├── 001_create_users_table.sql
│   │   ├── 002_create_pages_table.sql
│   │   ├── 003_create_news_table.sql
│   │   ├── 004_create_events_table.sql
│   │   ├── 005_create_files_table.sql
│   │   ├── 006_create_activity_logs_table.sql
│   │   ├── 007_create_password_resets_table.sql
│   │   ├── 008_create_settings_table.sql
│   │   └── 009_seed_data.sql
│   └── migrate.php             # Migration runner script
├── uploads/                    # User-uploaded files
│   ├── images/
│   └── documents/
├── logs/                       # Application logs
│   └── app.log
├── storage/                    # Temporary storage
├── public/
│   ├── index.php               # Application entry point
│   ├── .htaccess               # Apache rewrite rules
│   └── assets/                 # Publicly accessible assets
├── .env                        # Environment variables
├── .env.example                # Environment template
├── composer.json               # PHP dependencies
└── composer.lock
```

### Frontend

```
frontend/
├── public/
│   ├── index.html              # HTML template
│   ├── favicon.ico
│   └── robots.txt
├── src/
│   ├── assets/
│   │   ├── images/
│   │   └── icons/
│   ├── components/
│   │   ├── common/
│   │   │   ├── Button.jsx
│   │   │   ├── Input.jsx
│   │   │   ├── Select.jsx
│   │   │   ├── Modal.jsx
│   │   │   ├── Table.jsx
│   │   │   ├── Pagination.jsx
│   │   │   ├── Spinner.jsx
│   │   │   ├── Alert.jsx
│   │   │   ├── ConfirmDialog.jsx
│   │   │   ├── FileUpload.jsx
│   │   │   ├── RichTextEditor.jsx
│   │   │   ├── StatusBadge.jsx
│   │   │   ├── EmptyState.jsx
│   │   │   └── SearchBar.jsx
│   │   ├── layout/
│   │   │   ├── AdminSidebar.jsx
│   │   │   ├── AdminHeader.jsx
│   │   │   ├── AdminFooter.jsx
│   │   │   ├── PublicHeader.jsx
│   │   │   ├── PublicFooter.jsx
│   │   │   └── Breadcrumb.jsx
│   │   ├── dashboard/
│   │   │   ├── StatCard.jsx
│   │   │   ├── RecentActivity.jsx
│   │   │   └── QuickActions.jsx
│   │   ├── pages/
│   │   │   ├── PageForm.jsx
│   │   │   └── PageList.jsx
│   │   ├── news/
│   │   │   ├── NewsForm.jsx
│   │   │   └── NewsList.jsx
│   │   ├── events/
│   │   │   ├── EventForm.jsx
│   │   │   └── EventList.jsx
│   │   └── files/
│   │       ├── FileUploader.jsx
│   │       └── FileList.jsx
│   ├── contexts/
│   │   ├── AuthContext.jsx      # Authentication state
│   │   └── ToastContext.jsx     # Notification state
│   ├── hooks/
│   │   ├── useAuth.js           # Auth hook
│   │   ├── useApi.js            # API request hook
│   │   ├── usePagination.js     # Pagination hook
│   │   └── useDebounce.js       # Debounce hook
│   ├── layouts/
│   │   ├── AdminLayout.jsx      # Admin dashboard shell
│   │   └── PublicLayout.jsx     # Public website shell
│   ├── pages/
│   │   ├── admin/
│   │   │   ├── Dashboard.jsx
│   │   │   ├── Login.jsx
│   │   │   ├── Pages.jsx
│   │   │   ├── PageEditor.jsx
│   │   │   ├── News.jsx
│   │   │   ├── NewsEditor.jsx
│   │   │   ├── Events.jsx
│   │   │   ├── EventEditor.jsx
│   │   │   ├── FileManager.jsx
│   │   │   └── Users.jsx
│   │   └── public/
│   │       ├── Home.jsx
│   │       ├── About.jsx
│   │       ├── Academics.jsx
│   │       ├── Admissions.jsx
│   │       ├── News.jsx
│   │       ├── NewsArticle.jsx
│   │       ├── Events.jsx
│   │       └── Contact.jsx
│   ├── routes/
│   │   ├── AppRoutes.jsx        # Route definitions
│   │   ├── AdminRoute.jsx       # Protected admin route
│   │   └── PublicRoute.jsx      # Public route wrapper
│   ├── services/
│   │   ├── api.js               # Axios instance & config
│   │   ├── authService.js       # Auth API calls
│   │   ├── pageService.js       # Page API calls
│   │   ├── newsService.js       # News API calls
│   │   ├── eventService.js      # Event API calls
│   │   ├── fileService.js       # File API calls
│   │   ├── dashboardService.js  # Dashboard API calls
│   │   └── userService.js       # User API calls
│   ├── utils/
│   │   ├── validators.js        # Form validation rules
│   │   ├── formatters.js        # Date/text formatters
│   │   ├── constants.js         # App constants
│   │   └── helpers.js           # Utility functions
│   ├── constants/
│   │   ├── roles.js             # Role definitions
│   │   ├── statuses.js          # Status definitions
│   │   └── apiEndpoints.js      # API route constants
│   ├── styles/
│   │   └── globals.css          # Global styles + Tailwind
│   ├── App.jsx                  # Root component
│   └── main.jsx                 # Entry point
├── tailwind.config.js
├── postcss.config.js
├── vite.config.js
├── package.json
├── .env                         # Frontend environment variables
└── .env.example
```

---

## 4. DATABASE ARCHITECTURE

### Design Principles

1. **Normalization**: All tables are in 3NF minimum
2. **Audit Trail**: Every table includes `created_at`, `updated_at`, `created_by`, `updated_by`
3. **Soft Delete**: Critical tables use `deleted_at` for recoverable deletion
4. **Consistent Naming**: snake_case for columns, table names are plural
5. **UUID vs Auto-increment**: Using BIGSERIAL for simplicity and performance
6. **Indexing Strategy**: Indexes on all foreign keys, status fields, slug fields, and frequently queried columns

### Table Overview

| Table | Purpose | Relationships |
|-------|---------|---------------|
| users | System users & authentication | Has many activity_logs |
| pages | Website pages | Belongs to user (creator) |
| news | News articles | Belongs to user (author) |
| events | School events | Belongs to user (creator) |
| files | Uploaded files | Polymorphic (pages, news, events) |
| activity_logs | Audit trail | Belongs to user |
| password_resets | Password reset tokens | Belongs to user |
| settings | Site-wide settings | None |

---

## 5. DATABASE SCHEMA (SQL MIGRATIONS)

### See `backend/database/migrations/` for complete SQL files.

### Key Design Decisions

- **users.role**: ENUM type for role enforcement at database level
- **content fields**: TEXT type for rich content (supports HTML from WYSIWYG)
- **slug fields**: UNIQUE constraint with index for SEO-friendly URLs
- **status fields**: ENUM with index for efficient filtering
- **file_size**: BIGINT for files larger than 2GB
- **IP addresses**: INET type for PostgreSQL-native IP storage

---

## 6. ERD EXPLANATION

```
┌──────────────┐       ┌──────────────┐
│    users     │       │    pages     │
├──────────────┤       ├──────────────┤
│ id (PK)      │──┐    │ id (PK)      │
│ username     │  │    │ title        │
│ email        │  │    │ slug (UQ)    │
│ password     │  │    │ content      │
│ role         │  │    │ status       │
│ status       │  │    │ meta_title   │
│ first_name   │  │    │ meta_desc    │
│ last_name    │  │    │ featured_img │
│ avatar       │  │    │ sort_order   │
│ last_login   │  │    │ created_by(FK)│──┘
│ created_at   │  │    │ updated_by(FK)│──┘
│ updated_at   │  │    │ created_at   │
└──────────────┘  │    │ updated_at   │
       │          │    └──────────────┘
       │          │
       │          │    ┌──────────────┐
       │          │    │    news      │
       │          │    ├──────────────┤
       │          ├───>│ id (PK)      │
       │          │    │ title        │
       │          │    │ slug (UQ)    │
       │          │    │ content      │
       │          │    │ excerpt      │
       │          │    │ featured_img │
       │          │    │ status       │
       │          │    │ published_at │
       │          │    │ created_by(FK)│──┘
       │          │    │ updated_by(FK)│──┘
       │          │    │ created_at   │
       │          │    │ updated_at   │
       │          │    └──────────────┘
       │          │
       │          │    ┌──────────────┐
       │          │    │   events     │
       │          │    ├──────────────┤
       │          ├───>│ id (PK)      │
       │          │    │ title        │
       │          │    │ slug (UQ)    │
       │          │    │ description  │
       │          │    │ venue        │
       │          │    │ event_date   │
       │          │    │ end_date     │
       │          │    │ featured_img │
       │          │    │ status       │
       │          │    │ created_by(FK)│──┘
       │          │    │ updated_by(FK)│──┘
       │          │    │ created_at   │
       │          │    │ updated_at   │
       │          │    └──────────────┘
       │          │
       │          │    ┌──────────────┐
       │          │    │    files     │
       │          │    ├──────────────┤
       │          ├───>│ id (PK)      │
       │          │    │ filename     │
       │          │    │ original_name│
       │          │    │ file_path    │
       │          │    │ file_type    │
       │          │    │ file_size    │
       │          │    │ mime_type    │
       │          │    │ entity_type  │
       │          │    │ entity_id    │
       │          │    │ uploaded_by(FK)│
       │          │    │ created_at   │
       │          │    └──────────────┘
       │          │
       │          │    ┌──────────────────┐
       │          │    │  activity_logs   │
       │          │    ├──────────────────┤
       │          └───>│ id (PK)          │
       │               │ user_id (FK)     │
       │               │ action           │
       │               │ entity_type      │
       │               │ entity_id        │
       │               │ description      │
       │               │ ip_address       │
       │               │ user_agent       │
       │               │ created_at       │
       │               └──────────────────┘
       │
       │               ┌──────────────────┐
       │               │ password_resets  │
       │               ├──────────────────┤
       └──────────────>│ id (PK)          │
                       │ user_id (FK)     │
                       │ token (UQ)       │
                       │ expires_at       │
                       │ used_at          │
                       │ created_at       │
                       └──────────────────┘

                       ┌──────────────────┐
                       │    settings      │
                       ├──────────────────┤
                       │ id (PK)          │
                       │ key (UQ)         │
                       │ value            │
                       │ type             │
                       │ group_name       │
                       │ updated_at       │
                       └──────────────────┘
```

---

## 7. API ARCHITECTURE

### Base URL
```
/api/v1
```

### Response Format (Standardized)

**Success Response:**
```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 100,
    "total_pages": 7
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": [
      {"field": "title", "message": "Title is required"}
    ]
  }
}
```

### Authentication
- Session-based via HTTP-only cookies
- CSRF token in custom header for mutating requests
- API key not used — session is the auth mechanism

---

## 8. API ENDPOINTS

### Authentication
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/v1/auth/login | No | User login |
| POST | /api/v1/auth/logout | Yes | User logout |
| GET | /api/v1/auth/me | Yes | Get current user |
| POST | /api/v1/auth/csrf-token | No | Get CSRF token |

### Pages
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/v1/pages | No | List published pages (public) |
| GET | /api/v1/pages/admin | Yes | List all pages (admin) |
| GET | /api/v1/pages/:id | No | Get single page |
| GET | /api/v1/pages/slug/:slug | No | Get page by slug |
| POST | /api/v1/pages | Yes | Create page |
| PUT | /api/v1/pages/:id | Yes | Update page |
| DELETE | /api/v1/pages/:id | Yes | Delete page |

### News
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/v1/news | No | List published news |
| GET | /api/v1/news/admin | Yes | List all news |
| GET | /api/v1/news/:id | No | Get single article |
| GET | /api/v1/news/slug/:slug | No | Get article by slug |
| POST | /api/v1/news | Yes | Create article |
| PUT | /api/v1/news/:id | Yes | Update article |
| DELETE | /api/v1/news/:id | Yes | Delete article |

### Events
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/v1/events | No | List published events |
| GET | /api/v1/events/admin | Yes | List all events |
| GET | /api/v1/events/:id | No | Get single event |
| GET | /api/v1/events/slug/:slug | No | Get event by slug |
| POST | /api/v1/events | Yes | Create event |
| PUT | /api/v1/events/:id | Yes | Update event |
| DELETE | /api/v1/events/:id | Yes | Delete event |

### Files
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/v1/files | Yes | List files |
| POST | /api/v1/files/upload | Yes | Upload file |
| DELETE | /api/v1/files/:id | Yes | Delete file |

### Dashboard
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/v1/dashboard/stats | Yes | Dashboard statistics |
| GET | /api/v1/dashboard/activity | Yes | Recent activity |

### Users (Super Admin only)
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/v1/users | Super Admin | List users |
| POST | /api/v1/users | Super Admin | Create user |
| PUT | /api/v1/users/:id | Super Admin | Update user |
| DELETE | /api/v1/users/:id | Super Admin | Delete user |

### Settings
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/v1/settings | Yes | Get settings |
| PUT | /api/v1/settings | Yes | Update settings |

---

## 9. AUTHENTICATION DESIGN

### Session-Based Authentication

```
Login Flow:
1. Client sends POST /api/v1/auth/login {username, password}
2. Server validates credentials
3. Server creates session, stores in $_SESSION
4. Server sets HTTP-only, Secure, SameSite=Strict cookie
5. Server generates CSRF token, stores in session
6. Client stores CSRF token in memory
7. All subsequent requests include cookie automatically

Request Flow:
1. Client sends request with cookie (automatic)
2. Middleware reads session from cookie
3. AuthMiddleware validates session exists and not expired
4. RoleMiddleware checks user has required role
5. CsrfMiddleware validates CSRF token for POST/PUT/DELETE
6. Request proceeds to controller

Logout Flow:
1. Client sends POST /api/v1/auth/logout
2. Server destroys session
3. Server clears cookie
4. Client clears local state
```

### Password Security
- Algorithm: PASSWORD_BCRYPT (default)
- Cost: 12
- Never store plain text passwords
- Use `password_hash()` and `password_verify()`

---

## 10. AUTHORIZATION DESIGN

### Role Hierarchy
```
Super Admin (role: super_admin)
  ├── All Admin permissions
  ├── User management
  ├── System settings
  └── Full data access

Admin (role: admin)
  ├── All Editor permissions
  ├── Publish/unpublish content
  ├── Delete content
  └── File management

Editor (role: editor)
  ├── Create content
  ├── Edit own content
  ├── Upload files
  └── View dashboard
```

### Permission Matrix
| Action | Super Admin | Admin | Editor |
|--------|:-----------:|:-----:|:------:|
| Manage Users | ✅ | ❌ | ❌ |
| System Settings | ✅ | ❌ | ❌ |
| Create Content | ✅ | ✅ | ✅ |
| Edit Any Content | ✅ | ✅ | ❌ |
| Edit Own Content | ✅ | ✅ | ✅ |
| Delete Content | ✅ | ✅ | ❌ |
| Publish Content | ✅ | ✅ | ❌ |
| Upload Files | ✅ | ✅ | ✅ |
| View Dashboard | ✅ | ✅ | ✅ |
| Delete Files | ✅ | ✅ | ❌ |

---

## 11. SECURITY PLAN

### Password Hashing
```php
// Hashing
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verification
$valid = password_verify($input, $hash);
```

### Session Security
- HTTP-only cookies (not accessible via JavaScript)
- Secure flag (HTTPS only in production)
- SameSite=Strict
- Session timeout: 2 hours inactivity
- Session regeneration on login
- Destroy session on logout

### CSRF Protection
- Token generated per session
- Required for all POST/PUT/DELETE/PATCH requests
- Sent via X-CSRF-Token header
- Validated server-side before processing

### XSS Prevention
- All output escaped with `htmlspecialchars()`
- Content-Security-Policy header
- Input sanitization on all user inputs
- React's built-in JSX escaping

### SQL Injection Prevention
- PDO prepared statements exclusively
- Never concatenate SQL strings
- Parameterized queries for all operations

### File Upload Security
- Whitelist allowed MIME types: image/jpeg, image/png, image/gif, image/webp, application/pdf
- Maximum file size: 5MB images, 10MB PDFs
- Validate actual file content (not just extension)
- Rename files with unique names
- Store outside web root when possible
- Never allow executable file types

### Rate Limiting
- Login: 5 attempts per 15 minutes per IP
- API: 100 requests per minute per session
- File upload: 20 per hour per user

### Secure Headers
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains
Referrer-Policy: strict-origin-when-cross-origin
```

### Environment Variables
- All secrets in .env file
- .env excluded from version control
- Never commit credentials
- Separate .env for each environment

---

## 12. REACT ARCHITECTURE

### State Management
- **AuthContext**: User session state, login/logout, CSRF token
- **ToastContext**: Notification messages (success, error, warning)
- **Local state**: Component-level state with useState
- **No Redux needed**: The app is simple enough for Context + hooks

### Data Flow
```
Component → Service → API (Axios) → Backend
                ↑
          Context (Auth, Toast)
```

### Routing Architecture
```
/                          → Public Home
/about                     → Public About
/academics                 → Public Academics
/admissions                → Public Admissions
/news                      → Public News List
/news/:slug                → Public News Article
/events                    → Public Events
/contact                   → Public Contact

/admin/login               → Admin Login
/admin                     → Admin Dashboard
/admin/pages               → Page Management
/admin/pages/new           → Create Page
/admin/pages/:id/edit      → Edit Page
/admin/news                → News Management
/admin/news/new            → Create News
/admin/news/:id/edit       → Edit News
/admin/events              → Events Management
/admin/events/new          → Create Event
/admin/events/:id/edit     → Edit Event
/admin/files               → File Manager
/admin/users               → User Management (Super Admin)
```

---

## 13. UI ARCHITECTURE

### Design System
- **CSS Framework**: Tailwind CSS
- **Color Palette**: Blue primary (#1e40af), Green success (#16a34a), Red danger (#dc2626)
- **Typography**: System font stack (Inter, system-ui)
- **Icons**: Inline SVG (no external dependency)
- **Spacing**: Tailwind's 4px base grid

### Admin Dashboard Layout
```
┌────────────────────────────────────────────┐
│  Header: Logo | Search | User Menu        │
├──────────┬─────────────────────────────────┤
│          │  Breadcrumb                     │
│ Sidebar  │  ┌──────┬──────┬──────┬──────┐ │
│          │  │ Stat │ Stat │ Stat │ Stat │ │
│ - Dash   │  │ Card │ Card │ Card │ Card │ │
│ - Pages  │  └──────┴──────┴──────┴──────┘ │
│ - News   │                                 │
│ - Events │  ┌────────────┬───────────────┐ │
│ - Files  │  │  Recent    │  Quick        │ │
│ - Users  │  │  Activity  │  Actions      │ │
│          │  └────────────┴───────────────┘ │
└──────────┴─────────────────────────────────┘
```

### Public Website Layout
```
┌────────────────────────────────────────────┐
│  Header: Logo | Nav Links | Search        │
├────────────────────────────────────────────┤
│                                            │
│  Hero Section / Page Content               │
│                                            │
│  ┌─────────────────────────────────────┐  │
│  │  Main Content Area                  │  │
│  └─────────────────────────────────────┘  │
│                                            │
├────────────────────────────────────────────┤
│  Footer: Links | Contact | Copyright      │
└────────────────────────────────────────────┘
```

---

## 14. DEPLOYMENT ARCHITECTURE

### Development (Windows)
```
Frontend: Vite dev server (localhost:5173)
Backend:  PHP built-in server (localhost:8000)
Database: PostgreSQL on localhost:5432
Proxy:    Vite proxy to backend
```

### Production (Ubuntu + Nginx)
```
Frontend: Built static files served by Nginx
Backend:  PHP-FPM pool served by Nginx
Database: PostgreSQL on same server or dedicated
SSL:      Let's Encrypt / Certbot
```

### Nginx Configuration (Production)
```nginx
server {
    listen 443 ssl http2;
    server_name school.example.com;

    ssl_certificate /etc/letsencrypt/live/school.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/school.example.com/privkey.pem;

    root /var/www/school-cms/frontend/dist;
    index index.html;

    # Frontend routes (SPA)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API proxy
    location /api/ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/school-cms/backend/public/index.php;
        include fastcgi_params;
    }

    # Uploads
    location /uploads/ {
        alias /var/www/school-cms/backend/uploads/;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000" always;
}
```

---

## 15. LOCAL DEVELOPMENT SETUP

See Section 17 for complete step-by-step commands.

---

## 16. PRODUCTION DEPLOYMENT GUIDE

### Prerequisites
- Ubuntu 22.04/24.04 LTS
- PHP 8.3+
- PostgreSQL 16+
- Nginx
- Node.js 20+
- Composer
- SSL certificate (Let's Encrypt)

### Deployment Steps
1. SSH into server
2. Install system dependencies
3. Clone repository
4. Install Composer dependencies
5. Install Node.js dependencies
6. Configure .env
7. Run database migrations
8. Build frontend
9. Configure Nginx
10. Setup SSL
11. Configure PHP-FPM
12. Set file permissions
13. Setup log rotation
14. Enable firewall (UFW)
15. Test deployment

---

## 17. STEP-BY-STEP PROJECT EXECUTION ROADMAP

### Phase 1A: Foundation (Week 1)
1. Initialize project structure
2. Setup backend entry point and routing
3. Configure database connection
4. Create database migrations
5. Run migrations

### Phase 1B: Core Backend (Week 2)
6. Implement BaseModel with CRUD operations
7. Implement all Models
8. Implement AuthController and AuthService
9. Implement middleware (Auth, Role, CSRF, CORS, Rate)
10. Implement PageController and PageService
11. Implement NewsController and NewsService
12. Implement EventController and EventService
13. Implement FileController and FileService
14. Implement DashboardController

### Phase 1C: Frontend Foundation (Week 3)
15. Initialize React project with Vite
16. Configure Tailwind CSS
17. Setup routing
18. Create layouts (Admin + Public)
19. Implement AuthContext
20. Create API service layer

### Phase 1D: Frontend Pages (Week 4)
21. Build Login page
22. Build Admin Dashboard
23. Build Page Management (list + editor)
24. Build News Management (list + editor)
25. Build Events Management (list + editor)
26. Build File Manager
27. Build User Management
28. Build all Public website pages

### Phase 1E: Integration & Testing (Week 5)
29. End-to-end testing
30. Security audit
31. Performance optimization
32. Documentation

---

## 18. DEVELOPMENT MILESTONES

| Milestone | Deliverable | Timeline |
|-----------|-------------|----------|
| M1 | Project structure, DB schema, backend skeleton | End of Week 1 |
| M2 | All backend APIs functional and tested | End of Week 2 |
| M3 | Frontend shell, auth flow, layouts working | End of Week 3 |
| M4 | All frontend pages functional | End of Week 4 |
| M5 | Production-ready, documented, deployed | End of Week 5 |

---

## 19. RISKS AND MITIGATION STRATEGIES

| Risk | Impact | Mitigation |
|------|--------|------------|
| XSS via rich text content | High | HTMLPurifier or strict sanitization |
| Session hijacking | High | Secure cookies, session regeneration, IP binding |
| File upload vulnerability | Critical | Whitelist MIME types, validate content, rename files |
| Brute force attacks | Medium | Rate limiting, account lockout |
| SQL injection | Critical | PDO prepared statements exclusively |
| Data loss | High | Automated PostgreSQL backups, soft deletes |
| Performance degradation | Medium | Database indexing, query optimization, caching |
| Single point of failure | Medium | Health monitoring, automated restarts |

---

## 20. FINAL RECOMMENDED ARCHITECTURE

**Backend**: Pure PHP 8.3 MVC with PDO, RESTful JSON API
**Frontend**: React 18 SPA with Vite, Tailwind CSS, React Router v6
**Database**: PostgreSQL 16 with full migration system
**Authentication**: Session-based with HTTP-only cookies + CSRF
**Authorization**: Role-based with middleware pipeline
**File Storage**: Local filesystem with database metadata
**Deployment**: Nginx + PHP-FPM on Ubuntu, Let's Encrypt SSL
**Development**: PHP built-in server + Vite dev server with proxy

This architecture provides enterprise-grade security, maintainability, and scalability without framework overhead or containerization complexity.
