# School CMS — Developer Guide

## 1. Folder Structure

```
school-cms/
├── backend/                         # PHP 8.4 Backend
│   ├── config/                      # Configuration files
│   │   ├── app.php                  # Application config (timezone, session name, debug)
│   │   ├── cors.php                 # CORS configuration
│   │   ├── database.php             # Database connection config (from env)
│   │   ├── opcache.ini              # OPcache performance settings
│   │   └── upload.php               # File upload limits and allowed types
│   ├── controllers/                 # Request handlers (thin layer)
│   │   ├── AuthController.php       # Login, logout, me, csrf-token
│   │   ├── DashboardController.php  # Dashboard stats and activity
│   │   ├── EventController.php      # Event CRUD endpoints
│   │   ├── FileController.php       # File upload and listing
│   │   ├── NewsController.php       # News CRUD endpoints
│   │   ├── PageController.php       # Page CRUD endpoints
│   │   ├── PageSectionController.php# Section CRUD + templates
│   │   └── UserController.php       # User CRUD (super_admin only)
│   ├── database/
│   │   ├── migrate.php              # Migration runner
│   │   └── migrations/              # SQL migration files (001-012)
│   ├── helpers/                     # Utility classes
│   │   ├── ContentParser.php        # Markdown/HTML content parsing with cache
│   │   ├── FileSystem.php           # File system operations
│   │   ├── Logger.php               # File-based logging
│   │   ├── Response.php             # JSON response helpers
│   │   ├── Security.php             # Password hashing, sanitization, file validation
│   │   ├── SeoGenerator.php         # SEO meta tags and JSON-LD generation
│   │   ├── SessionManager.php       # Session management and CSRF
│   │   ├── SlugGenerator.php        # URL slug generation
│   │   └── Validator.php            # Input validation rules
│   ├── middleware/                  # HTTP middleware
│   │   ├── AuthMiddleware.php       # Session authentication check
│   │   ├── CorsMiddleware.php       # Cross-origin request handling
│   │   ├── CsrfMiddleware.php       # CSRF token validation
│   │   ├── RateLimitMiddleware.php  # API rate limiting
│   │   └── RoleMiddleware.php       # Role-based authorization
│   ├── models/                      # Data access layer (active record pattern)
│   │   ├── ActivityLog.php          # Activity log model
│   │   ├── BaseModel.php            # Abstract base model (CRUD, pagination)
│   │   ├── Event.php                # Event model
│   │   ├── File.php                 # File model
│   │   ├── News.php                 # News model
│   │   ├── Page.php                 # Page model
│   │   ├── PageSection.php          # Page section model
│   │   ├── PageTemplate.php         # Page template model
│   │   └── User.php                 # User model
│   ├── public/
│   │   └── index.php                # API entry point (bootstrapping, DI, routing)
│   ├── routes/
│   │   ├── ApiRouter.php            # Router engine with route definitions
│   │   └── api.php                  # Legacy route file (unused)
│   ├── services/                    # Business logic layer
│   │   ├── AuthService.php          # Authentication logic
│   │   ├── DashboardService.php     # Dashboard statistics
│   │   ├── EventService.php         # Event business logic
│   │   ├── FileService.php          # File upload logic
│   │   ├── NewsService.php          # News business logic
│   │   ├── PageSectionService.php   # Section business logic
│   │   ├── PageService.php          # Page business logic
│   │   ├── TemplateService.php      # Template business logic
│   │   └── UserService.php          # User business logic
│   ├── uploads/                     # Uploaded files directory
│   │   ├── images/                  # Uploaded images
│   │   └── documents/               # Uploaded documents
│   ├── .env                         # Environment variables
│   └── composer.json                # PHP dependencies
│
├── frontend/                        # React 18 Frontend
│   ├── public/                      # Static assets
│   ├── src/
│   │   ├── components/              # Reusable UI components
│   │   │   ├── common/              # Shared components
│   │   │   │   ├── Alert.jsx        # Alert/notification component
│   │   │   │   ├── Button.jsx       # Button with loading state
│   │   │   │   ├── ConfirmDialog.jsx # Confirmation modal
│   │   │   │   ├── ContentPreview.jsx# Content preview renderer
│   │   │   │   ├── EmptyState.jsx   # Empty state placeholder
│   │   │   │   ├── FileUpload.jsx   # File upload component
│   │   │   │   ├── ImagePicker.jsx  # Image selector with preview
│   │   │   │   ├── Input.jsx        # Form input with label/error
│   │   │   │   ├── MediaLibraryModal.jsx # Media browser modal
│   │   │   │   ├── Modal.jsx        # Reusable modal
│   │   │   │   ├── PageForm.jsx     # Page creation/edit form
│   │   │   │   ├── Pagination.jsx   # Pagination controls
│   │   │   │   ├── SearchBar.jsx    # Search input
│   │   │   │   ├── SectionRenderer.jsx # Section type renderer
│   │   │   │   ├── Select.jsx       # Dropdown select
│   │   │   │   ├── Spinner.jsx      # Loading spinner
│   │   │   │   ├── StatusBadge.jsx  # Status badge
│   │   │   │   ├── Table.jsx        # Data table component
│   │   │   │   └── index.js         # Barrel exports
│   │   │   └── layout/              # Layout components
│   │   │       ├── AdminHeader.jsx  # Admin top navigation bar
│   │   │       ├── AdminSidebar.jsx # Admin side navigation
│   │   │       ├── PublicFooter.jsx # Public website footer
│   │   │       └── PublicHeader.jsx # Public website header
│   │   ├── constants/               # Application constants
│   │   │   ├── apiEndpoints.js      # API URL constants
│   │   │   ├── roles.js             # Role definitions
│   │   │   └── statuses.js          # Status constants
│   │   ├── contexts/                # React contexts
│   │   │   ├── AuthContext.jsx      # Authentication state
│   │   │   └── ToastContext.jsx     # Toast notification state
│   │   ├── hooks/                   # Custom React hooks
│   │   │   ├── useApi.js            # API data fetching hook
│   │   │   ├── useAuth.js           # Authentication hook
│   │   │   ├── useDebounce.js       # Debounce hook
│   │   │   └── usePagination.js     # Pagination hook
│   │   ├── layouts/                 # Page layouts
│   │   │   ├── AdminLayout.jsx      # Admin dashboard layout
│   │   │   └── PublicLayout.jsx     # Public website layout
│   │   ├── pages/
│   │   │   ├── admin/               # Admin pages
│   │   │   │   ├── Dashboard.jsx    # Dashboard overview
│   │   │   │   ├── EventEditor.jsx  # Event create/edit
│   │   │   │   ├── EventsAdmin.jsx  # Events listing
│   │   │   │   ├── FileManager.jsx  # Media library
│   │   │   │   ├── Login.jsx        # Login page
│   │   │   │   ├── NewsAdmin.jsx    # News listing
│   │   │   │   ├── NewsEditor.jsx   # News create/edit
│   │   │   │   ├── PageBuilder.jsx  # Page creation with sections
│   │   │   │   ├── PageEditor.jsx   # Page editing
│   │   │   │   ├── Pages.jsx        # Pages listing
│   │   │   │   └── Users.jsx        # User management
│   │   │   └── public/              # Public pages
│   │   │       ├── About.jsx        # About page
│   │   │       ├── Academics.jsx    # Academics page
│   │   │       ├── Admissions.jsx   # Admissions page
│   │   │       ├── Contact.jsx      # Contact page
│   │   │       ├── Home.jsx         # Homepage
│   │   │       ├── PublicEvents.jsx # Events listing
│   │   │       ├── PublicNews.jsx   # News listing
│   │   │       ├── PublicNewsArticle.jsx # Single news article
│   │   │       └── PublicPage.jsx   # Dynamic page renderer
│   │   ├── routes/                  # Route configuration
│   │   │   ├── AdminRoute.jsx       # Admin route guard
│   │   │   ├── AppRoutes.jsx        # All route definitions
│   │   │   └── PublicRoute.jsx      # Public route guard (unused)
│   │   ├── services/                # API service modules
│   │   │   ├── api.js               # Axios instance + interceptors
│   │   │   ├── authService.js       # Auth API calls
│   │   │   ├── dashboardService.js  # Dashboard API calls
│   │   │   ├── eventService.js      # Events API calls
│   │   │   ├── fileService.js       # Files API calls
│   │   │   ├── newsService.js       # News API calls
│   │   │   ├── pageService.js       # Pages API calls
│   │   │   └── userService.js       # Users API calls
│   │   ├── utils/
│   │   │   └── helpers.js           # Utility functions (resolveImagePath, formatDate)
│   │   ├── App.jsx                  # Root component
│   │   └── main.jsx                 # Entry point with providers
│   ├── .env                         # Frontend env vars
│   ├── index.html                   # HTML template
│   ├── package.json                 # Node dependencies
│   ├── postcss.config.js            # PostCSS config
│   ├── tailwind.config.js           # TailwindCSS config
│   └── vite.config.js               # Vite build config
│
└── docs/                            # Documentation
```

