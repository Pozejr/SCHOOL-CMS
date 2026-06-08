# School CMS — Maintenance Guide

## 1. Backup Procedures

### 1.1 Database Backup

#### Manual Backup (pg_dump)
```bash
# Full database backup
pg_dump -U postgres -F c -f ~/backups/school_cms_$(date +%Y%m%d_%H%M%S).backup school_cms

# SQL format backup (human-readable)
pg_dump -U postgres -f ~/backups/school_cms_$(date +%Y%m%d_%H%M%S).sql school_cms

# Schema only
pg_dump -U postgres --schema-only -f ~/backups/schema_$(date +%Y%m%d).sql school_cms

# Data only
pg_dump -U postgres --data-only -f ~/backups/data_$(date +%Y%m%d).sql school_cms
```

#### Automated Backup Script
```bash
#!/bin/bash
# save as: /usr/local/bin/backup_school_cms.sh

BACKUP_DIR="/var/backups/school-cms"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
KEEP_DAYS=30

mkdir -p $BACKUP_DIR

# Database backup
pg_dump -U postgres -F c -f "${BACKUP_DIR}/db_${TIMESTAMP}.backup" school_cms

# Uploads backup
tar czf "${BACKUP_DIR}/uploads_${TIMESTAMP}.tar.gz" -C /var/www/school-cms/backend uploads/

# Remove backups older than KEEP_DAYS
find $BACKUP_DIR -name "*.backup" -mtime +$KEEP_DAYS -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +$KEEP_DAYS -delete

echo "Backup completed: ${TIMESTAMP}"
```

#### Cron Job (Daily at 2 AM)
```cron
0 2 * * * /usr/local/bin/backup_school_cms.sh >> /var/log/school-cms-backup.log 2>&1
```

### 1.2 File Backups

| Directory | Contents | Frequency |
|-----------|----------|-----------|
| `backend/uploads/` | User-uploaded images and documents | Daily |
| `backend/.env` | Environment configuration | On change |
| `backend/logs/` | Application logs | Weekly |
| `frontend/dist/` | Built frontend assets | On deploy |

### 1.3 Backup Verification
```bash
# Verify database backup
pg_restore --list ~/backups/db_20250115_020000.backup | head

# Test restore to a temporary database
createdb -U postgres school_cms_test
pg_restore -U postgres -d school_cms_test ~/backups/db_20250115_020000.backup
dropdb -U postgres school_cms_test
```

## 2. Restore Procedures

### 2.1 Database Restore

#### From Custom Format Backup
```bash
# Stop the application first
# systemctl stop school-cms  (or kill PHP process)

# Restore database
dropdb -U postgres school_cms        # ⚠️ DESTROYS existing data
createdb -U postgres school_cms
pg_restore -U postgres -d school_cms ~/backups/db_20250115_020000.backup

# Restart the application
```

#### From SQL Format Backup
```bash
dropdb -U postgres school_cms
createdb -U postgres school_cms
psql -U postgres school_cms < ~/backups/school_cms_20250115.sql
```

### 2.2 File Restore
```bash
# Restore uploads
cd /var/www/school-cms/backend
tar xzf ~/backups/uploads_20250115.tar.gz
chmod -R 755 uploads/
```

## 3. Updating Dependencies

### 3.1 PHP Dependencies (Composer)
```bash
cd backend

# Check for outdated packages
composer outdated

# Update all packages (with safety)
composer update --with-all-dependencies

# Update specific package
composer update psr/log

# After updating, test the application
php -S localhost:8000 -t public
# Visit http://localhost:5173 and test key features
```

### 3.2 Frontend Dependencies (npm)
```bash
cd frontend

# Check for outdated packages
npm outdated

# Update minor/patch versions (safe)
npm update

# Update specific package
npm install axios@latest

# Update all packages including major versions (review carefully)
npx npm-check-updates -u
npm install

# Rebuild frontend
npm run build

# Test the application
npm run dev
# Visit http://localhost:5173 and test all pages
```

### 3.3 System Dependencies
```bash
# Check PHP version
php -v

# Check PostgreSQL version
psql --version

# Update system packages (Ubuntu/Debian)
sudo apt update && sudo apt upgrade

# Update PHP
sudo apt install php8.4

# Update PostgreSQL
sudo apt install postgresql-17
```

## 4. Monitoring

### 4.1 Application Health Checks

#### API Health Check
```bash
# Basic API check
curl -f http://localhost:8000/api/v1/auth/csrf-token || echo "API DOWN"

# Expected: {"success":true,"data":{"csrf_token":"..."}}
```

#### Database Health Check
```bash
# Check PostgreSQL is running
pg_isready -h 127.0.0.1 -p 5432

# Check connection count
psql -U postgres -c "SELECT count(*) FROM pg_stat_activity;" school_cms

# Check database size
psql -U postgres -c "SELECT pg_size_pretty(pg_database_size('school_cms'));" 
```

#### Disk Space
```bash
# Check disk usage
df -h

# Check upload directory size
du -sh /var/www/school-cms/backend/uploads/

# Check log directory size
du -sh /var/www/school-cms/backend/logs/
```

### 4.2 Log Monitoring

#### Application Logs
```bash
# View recent logs
tail -f /var/www/school-cms/backend/logs/app.log

# Search for errors
grep -i "error\|exception\|critical" /var/www/school-cms/backend/logs/app.log

# Count errors today
grep -c "ERROR" /var/www/school-cms/backend/logs/app.log
```

