-- Migration: 008_create_settings_table
-- Created: 2025-01-01
-- Description: Create settings table for site-wide configuration

CREATE TABLE settings (
    id BIGSERIAL PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT DEFAULT '',
    type VARCHAR(50) NOT NULL DEFAULT 'string',
    group_name VARCHAR(50) NOT NULL DEFAULT 'general',
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_settings_key ON settings(key_name);
CREATE INDEX idx_settings_group ON settings(group_name);

-- Comments
COMMENT ON TABLE settings IS 'Site-wide configuration settings';
COMMENT ON COLUMN settings.type IS 'Value type: string, integer, boolean, json';
COMMENT ON COLUMN settings.group_name IS 'Setting group for organization';