## 2. Component Architecture

### Backend Architecture (Custom MVC)

```
Request → index.php → Middleware Pipeline → Router → Controller → Service → Model → Database
                                     ↓           ↓          ↓
                              Auth/Role check  Validate   Business Logic
                              CSRF check       Params     Transform Data
                              CORS/Rate Limit  Extract    Log Activity
```

**Layer Responsibilities:**

| Layer | Responsibility | Example |
|-------|---------------|---------|
| **index.php** | Bootstrap, DI container, middleware setup | Initialize PDO, models, services, controllers |
| **Middleware** | Cross-cutting concerns | Auth, CSRF, CORS, rate limiting |
| **Router** | URL → handler mapping, auth/role enforcement | `ApiRouter::dispatch()` |
| **Controller** | Request parsing, response formatting | Extract params, call service, return JSON |
| **Service** | Business logic, validation, logging | `PageService::create()`, `AuthService::login()` |
| **Model** | Data access, SQL queries | `BaseModel::findById()`, `User::findByUsername()` |
| **Helpers** | Utility functions | `Response::success()`, `Security::hashPassword()` |

### Frontend Architecture (React SPA)

```
BrowserRouter
  └── ToastProvider
      └── AuthProvider
          └── AppRoutes
              ├── PublicLayout (no auth)
              │   └── <Outlet /> → Public Pages
              ├── Login Page (no auth)
              ├── AdminRoute (auth + role check)
              │   └── AdminLayout
              │       └── <Outlet /> → Admin Pages
              └── NotFound / AccessDenied
```

