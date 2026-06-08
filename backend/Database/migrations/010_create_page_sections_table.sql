-- Migration: 010_create_page_sections_table
-- Description: Section-based page builder

CREATE TABLE page_sections (
    id BIGSERIAL PRIMARY KEY,
    page_id BIGINT NOT NULL REFERENCES pages(id) ON DELETE CASCADE,
    section_type VARCHAR(50) NOT NULL DEFAULT 'text',
    title VARCHAR(255) NOT NULL DEFAULT '',
    content TEXT DEFAULT '',
    image_url VARCHAR(500) DEFAULT NULL,
    image_caption VARCHAR(255) DEFAULT '',
    video_url VARCHAR(500) DEFAULT NULL,
    gallery_urls TEXT DEFAULT NULL,
    pdf_url VARCHAR(500) DEFAULT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    layout VARCHAR(50) NOT NULL DEFAULT 'full',
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_page_sections_page_id ON page_sections(page_id);
CREATE INDEX idx_page_sections_order ON page_sections(page_id, display_order);

COMMENT ON TABLE page_sections IS 'Page sections for section-based page builder';
COMMENT ON COLUMN page_sections.section_type IS 'Section type: text, gallery, video, quote, contact';
COMMENT ON COLUMN page_sections.layout IS 'Layout: full, left, right, centered';
COMMENT ON COLUMN page_sections.gallery_urls IS 'JSON array of image URLs for gallery sections';
