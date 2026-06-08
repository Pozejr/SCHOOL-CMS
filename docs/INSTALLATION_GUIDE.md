# School CMS — Installation Guide

## 1. Prerequisites

### System Requirements
- **Operating System**: Windows (XAMPP/WAMP), macOS, or Linux
- **PHP**: 8.4 or higher
- **PostgreSQL**: 17 or higher
- **Node.js**: 18.x or higher
- **npm**: 9.x or higher
- **Composer**: 2.x

### PHP Extensions Required
```
- php-pgsql      (PostgreSQL driver)
- php-pdo        (PDO extension)
- php-json       (JSON support)
- php-mbstring   (Multibyte string)
- php-zip        (File upload handling)
- php-gd         (Image processing)
- php-zlib       (Gzip compression)
- php-openssl    (Secure connections)
- php-fileinfo   (MIME type detection)
```

### Verify Prerequisites
```bash
php -v          # Should show PHP 8.4.x
psql --version  # Should show PostgreSQL 17.x
node -v         # Should show v18.x+
npm -v          # Should show 9.x+
composer -V     # Should show Composer 2.x
```

## 2. Installation Steps

### Step 1: Clone or Extract the Project
```bash
# If using Git
git clone <repository-url> school-cms
cd school-cms

# If using ZIP archive
# Extract to your desired directory (e.g., C:\Users\user\Downloads\school-cms)
```

### Step 2: Install Backend Dependencies
```bash
cd backend
composer install
```

### Step 3: Install Frontend Dependencies
```bash
cd frontend
npm install
```

### Step 4: Configure Environment Variables

Copy the `.env` file in `backend/` and update values:

```bash
cd backend
cp .env.example .env   # Or edit the existing .env
```

**Key Environment Variables** (`backend/.env`):
```env
# Application
APP_NAME="School CMS"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=school_cms
DB_USER=postgres
DB_PASSWORD=postgres

# Session
SESSION_LIFETIME=7200
SESSION_NAME=school_cms_session

# Upload
UPLOAD_MAX_SIZE=10485760
UPLOAD_IMAGE_MAX_SIZE=5242880
UPLOAD_PATH=uploads
ALLOWED_IMAGE_TYPES=image/jpeg,image/png,image/gif,image/webp
ALLOWED_DOCUMENT_TYPES=application/pdf

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:5173
```

**Frontend Environment** (`frontend/.env`):
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

### Step 5: Create the Database

```bash
# Connect to PostgreSQL
psql -U postgres

# Create database
CREATE DATABASE school_cms;

# Exit
\q
```

### Step 6: Run Database Migrations

```bash
cd backend/database
php migrate.php
```

This will execute all migration files in order:
- `001_create_users_table.sql`
- `002_create_pages_table.sql`
- `003_create_news_table.sql`
- `004_create_events_table.sql`
- `005_create_files_table.sql`
- `006_create_activity_logs_table.sql`
- `007_create_password_resets_table.sql`
- `008_create_settings_table.sql`
- `009_seed_data.sql`
- `010_create_page_sections_table.sql`
- `011_create_page_templates_table.sql`
- `012_add_performance_indexes.sql`

### Step 7: Create Upload Directory

```bash
cd backend
mkdir -p uploads/images
mkdir -p uploads/documents
chmod -R 755 uploads
```

### Step 8: Create Log Directory

```bash
cd backend
mkdir -p logs
chmod -R 755 logs
```

## 3. Running the Application

### Start Backend (PHP Development Server)
```bash
cd backend
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000/api/v1/`

### Start Frontend (Vite Development Server)
```bash
cd frontend
npm run dev
```

The application will be available at `http://localhost:5173/`

### Default Login Credentials
| Field | Value |
|-------|-------|
| URL | `http://localhost:5173/admin/login` |
| Username | `superadmin` |
| Password | `Admin@123` |

> **⚠️ Important**: Change the default password immediately after first login.

## 4. Build Steps (Production)

### Build Frontend for Production
```bash
cd frontend
npm run build
```

This creates an optimized production build in `frontend/dist/`.

### Configure Production Web Server

#### Nginx Configuration Example
```nginx
server {
    listen 80;
    server_name your-school-domain.com;
    root /var/www/school-cms/frontend/dist;
    index index.html;

    # Frontend routes — serve index.html for SPA routing
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API proxy to PHP backend
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Static uploads proxy
    location /uploads/ {
        proxy_pass http://127.0.0.1:8000;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### Apache Configuration Example
```apache
<VirtualHost *:80>
    ServerName your-school-domain.com
    DocumentRoot /var/www/school-cms/frontend/dist

    <Directory /var/www/school-cms/frontend/dist>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted

        # SPA fallback
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>

    # Proxy API requests
    ProxyPreserveHost On
    ProxyPass /api/ http://127.0.0.1:8000/api/
    ProxyPassReverse /api/ http://127.0.0.1:8000/api/
    ProxyPass /uploads/ http://127.0.0.1:8000/uploads/
    ProxyPassReverse /uploads/ http://127.0.0.1:8000/uploads/
</VirtualHost>
```

## 5. Deployment Steps

### 5.1 Server Preparation
1. Install PHP 8.4, PostgreSQL 17, Nginx/Apache
2. Configure OPcache using `backend/config/opcache.ini`
3. Set up SSL certificate (Let's Encrypt recommended)
4. Configure firewall (allow ports 80, 443 only)

### 5.2 Application Deployment
```bash
# Clone to production directory
git clone <repository> /var/www/school-cms
cd /var/www/school-cms

# Install backend dependencies (no dev)
cd backend
composer install --no-dev --optimize-autoloader

# Install and build frontend
cd ../frontend
npm ci
npm run build

# Set permissions
chown -R www-data:www-data /var/www/school-cms
chmod -R 755 /var/www/school-cms/backend/uploads
chmod -R 755 /var/www/school-cms/backend/logs
```

### 5.3 Production Environment
Update `backend/.env` for production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-school-domain.com
CORS_ALLOWED_ORIGINS=https://your-school-domain.com
```

### 5.4 OPcache Configuration
Copy the OPcache configuration:
```bash
sudo cp backend/config/opcache.ini /etc/php/8.4/cli/conf.d/99-opcache.ini
# Or add to php.ini
```

## 6. Troubleshooting Installation

### Common Issues

| Issue | Solution |
|-------|---------|
| `PDOException: could not find driver` | Install `php-pgsql` extension |
| `Permission denied` on uploads | `chmod -R 755 backend/uploads` |
| `CORS error` in browser | Update `CORS_ALLOWED_ORIGINS` in `.env` |
| `404 Not Found` on API calls | Ensure PHP server uses `-t public` flag |
| `CSRF token mismatch` | Ensure session is started; clear browser cookies |
| `TypeError: array given` in controllers | Ensure all controller methods use `array $params` |
| Blank page on frontend | Check browser console; run `npm run build` again |
| Database connection refused | Verify PostgreSQL is running: `pg_isready` |

### Verify Installation
```bash
# Test API
curl http://localhost:8000/api/v1/auth/csrf-token

# Expected response:
# {"success":true,"data":{"csrf_token":"..."},"message":"Operation successful"}

# Test frontend
curl http://localhost:5173
# Should return HTML
```