## 3. Backend Architecture

### 3.1 Request Lifecycle

1. **HTTP Request** arrives at `public/index.php`
2. **Static file check** — if `/uploads/*`, serve file directly with ETag caching
3. **Gzip buffering** starts for API responses
4. **CORS middleware** handles cross-origin and preflight requests
5. **Session starts** with secure cookie settings
6. **CSRF middleware** validates tokens on mutating requests
7. **Router dispatches** URL to matching route handler
8. **Auth middleware** validates session (for protected routes)
9. **Role middleware** checks permissions (for role-restricted routes)
10. **Controller** processes request
11. **Response** sent as JSON (gzip compressed)

### 3.2 Dependency Injection

Manual DI in `index.php`:
```php
// Models (depend on PDO)
$userModel = new User($pdo);

// Services (depend on Models)
$authService = new AuthService($userModel, $activityLogModel);

// Controllers (depend on Services)
$authController = new AuthController($authService);

// Router (receives Controllers via setController)
$router->setController('auth', $authController);
```

### 3.3 Database Access Pattern

All models extend `BaseModel` which provides:
- `findById(int $id)` — Find single record by primary key
- `findAll(int $page, int $perPage)` — Paginated listing
- `create(array $data)` — Insert with fillable filtering, returns created record
- `update(int $id, array $data)` — Update with fillable filtering
- `delete(int $id)` — Hard delete
- `softDelete(int $id)` — Set `deleted_at` timestamp
- `findByField(string $field, mixed $value)` — Find by column value
- `count(array $conditions)` — Count with optional conditions

## 4. Coding Standards

