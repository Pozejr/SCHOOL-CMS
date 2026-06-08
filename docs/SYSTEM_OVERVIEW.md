# School CMS — System Overview

## 1. System Description

School CMS is a custom-built content management system designed for educational institutions. It provides a modern web platform for schools to manage and publish their website content, news articles, events, media files, and user accounts through an intuitive administration dashboard.

The system follows a decoupled architecture with a PHP 8.4 backend API and a React 18 single-page application (SPA) frontend, communicating via RESTful JSON endpoints.

## 2. Objectives

- **Content Management**: Enable non-technical staff to create, edit, and publish website pages using a section-based page builder
- **News & Events**: Manage school news articles and events with rich content, featured images, and SEO metadata
- **Media Management**: Upload, organize, and manage images and documents through a centralized media library
- **User Administration**: Provide role-based access control with granular permissions for super admins, admins, and editors
- **Public Website**: Deliver a fast, responsive public-facing website optimized for search engines and mobile devices
- **Security**: Enforce authentication, authorization, CSRF protection, rate limiting, and secure session management

## 3. Features

### 3.1 Public Website
- Responsive design with mobile-first approach
- Home page, About, Academics, Admissions, Contact pages
- News listing with individual article pages (slug-based URLs)
- Events listing with upcoming event details
- Dynamic page rendering from CMS content
- SEO-optimized pages with meta tags and structured data (JSON-LD)
- Fast static file serving with ETag/304 caching
- Gzip-compressed API responses

### 3.2 Administration Dashboard
- **Dashboard**: Overview statistics (total pages, news, events, users) and recent activity feed
- **Page Builder**: Section-based page creation with multiple section types (text, gallery, video, quote, contact), layout options (full, left, right, centered), drag-and-drop reordering, and template support
- **News Management**: Create, edit, publish/draft news articles with featured images, excerpts, and slug generation
- **Events Management**: Create, edit, publish/draft events with venue, date ranges, and featured images
- **Media Library**: Upload images and documents, browse files with pagination, and select images via an integrated picker
- **User Management**: Create, edit, delete users with role assignment (super_admin only)
- **Templates**: Pre-defined page templates for quick page creation (About, Admissions, Academics, Contact, Department)

### 3.3 Security
- Session-based authentication with bcrypt password hashing (cost 12)
- Role-based access control (super_admin, admin, editor)
- CSRF token protection on all mutating requests
- Account lockout after 5 failed login attempts (30-minute cooldown)
- Secure session cookies (httponly, samesite=strict, secure on HTTPS)
- Security headers (X-Content-Type-Options, X-Frame-Options, XSS-Protection, HSTS)
- Rate limiting on API endpoints
- Server-side input validation and sanitization

## 4. Technology Stack

### Backend
| Component | Technology | Version |
|-----------|-----------|---------|
| Language | PHP | 8.4 |
| Database | PostgreSQL | 17 |
| Web Server | PHP Built-in (dev) | 8.4 |
| Architecture | Custom MVC | — |
| Connection | PDO (persistent) | — |
| Password Hashing | bcrypt | cost 12 |
| Autoloading | Composer PSR-4 | 2.x |

### Frontend
| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | React | 18.3 |
| Routing | React Router DOM | 6.26 |
| HTTP Client | Axios | 1.7 |
| Styling | TailwindCSS | 3.4 |
| Build Tool | Vite | 5.4 |
| Module System | ES Modules | — |
| Code Splitting | React.lazy + Suspense | — |

### Development Tools
| Tool | Purpose |
|------|---------|
| Vite Dev Server | Frontend development with HMR |
| PHP Built-in Server | Backend API serving |
| PostgreSQL | Database management |
| Composer | PHP dependency management |
| npm | Frontend dependency management |

## 5. Architecture Overview

