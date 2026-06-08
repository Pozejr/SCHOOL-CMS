# School CMS — Routes Documentation

## 1. Overview

The School CMS uses two routing systems:
- **Backend**: PHP router in `ApiRouter.php` handles API requests at `/api/v1/*`
- **Frontend**: React Router 6 handles client-side page navigation

All API routes follow the pattern: `http://localhost:8000/api/v1/{resource}[/{id}]`

## 2. Public Routes (No Authentication Required)

These routes are accessible to all visitors, including search engine crawlers and unauthenticated guests.

### 2.1 Frontend Public Routes

| Route | Component | Description |
|-------|-----------|-------------|
| `/` | `Home` | School homepage |
| `/about` | `About` | About the school |
| `/academics` | `Academics` | Academic programs |
| `/admissions` | `Admissions` | Admissions information |
| `/news` | `PublicNews` | News articles listing |
| `/news/:slug` | `PublicNewsArticle` | Individual news article |
| `/events` | `PublicEvents` | Events listing |
| `/contact` | `Contact` | Contact information |
| `/page/:slug` | `PublicPage` | Dynamic CMS page |
| `/admin/login` | `Login` | Admin login page |

### 2.2 Backend Public API Routes

| Method | Endpoint | Description | Auth | Roles |
|--------|----------|-------------|------|-------|
| `POST` | `/auth/login` | Authenticate user | ❌ | — |
| `GET` | `/auth/csrf-token` | Get CSRF token | ❌ | — |
| `GET` | `/pages` | List published pages | ❌ | — |
| `GET` | `/pages/slug/{slug}` | Get page by slug | ❌ | — |
| `GET` | `/pages/{id}` | Get page by ID | ❌ | — |
| `GET` | `/news` | List published news | ❌ | — |
| `GET` | `/news/slug/{slug}` | Get news by slug | ❌ | — |
| `GET` | `/news/{id}` | Get news by ID | ❌ | — |
| `GET` | `/events` | List published events | ❌ | — |
| `GET` | `/events/slug/{slug}` | Get event by slug | ❌ | — |
| `GET` | `/events/{id}` | Get event by ID | ❌ | — |
| `GET` | `/uploads/{path}` | Serve static files | ❌ | — |

## 3. Protected Routes (Authentication Required)

These routes require a valid session. Users must be logged in.

### 3.1 Authenticated API Routes

| Method | Endpoint | Description | Auth | Roles |
|--------|----------|-------------|------|-------|
| `POST` | `/auth/logout` | End session | ✅ | Any authenticated |
| `GET` | `/auth/me` | Get current user | ✅ | Any authenticated |

## 4. Admin Routes (super_admin or admin Required)

These routes require both authentication AND the specified role.

### 4.1 Frontend Admin Routes

| Route | Component | Allowed Roles | Description |
|-------|-----------|---------------|-------------|
| `/admin` | `Dashboard` | super_admin, admin | Dashboard overview |
| `/admin/pages` | `Pages` | super_admin, admin | Pages listing |
| `/admin/pages/new` | `PageBuilder` | super_admin, admin | Create new page |
| `/admin/pages/:id/edit` | `PageEditor` | super_admin, admin | Edit page |
| `/admin/news` | `NewsAdmin` | super_admin, admin | News listing |
| `/admin/news/new` | `NewsEditor` | super_admin, admin | Create news |
| `/admin/news/:id/edit` | `NewsEditor` | super_admin, admin | Edit news |
| `/admin/events` | `EventsAdmin` | super_admin, admin | Events listing |
| `/admin/events/new` | `EventEditor` | super_admin, admin | Create event |
| `/admin/events/:id/edit` | `EventEditor` | super_admin, admin | Edit event |
| `/admin/files` | `FileManager` | super_admin, admin | Media library |

### 4.2 Super Admin Only Routes (Frontend)

| Route | Component | Allowed Roles | Description |
|-------|-----------|---------------|-------------|
| `/admin/users` | `Users` | super_admin | User management |

### 4.3 Admin API Routes (Backend)