### 4.1 PHP Standards
- **PSR-4 Autoloading**: `App\` namespace maps to `backend/`
- **Type Declarations**: Use PHP 8.4 typed properties and parameters
- **Return Types**: Always declare return types
- **Controller Methods**: Must use `array $params` for router-passed parameters
- **Response Format**: Always use `Response` helper methods (never `echo json_encode` directly)
- **Error Handling**: Use try-catch in services, `Response::error()` for API errors

### 4.2 React Standards
- **Functional Components**: Use function declarations, not classes
- **Hooks**: Use built-in and custom hooks for state and side effects
- **Lazy Loading**: Use `React.lazy()` for route-level code splitting
- **Barrel Exports**: Use `index.js` for component directories
- **API Calls**: Always go through service modules (`src/services/`)
- **Error Handling**: Display user-friendly errors via toast notifications

### 4.3 Database Standards
- **Migrations**: Sequential numbering (001, 002, etc.)
- **Naming**: snake_case for tables and columns
- **Timestamps**: Always include `created_at` and `updated_at`
- **Soft Deletes**: Use `deleted_at` column where applicable
- **Foreign Keys**: Always define with appropriate ON DELETE action

## 5. Extension Guidelines

### 5.1 Adding a New API Endpoint

1. **Create migration** (if new table):
   ```sql
   -- backend/database/migrations/013_create_resources_table.sql
   CREATE TABLE resources (
       id BIGSERIAL PRIMARY KEY,
       title VARCHAR(255) NOT NULL,
       created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
       updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
   );
   ```

2. **Create Model** (`backend/models/Resource.php`):
   ```php
   class Resource extends BaseModel {
       protected string $table = 'resources';
       protected array $fillable = ['title'];
   }
   ```

3. **Create Service** (`backend/services/ResourceService.php`):
   ```php
   class ResourceService {
       public function __construct(private Resource $model, private ActivityLog $log) {}
       public function getAll(int $page, int $perPage): array { ... }
   }
   ```

4. **Create Controller** (`backend/controllers/ResourceController.php`):
   ```php
   class ResourceController {
       public function index(): void { ... }
       public function store(): void { ... }
   }
   ```

5. **Register Routes** in `ApiRouter::loadRoutes()`:
   ```php
   // Public
   $this->addRoute('GET', '/resources', fn() => $c['resource']->index());
   // Admin
   $this->addRoute('POST', '/resources', fn() => $c['resource']->store(), true, ['super_admin', 'admin']);
   ```

6. **Wire DI** in `public/index.php`:
   ```php
   $resourceModel = new Resource($pdo);
   $resourceService = new ResourceService($resourceModel, $activityLogModel);
   $resourceController = new ResourceController($resourceService);
   $router->setController('resource', $resourceController);
   ```

### 5.2 Adding a New Frontend Page

1. **Create page component** (`frontend/src/pages/admin/ResourceAdmin.jsx`)
2. **Add lazy import** in `AppRoutes.jsx`:
   ```jsx
   const ResourceAdmin = lazy(() => import('../pages/admin/ResourceAdmin'));
   ```
3. **Add route** under the appropriate layout:
   ```jsx
   <Route path="resources" element={<LazyRoute><ResourceAdmin /></LazyRoute>} />
   ```
4. **Create service** (`frontend/src/services/resourceService.js`)
5. **Add API endpoints** to `constants/apiEndpoints.js`

## 6. Deployment Procedures

### 6.1 Staging Deployment
```bash
# Pull latest code
git pull origin staging

# Backend
cd backend
composer install --no-dev

# Frontend
cd ../frontend
npm ci
npm run build

# Database
cd ../backend
php database/migrate.php

# Clear caches
rm -rf frontend/dist
npm run build
```

### 6.2 Production Deployment
```bash
# Same as staging, plus:
# 1. Update .env for production
# 2. Enable OPcache
# 3. Configure web server (Nginx/Apache)
# 4. Set up SSL
# 5. Test all endpoints
curl -f http://localhost:8000/api/v1/auth/csrf-token || exit 1
```

### 6.3 Rollback Procedure
```bash
git checkout <previous-stable-tag>
cd backend && composer install --no-dev
cd ../frontend && npm ci && npm run build
# Run any reverse migrations if needed
```
