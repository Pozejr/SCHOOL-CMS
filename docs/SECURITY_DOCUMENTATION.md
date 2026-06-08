# School CMS — Security Documentation

## 1. Authentication Flow

### 1.1 Login Process
```
Client                          Server
  │                               │
  │  POST /auth/csrf-token        │  ← Initializes session, returns CSRF token
  │──────────────────────────────>│
  │  { csrf_token: "abc..." }     │
  │<──────────────────────────────│
  │                               │
  │  POST /auth/login             │  ← CSRF token NOT required for login
  │  { username, password }       │
  │──────────────────────────────>│
  │                               │
  │  ┌─ SessionManager::start()   │
  │  ├─ Find user by username/email
  │  ├─ Check account status      │
  │  ├─ Check lock status         │
  │  ├─ Verify bcrypt password    │
  │  ├─ Update last_login         │
  │  ├─ SessionManager::regenerate()  ← New session ID
  │  ├─ SessionManager::setUser()     ← Store user in session
  │  ├─ Generate CSRF token       │
  │  └─ Log activity              │
  │                               │
  │  { user, csrf_token }         │
  │<──────────────────────────────│
  │                               │
  │  Subsequent requests include: │
  │  - Session cookie (automatic) │
  │  - X-CSRF-Token header        │
```

### 1.2 Session Management
- **Session Name**: `school_cms_session` (configurable)
- **Session Lifetime**: 7200 seconds (2 hours, configurable)
- **Cookie Security**:
  - `httponly`: true (JavaScript cannot access)
  - `secure`: true on HTTPS
  - `samesite`: Strict (CSRF mitigation)
- **Session Regeneration**: New session ID generated on every login
- **Session Expiry**: Login timestamp checked on every authenticated request

### 1.3 Password Security
- **Algorithm**: bcrypt
- **Cost Factor**: 12
- **Storage**: Hashed only — plaintext passwords are never stored
- **Verification**: `password_verify()` with timing-safe comparison

### 1.4 Account Lockout
- **Trigger**: 5 consecutive failed login attempts
- **Duration**: 30 minutes
- **Reset**: Cleared on successful login
- **Implementation**: `login_attempts` counter + `locked_until` timestamp

## 2. Authorization Model

### 2.1 Role Hierarchy

```
┌──────────────────────────────────────────────────┐
│                  super_admin                      │
│  Full system access                              │
│  • User management (CRUD)                        │
│  • System settings                               │
│  • Security settings                             │
│  • Backup management                             │
│  • Role management                               │
│  • Audit logs                                    │
│  • All CMS features                              │
│  Level: 3                                        │
├──────────────────────────────────────────────────┤
│                     admin                        │
│  CMS management                                  │
│  • Pages (CRUD)                                  │
│  • News (CRUD)                                   │
│  • Events (CRUD)                                 │
│  • Files (upload, delete)                        │
│  • Dashboard                                     │
│  • Templates                                     │
│  • Page sections                                 │
│  Level: 2                                        │
├──────────────────────────────────────────────────┤
│                    editor                         │
│  Limited access                                  │
│  • Can authenticate                              │
│  • Cannot access admin dashboard                 │
│  • Redirected to Access Denied page              │
│  Level: 1                                        │
├──────────────────────────────────────────────────┤
│                    guest                          │
│  Public access only                              │
│  • View public pages                             │
│  • Read news and events                          │
│  • No admin access                               │
│  • Redirected to Login                           │
│  Level: 0                                        │
└──────────────────────────────────────────────────┘
```

### 2.2 Role Permissions Matrix

| Feature | super_admin | admin | editor | guest |
|---------|:-----------:|:-----:|:------:|:-----:|
| **User Management** | ✅ | ❌ | ❌ | ❌ |
| **Dashboard Access** | ✅ | ✅ | ❌ | ❌ |
| **Page CRUD** | ✅ | ✅ | ❌ | ❌ |
| **News CRUD** | ✅ | ✅ | ❌ | ❌ |
| **Events CRUD** | ✅ | ✅ | ❌ | ❌ |
| **File Upload/Delete** | ✅ | ✅ | ❌ | ❌ |
| **Templates** | ✅ | ✅ | ❌ | ❌ |
| **Page Sections** | ✅ | ✅ | ❌ | ❌ |
| **View Public Pages** | ✅ | ✅ | ✅ | ✅ |
| **Read News/Events** | ✅ | ✅ | ✅ | ✅ |
| **Login** | ✅ | ✅ | ✅ | ❌ |
| **Logout** | ✅ | ✅ | ✅ | ❌ |
| **View Own Profile** | ✅ | ✅ | ✅ | ❌ |

## 3. Route Protection

### 3.1 Frontend Route Guards

**AdminRoute Component:**
- Wraps all `/admin/*` routes
- Checks `isAuthenticated` from AuthContext
- Checks user role against allowed roles
- Redirects to `/admin/login` if not authenticated
- Redirects to `/access-denied` if authenticated but unauthorized