```
┌──────────────────────────────────────────────────────┐
│                    Browser / Client                    │
│                     (React 18 SPA)                     │
├──────────────────────────────────────────────────────┤
│  React Router 6  │  Auth Context  │  Axios (API)     │
│  (Route Guards)  │  (JWT/Session) │  (with CSRF)     │
└──────────┬───────────────────────────────────────┬────┘
           │  HTTP/JSON (REST API)                  │
           │  /api/v1/*                             │
┌──────────▼───────────────────────────────────────▼────┐
│                  PHP 8.4 Backend API                   │
├──────────────────────────────────────────────────────┤
│  index.php → Middleware → Router → Controller          │
├──────────────────────────────────────────────────────┤
│  Middleware Pipeline:                                   │
│    1. Static File Handler (ETag/304 caching)           │
│    2. Gzip Compression (ob_gzhandler)                  │
│    3. CORS Middleware                                   │
│    4. Session Manager                                   │
│    5. CSRF Middleware                                   │
│    6. Auth Middleware (session validation)              │
│    7. Role Middleware (authorization)                   │
├──────────────────────────────────────────────────────┤
│  Controller → Service → Model → PostgreSQL 17          │
└──────────────────────────────────────────────────────┘
```

### Request Flow
1. **Client** makes HTTP request to `/api/v1/*`
2. **index.php** loads environment, configurations, and initializes dependencies
3. **Static File Handler** intercepts `/uploads/*` requests and serves files directly
4. **Gzip Compression** buffers API responses for compression
5. **CORS Middleware** handles cross-origin headers and preflight requests
6. **Session Manager** starts/resumes PHP session
7. **CSRF Middleware** validates tokens on mutating requests
8. **ApiRouter** matches URL pattern and HTTP method to route definition
9. **Auth Middleware** checks session validity for protected routes (401 if unauthenticated)
10. **Role Middleware** checks user role against allowed roles (403 if unauthorized)
11. **Controller** processes request, calls Service layer
12. **Service** applies business logic, calls Model layer
13. **Model** executes SQL queries via PDO against PostgreSQL
14. **Response** sends JSON response back to client

### Directory Structure
```
school-cms/
├── backend/
│   ├── config/           # Configuration files (app, database, cors, upload, opcache)
│   ├── controllers/      # Request handlers (Auth, Page, News, Event, File, User, Dashboard)
│   ├── database/
│   │   └── migrations/   # SQL migration files (001-012)
│   ├── helpers/          # Utility classes (Response, Security, Session, Logger, etc.)
│   ├── middleware/       # HTTP middleware (Auth, Role, CSRF, CORS, RateLimit)
│   ├── models/           # Data access layer (User, Page, News, Event, File, etc.)
│   ├── public/
│   │   └── index.php     # API entry point
│   ├── routes/
│   │   ├── ApiRouter.php # Router engine
│   │   └── api.php       # Route definitions (unused — routes in ApiRouter)
│   ├── services/         # Business logic layer
│   ├── uploads/          # Uploaded files directory
│   └── vendor/           # Composer dependencies
├── frontend/
│   ├── public/           # Static assets
│   ├── src/
│   │   ├── components/   # Reusable UI components
│   │   │   ├── common/   # Shared components (Button, Input, Modal, etc.)
│   │   │   └── layout/   # Layout components (Headers, Sidebar, Footer)
│   │   ├── constants/    # Constants (API endpoints, roles, statuses)
│   │   ├── contexts/     # React contexts (Auth, Toast)
│   │   ├── hooks/        # Custom hooks (useApi, useAuth, useDebounce, usePagination)
│   │   ├── layouts/      # Page layouts (AdminLayout, PublicLayout)
│   │   ├── pages/
│   │   │   ├── admin/    # Admin pages (Dashboard, CRUD editors)
│   │   │   └── public/   # Public pages (Home, About, etc.)
│   │   ├── routes/       # Route guards and definitions
│   │   ├── services/     # API service modules
│   │   ├── utils/        # Utility functions
│   │   └── main.jsx      # Application entry point
│   └── vite.config.js    # Vite configuration
└── docs/                 # Documentation files
```
