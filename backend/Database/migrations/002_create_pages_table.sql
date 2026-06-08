-- Migration: 002_create_pages_table
-- Created: 2025-01-01
-- Description: Create pages table for website page management

CREATE TYPE page_status AS ENUM ('published', 'draft');

CREATE TABLE pages (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL DEFAULT '',
    excerpt TEXT DEFAULT '',
    status page_status NOT NULL DEFAULT 'draft',
    meta_title VARCHAR(255) DEFAULT '',
    meta_description VARCHAR(500) DEFAULT '',
    featured_image VARCHAR(500) DEFAULT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    template VARCHAR(100) DEFAULT 'default',
    created_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    updated_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMP WITH TIME ZONE DEFAULT NULL
);

-- Indexes
CREATE INDEX idx_pages_slug ON pages(slug);
CREATE INDEX idx_pages_status ON pages(status);
CREATE INDEX idx_pages_created_by ON pages(created_by);
CREATE INDEX idx_pages_sort ON pages(sort_order);
CREATE INDEX idx_pages_deleted_at ON pages(deleted_at);

-- Comments
COMMENT ON TABLE pages IS 'Website pages managed through CMS';
COMMENT ON COLUMN pages.slug IS 'URL-friendly page identifier';
COMMENT ON COLUMN pages.deleted_at IS 'Soft delete timestamp, NULL if not deleted';
