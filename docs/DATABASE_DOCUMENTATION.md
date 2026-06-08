# School CMS — Database Documentation

## 1. Database Overview

- **Database Engine**: PostgreSQL 17
- **Database Name**: `school_cms`
- **Character Set**: UTF-8
- **Connection**: PDO with persistent connections

## 2. Entity Relationship Diagram

```
┌─────────────┐       ┌──────────────────┐       ┌─────────────────┐
│    users     │       │      pages       │       │  page_sections  │
├─────────────┤       ├──────────────────┤       ├─────────────────┤
│ id (PK)     │──┐    │ id (PK)          │───┐   │ id (PK)         │
│ username    │  │    │ title            │   │   │ page_id (FK)    │
│ email       │  │    │ slug             │   │   │ section_type    │
│ password    │  │    │ content          │   │   │ title           │
│ role        │  │    │ excerpt          │   │   │ content         │
│ status      │  │    │ status           │   │   │ image_url       │
│ first_name  │  │    │ meta_title       │   │   │ layout          │
│ last_name   │  │    │ meta_description │   │   │ display_order   │
│ avatar      │  │    │ featured_image   │   │   └─────────────────┘
│ last_login  │  │    │ sort_order       │   │
│ ...         │  │    │ template         │   │   ┌─────────────────┐
└─────────────┘  │    │ created_by (FK) ─│───┘   │ page_templates  │
                 │    │ updated_by (FK) ─│───┐   ├─────────────────┤
                 │    │ deleted_at       │   │   │ id (PK)         │
                 │    └──────────────────┘   │   │ name            │
                 │                           │   │ slug            │
                 │    ┌──────────────────┐   │   │ description     │
                 │    │      news        │   │   │ sections (JSONB)│
                 │    ├──────────────────┤   │   └─────────────────┘
                 ├───>│ id (PK)          │   │
                 │    │ title            │   │   ┌─────────────────┐
                 │    │ slug             │   │   │  activity_logs  │
                 │    │ content          │   │   ├─────────────────┤
                 │    │ excerpt          │   │   │ id (PK)         │
                 │    │ featured_image   │   │   │ user_id (FK)    │
                 │    │ status           │   │   │ action          │
                 │    │ published_at     │   │   │ entity_type     │
                 │    │ created_by (FK) ─│───┘   │ entity_id       │
                 │    │ updated_by (FK) ─│───┐   │ description     │
                 │    │ deleted_at       │   │   │ ip_address      │
                 │    └──────────────────┘   │   │ user_agent      │
                 │                           │   └─────────────────┘
                 │    ┌──────────────────┐   │
                 │    │     events       │   │   ┌─────────────────┐
                 │    ├──────────────────┤   │   │  password_resets│
                 ├───>│ id (PK)          │   │   ├─────────────────┤
                 │    │ title            │   │   │ id (PK)         │
                 │    │ slug             │   │   │ user_id (FK)    │
                 │    │ description      │   │   │ token           │
                 │    │ venue            │   │   │ expires_at      │
                 │    │ event_date       │   │   │ used_at         │
                 │    │ end_date         │   │   └─────────────────┘
                 │    │ featured_image   │   │
                 │    │ status           │   │   ┌─────────────────┐
                 │    │ created_by (FK) ─│───┘   │    settings     │
                 │    │ updated_by (FK) ─│       ├─────────────────┤
                 │    │ deleted_at       │       │ id (PK)         │
                 │    └──────────────────┘       │ key_name        │
                 │                                │ value           │
                 │    ┌──────────────────┐       │ type            │
                 │    │      files       │       │ group_name      │
                 │    ├──────────────────┤       └─────────────────┘
                 └───>│ id (PK)          │
                      │ filename         │
                      │ original_name    │
                      │ file_path        │
                      │ file_type        │
                      │ mime_type        │
                      │ file_size        │
                      │ entity_type      │
                      │ entity_id        │
                      │ uploaded_by (FK) │
                      └──────────────────┘
```

## 3. Tables

### 3.1 users
Stores CMS user accounts.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| username | VARCHAR(100) | No | — | Unique username |
| email | VARCHAR(255) | No | — | Unique email address |
| password | VARCHAR(255) | No | — | Bcrypt hashed password (cost 12) |
| role | user_role | No | 'editor' | Enum: super_admin, admin, editor |
| status | user_status | No | 'active' | Enum: active, inactive, locked |
| first_name | VARCHAR(100) | No | '' | First name |
| last_name | VARCHAR(100) | No | '' | Last name |
| avatar | VARCHAR(500) | Yes | NULL | Avatar image URL |
| last_login | TIMESTAMPTZ | Yes | NULL | Last successful login |
| login_attempts | INTEGER | No | 0 | Consecutive failed attempts |
| locked_until | TIMESTAMPTZ | Yes | NULL | Account lock expiry |
| created_at | TIMESTAMPTZ | No | NOW() | Record creation time |
| updated_at | TIMESTAMPTZ | No | NOW() | Last update time |

**Custom Types:**
```sql
CREATE TYPE user_role AS ENUM ('super_admin', 'admin', 'editor');
CREATE TYPE user_status AS ENUM ('active', 'inactive', 'locked');
```

