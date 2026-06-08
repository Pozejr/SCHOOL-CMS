-- Migration: 003_create_news_table
-- Created: 2025-01-01
-- Description: Create news table for news articles management

CREATE TYPE news_status AS ENUM ('published', 'draft');

CREATE TABLE news (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL DEFAULT '',
    excerpt TEXT DEFAULT '',
    featured_image VARCHAR(500) DEFAULT NULL,
    status news_status NOT NULL DEFAULT 'draft',
    published_at TIMESTAMP WITH TIME ZONE DEFAULT NULL,
    created_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    updated_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMP WITH TIME ZONE DEFAULT NULL
);

-- Indexes
CREATE INDEX idx_news_slug ON news(slug);
CREATE INDEX idx_news_status ON news(status);
CREATE INDEX idx_news_created_by ON news(created_by);
CREATE INDEX idx_news_published_at ON news(published_at);
CREATE INDEX idx_news_deleted_at ON news(deleted_at);

-- Comments
COMMENT ON TABLE news IS 'News articles for school website';
COMMENT ON COLUMN news.slug IS 'URL-friendly article identifier';
COMMENT ON COLUMN news.published_at IS 'Timestamp when article was published';
