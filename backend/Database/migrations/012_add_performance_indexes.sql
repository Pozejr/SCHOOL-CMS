-- Migration: 012_add_performance_indexes
-- Description: Add compound indexes for common query patterns
-- Impact: Eliminates full table scans on filtered queries

-- Pages: published + not-deleted is the most common public query
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pages_status_deleted
    ON pages(status, deleted_at);

-- Pages: slug + not-deleted for slug lookups (unique constraint already covers slug alone)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pages_slug_deleted
    ON pages(slug, deleted_at) WHERE deleted_at IS NULL;

-- News: published + not-deleted for public news listing
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_news_status_deleted_published
    ON news(status, deleted_at, published_at DESC NULLS LAST);

-- News: slug + not-deleted for slug lookups
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_news_slug_deleted
    ON news(slug, deleted_at) WHERE deleted_at IS NULL;

-- Events: published + not-deleted for public events
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_events_status_deleted_date
    ON events(status, deleted_at, event_date ASC);

-- Events: upcoming events (published + future date)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_events_upcoming
    ON events(event_date ASC) WHERE status = 'published' AND deleted_at IS NULL AND event_date >= NOW();

-- Events: slug + not-deleted for slug lookups
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_events_slug_deleted
    ON events(slug, deleted_at) WHERE deleted_at IS NULL;

-- Activity logs: recent activity ordered by created_at (most common query)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_activity_logs_recent
    ON activity_logs(created_at DESC);

-- Page sections: already has idx_page_sections_order(page_id, display_order) — good

-- Files: uploaded_by + created_at for rate limit check
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_files_uploaded_by_created
    ON files(uploaded_by, created_at DESC);