**Indexes:**
- `idx_users_username` ON (username)
- `idx_users_email` ON (email)
- `idx_users_role` ON (role)
- `idx_users_status` ON (status)

**Constraints:**
- `UNIQUE (username)`
- `UNIQUE (email)`

---

### 3.2 pages
Stores website pages.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| title | VARCHAR(255) | No | — | Page title |
| slug | VARCHAR(255) | No | — | URL-friendly identifier (unique) |
| content | TEXT | No | '' | Page body content |
| excerpt | TEXT | Yes | '' | Short summary |
| status | page_status | No | 'draft' | Enum: published, draft |
| meta_title | VARCHAR(255) | Yes | '' | SEO title |
| meta_description | VARCHAR(500) | Yes | '' | SEO description |
| featured_image | VARCHAR(500) | Yes | NULL | Featured image path |
| sort_order | INTEGER | No | 0 | Display order |
| template | VARCHAR(100) | Yes | 'default' | Page template name |
| created_by | BIGINT | No | — | FK → users.id |
| updated_by | BIGINT | No | — | FK → users.id |
| created_at | TIMESTAMPTZ | No | NOW() | Creation time |
| updated_at | TIMESTAMPTZ | No | NOW() | Last update |
| deleted_at | TIMESTAMPTZ | Yes | NULL | Soft delete timestamp |

**Custom Types:**
```sql
CREATE TYPE page_status AS ENUM ('published', 'draft');
```

**Indexes:**
- `idx_pages_slug` ON (slug)
- `idx_pages_status` ON (status)
- `idx_pages_created_by` ON (created_by)
- `idx_pages_sort` ON (sort_order)
- `idx_pages_deleted_at` ON (deleted_at)
- `idx_pages_status_deleted` ON (status, deleted_at) — compound
- `idx_pages_slug_deleted` ON (slug, deleted_at) WHERE deleted_at IS NULL — partial compound

---

### 3.3 page_sections
Stores page builder sections.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| page_id | BIGINT | No | — | FK → pages.id (ON DELETE CASCADE) |
| section_type | VARCHAR(50) | No | 'text' | text, gallery, video, quote, contact |
| title | VARCHAR(255) | No | '' | Section title |
| content | TEXT | Yes | '' | Section content |
| image_url | VARCHAR(500) | Yes | NULL | Image URL |
| image_caption | VARCHAR(255) | Yes | '' | Image caption |
| video_url | VARCHAR(500) | Yes | NULL | Video URL |
| gallery_urls | TEXT | Yes | NULL | JSON array of image URLs |
| pdf_url | VARCHAR(500) | Yes | NULL | PDF document URL |
| display_order | INTEGER | No | 0 | Sort order within page |
| layout | VARCHAR(50) | No | 'full' | full, left, right, centered |
| created_at | TIMESTAMPTZ | No | NOW() | Creation time |
| updated_at | TIMESTAMPTZ | No | NOW() | Last update |

**Indexes:**
- `idx_page_sections_page_id` ON (page_id)
- `idx_page_sections_order` ON (page_id, display_order)

---

### 3.4 page_templates
Stores reusable page templates.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| name | VARCHAR(100) | No | — | Template name |
| slug | VARCHAR(100) | No | — | URL-friendly identifier (unique) |
| description | TEXT | Yes | '' | Template description |
| sections | JSONB | No | '[]' | Array of section definitions |
| created_at | TIMESTAMPTZ | No | NOW() | Creation time |

**Constraints:**
- `UNIQUE (slug)`

---

### 3.5 news
Stores news articles.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| title | VARCHAR(255) | No | — | Article title |
| slug | VARCHAR(255) | No | — | URL-friendly identifier (unique) |
| content | TEXT | No | '' | Article body |
| excerpt | TEXT | Yes | '' | Short summary |
| featured_image | VARCHAR(500) | Yes | NULL | Featured image path |
| status | news_status | No | 'draft' | Enum: published, draft |
| published_at | TIMESTAMPTZ | Yes | NULL | Publication date |
| created_by | BIGINT | No | — | FK → users.id |
| updated_by | BIGINT | No | — | FK → users.id |
| created_at | TIMESTAMPTZ | No | NOW() | Creation time |
| updated_at | TIMESTAMPTZ | No | NOW() | Last update |
| deleted_at | TIMESTAMPTZ | Yes | NULL | Soft delete timestamp |

**Indexes:**
- `idx_news_slug` ON (slug)
- `idx_news_status` ON (status)
- `idx_news_created_by` ON (created_by)
- `idx_news_published_at` ON (published_at)
- `idx_news_deleted_at` ON (deleted_at)
- `idx_news_status_deleted_published` ON (status, deleted_at, published_at DESC NULLS LAST)
- `idx_news_slug_deleted` ON (slug, deleted_at) WHERE deleted_at IS NULL

---