#### PHP Error Logs
```bash
# PHP errors
tail -f /var/log/php_errors.log

# Or check PHP error log location
php -i | grep error_log
```

#### PostgreSQL Logs
```bash
# PostgreSQL logs
tail -f /var/log/postgresql/postgresql-17-main.log

# Slow queries
grep "duration" /var/log/postgresql/postgresql-17-main.log
```

### 4.3 Performance Monitoring

#### OPcache Status
```bash
# Check OPcache configuration
php -i | grep opcache

# Monitor OPcache hit rate (requires opcache-gui or similar)
```

#### Database Performance
```sql
-- Active queries
SELECT * FROM pg_stat_activity WHERE state = 'active';

-- Slow queries (requires pg_stat_statements extension)
SELECT query, calls, total_time, mean_time 
FROM pg_stat_statements 
ORDER BY mean_time DESC LIMIT 10;

-- Table sizes
SELECT relname, pg_size_pretty(pg_total_relation_size(relid)) 
FROM pg_catalog.pg_statio_user_tables 
ORDER BY pg_total_relation_size(relid) DESC;
```

## 5. Troubleshooting

### 5.1 Common Issues

#### Blank White Page (Frontend)
```bash
# Check if build exists
ls -la frontend/dist/

# Rebuild
cd frontend && npm run build

# Check browser console for JavaScript errors
```

#### 500 Internal Server Error (API)
```bash
# Check PHP error log
tail -100 backend/logs/app.log

# Check database connection
psql -U postgres -c "SELECT 1;" school_cms

# Verify .env configuration
cat backend/.env | grep DB_
```

#### 401 Unauthorized Errors
```bash
# Check session configuration
cat backend/.env | grep SESSION

# Verify session save path is writable
php -i | grep session.save_path

# Clear browser cookies and re-login
```

#### 403 Forbidden Errors
```bash
# Check user role in database
psql -U postgres -c "SELECT id, username, role, status FROM users;" school_cms

# Verify role middleware is correctly configured
grep -A5 "roles" backend/routes/ApiRouter.php
```

#### Upload Failures
```bash
# Check upload directory permissions
ls -la backend/uploads/

# Fix permissions
chmod -R 755 backend/uploads/
chown -R www-data:www-data backend/uploads/

# Check upload size limits
cat backend/.env | grep UPLOAD
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

#### Slow Page Loads
```bash
# Check gzip is working
curl -H "Accept-Encoding: gzip" -I http://localhost:8000/api/v1/pages
# Should show: Content-Encoding: gzip

# Check OPcache is enabled
php -i | grep "opcache.enable"

# Check database query performance
psql -U postgres -c "SELECT * FROM pg_stat_activity WHERE state = 'active';" school_cms

# Run EXPLAIN ANALYZE on slow queries
psql -U postgres -c "EXPLAIN ANALYZE SELECT * FROM pages WHERE status='published' AND deleted_at IS NULL;" school_cms
```

### 5.2 Emergency Procedures

#### Clear All Sessions (Force Logout)
```bash
# Find PHP session save path
php -i | grep session.save_path

# Delete all sessions
rm -f /tmp/sess_*
# Or
rm -f /var/lib/php/sessions/*

# Restart PHP-FPM (if using)
sudo systemctl restart php8.4-fpm
```

#### Reset Admin Password
```php
// Run this PHP script to reset the superadmin password
php -r "
require 'vendor/autoload.php';
\$pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=school_cms', 'postgres', 'postgres');
\$hash = password_hash('NewPassword123', PASSWORD_BCRYPT, ['cost' => 12]);
\$stmt = \$pdo->prepare('UPDATE users SET password = ?, login_attempts = 0, locked_until = NULL WHERE username = ?');
\$stmt->execute([\$hash, 'superadmin']);
echo 'Password updated for superadmin\n';
"
```

#### Lock Down System (Maintenance Mode)
```sql
-- Enable maintenance mode
UPDATE settings SET value = 'true' WHERE key_name = 'maintenance_mode';
```

## 6. Performance Optimization Recommendations

### 6.1 Current Optimizations (Already Implemented)
- **Code Splitting**: React.lazy + Suspense (311KB → 236KB initial bundle, −24%)
- **Gzip Compression**: ob_gzhandler for API responses
- **Static File Caching**: ETag + 304 Not Modified
- **PDO Persistent Connections**: Reused across requests
- **OPcache**: Pre-configured settings
- **Database Indexes**: 9 compound indexes for common queries
- **Route Compilation**: Regex pre-compiled once, sorted once
- **Content Caching**: MD5-keyed parse cache in ContentParser
- **Dashboard Queries**: Consolidated from 9 to 4 using FILTER clauses

### 6.2 Future Optimization Opportunities

| Area | Recommendation | Impact |
|------|---------------|--------|
| **CDN** | Serve static assets via CDN (CloudFlare, AWS CloudFront) | High — reduces latency globally |
| **Redis** | Replace session-based caching with Redis | Medium — better for multi-server |
| **HTTP/2** | Enable HTTP/2 on web server | Medium — parallel loading |
| **Image Optimization** | Auto-generate WebP versions on upload | Medium — smaller images |
| **Database Connection Pooling** | Use PgBouncer for connection pooling | Medium — under high load |
| **Full-Page Cache** | Cache rendered public pages | High — eliminates API calls |
| **Service Worker** | Offline support for public pages | Low — improved UX |
| **Lazy Loading Images** | IntersectionObserver for images below fold | Low — faster perceived load |
