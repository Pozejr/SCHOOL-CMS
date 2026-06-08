-- Migration: 004_create_events_table
-- Created: 2025-01-01
-- Description: Create events table for school events management

CREATE TYPE event_status AS ENUM ('published', 'draft');

CREATE TABLE events (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    venue VARCHAR(255) DEFAULT '',
    event_date TIMESTAMP WITH TIME ZONE NOT NULL,
    end_date TIMESTAMP WITH TIME ZONE DEFAULT NULL,
    featured_image VARCHAR(500) DEFAULT NULL,
    status event_status NOT NULL DEFAULT 'draft',
    created_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    updated_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMP WITH TIME ZONE DEFAULT NULL
);

-- Indexes
CREATE INDEX idx_events_slug ON events(slug);
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_events_created_by ON events(created_by);
CREATE INDEX idx_events_event_date ON events(event_date);
CREATE INDEX idx_events_deleted_at ON events(deleted_at);

-- Comments
COMMENT ON TABLE events IS 'School events managed through CMS';
COMMENT ON COLUMN events.slug IS 'URL-friendly event identifier';
COMMENT ON COLUMN events.event_date IS 'Start date and time of the event';