**Route Configuration:**
```jsx
// Admin routes — require super_admin or admin
<AdminRoute roles={['super_admin', 'admin']}>
  <AdminLayout />
</AdminRoute>

// User management — require super_admin only
<AdminRoute roles={['super_admin']}>
  <Users />
</AdminRoute>

// Public routes — no guard
<PublicLayout>
  <Route path="/" element={<Home />} />
  ...
</PublicLayout>
```

### 3.2 Backend API Protection

**Authentication Middleware** (`AuthMiddleware::handle()`):
1. Checks `SessionManager::isAuthenticated()` — is `user_id` in session?
2. Checks `SessionManager::isExpired()` — has session exceeded lifetime?
3. If expired: destroys session, returns 401
4. If valid: refreshes `login_time` (rolling session window)

**Role Middleware** (`RoleMiddleware::handle($user, $allowedRoles)`):
1. Gets user role from session
2. Checks if role is in the allowed roles array
3. If not: returns HTTP 403 Forbidden

**Route-Level Configuration** (in `ApiRouter::loadRoutes()`):
```php
// Public endpoint — no auth flag
$this->addRoute('GET', '/news', fn() => $c['news']->index());

// Protected endpoint — auth + role restriction
$this->addRoute('POST', '/news', fn() => $c['news']->store(), true, ['super_admin', 'admin']);

// Super admin only
$this->addRoute('GET', '/users', fn() => $c['user']->index(), true, ['super_admin']);
```

## 4. API Protection

### 4.1 CSRF Protection
- **Mechanism**: Double-submit cookie pattern
- **Token Generation**: `bin2hex(random_bytes(32))` — 64-character hex string
- **Validation**: `hash_equals()` — timing-safe comparison
- **Scope**: All mutating requests (POST, PUT, DELETE, PATCH)
- **Exclusions**: Login endpoint (no session yet), CSRF token endpoint
- **Header**: `X-CSRF-Token`

### 4.2 CORS Protection
- **Allowed Origins**: Configurable via `CORS_ALLOWED_ORIGINS`
- **Default**: `http://localhost:5173` (development)
- **Credentials**: `Access-Control-Allow-Credentials: true`
- **Max Age**: 86400 seconds
- **Preflight**: Returns 204 No Content for OPTIONS requests

### 4.3 Rate Limiting
- **Login**: 5 attempts per 15-minute window
- **API**: 100 requests per 60-second window
- **Storage**: Session-based counter keyed by IP address
- **Response**: HTTP 429 Too Many Requests

### 4.4 Input Validation
- **SQL Injection**: Prevented via PDO prepared statements throughout
- **XSS Prevention**: `htmlspecialchars()` + `strip_tags()` with allowed tags
- **File Upload**: MIME type validation, file size limits, secure filename generation
- **JSON Input**: `json_decode()` with null coalescing for missing fields

## 5. Security Best Practices

### 5.1 Implemented Measures

| Measure | Implementation | Location |
|---------|---------------|----------|
| Password Hashing | bcrypt cost 12 | `Security::hashPassword()` |
| Session Security | httponly, samesite, secure | `SessionManager::start()` |
| CSRF Tokens | 64-char random hex, timing-safe | `CsrfMiddleware` |
| SQL Injection | PDO prepared statements | All models via `BaseModel` |
| XSS Protection | HTML sanitization | `Security::sanitizeHtml()` |
| Security Headers | X-Frame-Options, CSP, etc. | `index.php` |
| Account Lockout | 5 attempts → 30 min lock | `AuthService::login()` |
| Session Regeneration | On login | `SessionManager::regenerate()` |
| File Type Validation | MIME type checking | `Security::validateFileType()` |
| Gzip Compression | ob_gzhandler | `index.php` |
| ETag Caching | 304 Not Modified | `index.php` |

### 5.2 HTTP Security Headers
```
Content-Type: application/json; charset=utf-8
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains (HTTPS only)
```

### 5.3 Audit Logging
All significant actions are logged in the `activity_logs` table:
- User logins and logouts
- Content creation, updates, and deletions
- File uploads
- User management actions

Each log entry captures:
- User ID
- Action type
- Entity type and ID
- Description
- Client IP address
- User agent string

## 6. Security Recommendations

### For Production Deployment:
1. **Change default credentials** immediately
2. **Enable HTTPS** — configure SSL/TLS certificate
3. **Update `APP_DEBUG=false`** in production `.env`
4. **Restrict `CORS_ALLOWED_ORIGINS`** to production domain
5. **Configure OPcache** using provided `opcache.ini`
6. **Set up a WAF** (Web Application Firewall)
7. **Regular security updates** for PHP, PostgreSQL, and dependencies
8. **Enable database SSL connections** in production
9. **Implement Content Security Policy (CSP)** headers
10. **Set up automated backups** for the database