| Method | Endpoint | Description | Auth | Roles |
|--------|----------|-------------|------|-------|
| `GET` | `/dashboard/stats` | Dashboard statistics | ✅ | super_admin, admin |
| `GET` | `/dashboard/activity` | Recent activity | ✅ | super_admin, admin |
| `GET` | `/pages/admin/all` | All pages (incl. drafts) | ✅ | super_admin, admin |
| `POST` | `/pages` | Create page | ✅ | super_admin, admin |
| `PUT` | `/pages/{id}` | Update page | ✅ | super_admin, admin |
| `DELETE` | `/pages/{id}` | Delete page | ✅ | super_admin, admin |
| `GET` | `/pages/{page_id}/sections` | List sections | ✅ | super_admin, admin |
| `POST` | `/pages/{page_id}/sections` | Create section | ✅ | super_admin, admin |
| `PUT` | `/pages/sections/{id}` | Update section | ✅ | super_admin, admin |
| `DELETE` | `/pages/sections/{id}` | Delete section | ✅ | super_admin, admin |
| `PUT` | `/pages/{page_id}/sections/reorder` | Reorder sections | ✅ | super_admin, admin |
| `GET` | `/templates` | List templates | ✅ | super_admin, admin |
| `GET` | `/templates/{slug}` | Get template | ✅ | super_admin, admin |
| `POST` | `/pages/{page_id}/apply-template` | Apply template | ✅ | super_admin, admin |
| `GET` | `/news/admin/all` | All news (incl. drafts) | ✅ | super_admin, admin |
| `POST` | `/news` | Create news | ✅ | super_admin, admin |
| `PUT` | `/news/{id}` | Update news | ✅ | super_admin, admin |
| `DELETE` | `/news/{id}` | Delete news | ✅ | super_admin, admin |
| `GET` | `/events/admin/all` | All events (incl. drafts) | ✅ | super_admin, admin |
| `POST` | `/events` | Create event | ✅ | super_admin, admin |
| `PUT` | `/events/{id}` | Update event | ✅ | super_admin, admin |
| `DELETE` | `/events/{id}` | Delete event | ✅ | super_admin, admin |
| `GET` | `/files` | List files | ✅ | super_admin, admin |
| `POST` | `/files/upload` | Upload file | ✅ | super_admin, admin |
| `DELETE` | `/files/{id}` | Delete file | ✅ | super_admin, admin |

### 4.4 Super Admin Only API Routes (Backend)

| Method | Endpoint | Description | Auth | Roles |
|--------|----------|-------------|------|-------|
| `GET` | `/users` | List users | ✅ | super_admin |
| `POST` | `/users` | Create user | ✅ | super_admin |
| `PUT` | `/users/{id}` | Update user | ✅ | super_admin |
| `DELETE` | `/users/{id}` | Delete user | ✅ | super_admin |

## 5. Special Routes

| Route | Component | Description |
|-------|-----------|-------------|
| `/access-denied` | `AccessDenied` | Displayed when user lacks required role |
| `*` (any unmatched) | `NotFound` | 404 page not found |

## 6. Access Permissions Summary

### Frontend Route Access Matrix

| Route | Guest | Editor | Admin | Super Admin |
|-------|:-----:|:------:|:-----:|:-----------:|
| `/` | ✅ | ✅ | ✅ | ✅ |
| `/about` | ✅ | ✅ | ✅ | ✅ |
| `/academics` | ✅ | ✅ | ✅ | ✅ |
| `/admissions` | ✅ | ✅ | ✅ | ✅ |
| `/news` | ✅ | ✅ | ✅ | ✅ |
| `/news/:slug` | ✅ | ✅ | ✅ | ✅ |
| `/events` | ✅ | ✅ | ✅ | ✅ |
| `/contact` | ✅ | ✅ | ✅ | ✅ |
| `/page/:slug` | ✅ | ✅ | ✅ | ✅ |
| `/admin/login` | ✅ | ✅ | ✅ | ✅ |
| `/admin` | ❌ | ❌ | ✅ | ✅ |
| `/admin/pages` | ❌ | ❌ | ✅ | ✅ |
| `/admin/news` | ❌ | ❌ | ✅ | ✅ |
| `/admin/events` | ❌ | ❌ | ✅ | ✅ |
| `/admin/files` | ❌ | ❌ | ✅ | ✅ |
| `/admin/users` | ❌ | ❌ | ❌ | ✅ |

### API Route Protection Matrix

| Endpoint Pattern | Guest | Editor | Admin | Super Admin |
|-----------------|:-----:|:------:|:-----:|:-----------:|
| `GET /pages` | ✅ | ✅ | ✅ | ✅ |
| `GET /news` | ✅ | ✅ | ✅ | ✅ |
| `GET /events` | ✅ | ✅ | ✅ | ✅ |
| `POST /auth/login` | ✅ | ✅ | ✅ | ✅ |
| `POST /auth/logout` | ❌ | ✅ | ✅ | ✅ |
| `GET /auth/me` | ❌ | ✅ | ✅ | ✅ |
| `GET /dashboard/*` | ❌ | ❌ | ✅ | ✅ |
| `POST /pages` | ❌ | ❌ | ✅ | ✅ |
| `PUT /pages/{id}` | ❌ | ❌ | ✅ | ✅ |
| `DELETE /pages/{id}` | ❌ | ❌ | ✅ | ✅ |
| `POST /news` | ❌ | ❌ | ✅ | ✅ |
| `POST /events` | ❌ | ❌ | ✅ | ✅ |
| `POST /files/upload` | ❌ | ❌ | ✅ | ✅ |
| `GET /users` | ❌ | ❌ | ❌ | ✅ |
| `POST /users` | ❌ | ❌ | ❌ | ✅ |
| `PUT /users/{id}` | ❌ | ❌ | ❌ | ✅ |
| `DELETE /users/{id}` | ❌ | ❌ | ❌ | ✅ |
