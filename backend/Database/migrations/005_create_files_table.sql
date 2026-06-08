-- Migration: 005_create_files_table
-- Created: 2025-01-01
-- Description: Create files table for uploaded file management

CREATE TYPE file_category AS ENUM ('image', 'document', 'other');

CREATE TABLE files (
    id BIGSERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type file_category NOT NULL DEFAULT 'image',
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT NOT NULL,
    alt_text VARCHAR(255) DEFAULT '',
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id BIGINT DEFAULT NULL,
    uploaded_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_files_uploaded_by ON files(uploaded_by);
CREATE INDEX idx_files_entity ON files(entity_type, entity_id);
CREATE INDEX idx_files_type ON files(file_type);
CREATE INDEX idx_files_created_at ON files(created_at);

-- Comments
COMMENT ON TABLE files IS 'Uploaded files (images, documents)';
COMMENT ON COLUMN files.entity_type IS 'Associated entity type (page, news, event)';
COMMENT ON COLUMN files.entity_id IS 'Associated entity ID';