### 3.6 events
Stores school events.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| title | VARCHAR(255) | No | — | Event title |
| slug | VARCHAR(255) | No | — | URL-friendly identifier (unique) |
| description | TEXT | No | '' | Event description |
| venue | VARCHAR(255) | Yes | '' | Event venue |
| event_date | TIMESTAMPTZ | No | — | Start date/time |
| end_date | TIMESTAMPTZ | Yes | NULL | End date/time |
| featured_image | VARCHAR(500) | Yes | NULL | Featured image path |
| status | event_status | No | 'draft' | Enum: published, draft |
| created_by | BIGINT | No | — | FK → users.id |
| updated_by | BIGINT | No | — | FK → users.id |
| created_at | TIMESTAMPTZ | No | NOW() | Creation time |
| updated_at | TIMESTAMPTZ | No | NOW() | Last update |
| deleted_at | TIMESTAMPTZ | Yes | NULL | Soft delete timestamp |

**Indexes:**
- `idx_events_slug` ON (slug)
- `idx_events_status` ON (status)
- `idx_events_created_by` ON (created_by)
- `idx_events_event_date` ON (event_date)
- `idx_events_deleted_at` ON (deleted_at)
- `idx_events_status_deleted_date` ON (status, deleted_at, event_date ASC)
- `idx_events_upcoming` ON (event_date ASC) WHERE status='published' AND deleted_at IS NULL AND event_date >= NOW()
- `idx_events_slug_deleted` ON (slug, deleted_at) WHERE deleted_at IS NULL

---

### 3.7 files
Stores uploaded file metadata.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| filename | VARCHAR(255) | No | — | Stored filename |
| original_name | VARCHAR(255) | No | — | Original upload name |
| file_path | VARCHAR(500) | No | — | Full file path |
| file_type | file_category | No | 'image' | Enum: image, document, other |
| mime_type | VARCHAR(100) | No | — | MIME type |
| file_size | BIGINT | No | — | Size in bytes |
| alt_text | VARCHAR(255) | Yes | '' | Image alt text |
| entity_type | VARCHAR(50) | Yes | NULL | Associated entity type |
| entity_id | BIGINT | Yes | NULL | Associated entity ID |
| uploaded_by | BIGINT | No | — | FK → users.id |
| created_at | TIMESTAMPTZ | No | NOW() | Upload time |

**Indexes:**
- `idx_files_uploaded_by` ON (uploaded_by)
- `idx_files_entity` ON (entity_type, entity_id)
- `idx_files_type` ON (file_type)
- `idx_files_created_at` ON (created_at)
- `idx_files_uploaded_by_created` ON (uploaded_by, created_at DESC)

---

### 3.8 activity_logs
Audit trail for all CMS actions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| user_id | BIGINT | Yes | NULL | FK → users.id (ON DELETE SET NULL) |
| action | VARCHAR(100) | No | — | Action type (login, create, update, delete) |
| entity_type | VARCHAR(50) | No | — | Entity type (user, page, news, event, file) |
| entity_id | BIGINT | Yes | NULL | Entity ID |
| description | TEXT | Yes | '' | Human-readable description |
| ip_address | INET | Yes | NULL | Client IP address |
| user_agent | TEXT | Yes | '' | Client user agent |
| created_at | TIMESTAMPTZ | No | NOW() | Log time |

**Indexes:**
- `idx_activity_logs_user_id` ON (user_id)
- `idx_activity_logs_action` ON (action)
- `idx_activity_logs_entity` ON (entity_type, entity_id)
- `idx_activity_logs_created_at` ON (created_at)
- `idx_activity_logs_recent` ON (created_at DESC)

---

### 3.9 password_resets
Password reset tokens.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| user_id | BIGINT | No | — | FK → users.id (ON DELETE CASCADE) |
| token | VARCHAR(255) | No | — | Reset token (unique) |
| expires_at | TIMESTAMPTZ | No | — | Token expiry time |
| used_at | TIMESTAMPTZ | Yes | NULL | When token was used |
| created_at | TIMESTAMPTZ | No | NOW() | Creation time |

---

### 3.10 settings
Site-wide configuration.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGSERIAL | No | AUTO | Primary key |
| key_name | VARCHAR(100) | No | — | Setting key (unique) |
| value | TEXT | Yes | '' | Setting value |
| type | VARCHAR(50) | No | 'string' | Value type: string, integer, boolean, json |
| group_name | VARCHAR(50) | No | 'general' | Setting group |
| updated_at | TIMESTAMPTZ | No | NOW() | Last update |

## 4. Relationships Summary

| From | To | Type | Foreign Key | On Delete |
|------|----|------|-------------|-----------|
| pages | users | Many-to-One | created_by → users.id | RESTRICT |
| pages | users | Many-to-One | updated_by → users.id | RESTRICT |
| page_sections | pages | Many-to-One | page_id → pages.id | CASCADE |
| news | users | Many-to-One | created_by → users.id | RESTRICT |
| news | users | Many-to-One | updated_by → users.id | RESTRICT |
| events | users | Many-to-One | created_by → users.id | RESTRICT |
| events | users | Many-to-One | updated_by → users.id | RESTRICT |
| files | users | Many-to-One | uploaded_by → users.id | RESTRICT |
| activity_logs | users | Many-to-One | user_id → users.id | SET NULL |
| password_resets | users | Many-to-One | user_id → users.id | CASCADE |
