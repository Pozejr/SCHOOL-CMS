-- Migration: 011_create_page_templates_table
-- Description: Reusable page templates

CREATE TABLE page_templates (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT '',
    sections JSONB NOT NULL DEFAULT '[]',
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE page_templates IS 'Pre-defined page templates for quick page creation';

-- Seed default templates
INSERT INTO page_templates (name, slug, description, sections) VALUES
('About Page', 'about', 'Standard about page with history, vision, mission, and values sections', 
 '[{"title":"Our History","section_type":"text","layout":"full"},{"title":"Our Vision","section_type":"text","layout":"centered"},{"title":"Our Mission","section_type":"text","layout":"centered"},{"title":"Core Values","section_type":"text","layout":"left"},{"title":"Message from the Principal","section_type":"text","layout":"right"}]'::jsonb),

('Admissions Page', 'admissions', 'Admissions information page with process, requirements and fees',
 '[{"title":"Why Choose Us","section_type":"text","layout":"full"},{"title":"Admission Process","section_type":"text","layout":"full"},{"title":"Requirements","section_type":"text","layout":"left"},{"title":"Fees Structure","section_type":"text","layout":"full"},{"title":"Frequently Asked Questions","section_type":"text","layout":"centered"},{"title":"Apply Now","section_type":"text","layout":"centered"}]'::jsonb),

('Academics Page', 'academics', 'Academic programs and curriculum overview',
 '[{"title":"Our Curriculum","section_type":"text","layout":"full"},{"title":"Early Years","section_type":"text","layout":"left"},{"title":"Primary School","section_type":"text","layout":"right"},{"title":"Secondary School","section_type":"text","layout":"left"},{"title":"Extracurricular Activities","section_type":"text","layout":"full"},{"title":"Academic Achievements","section_type":"text","layout":"centered"}]'::jsonb),

('Contact Page', 'contact', 'Contact information and form',
 '[{"title":"Get In Touch","section_type":"text","layout":"centered"},{"title":"Our Location","section_type":"text","layout":"full"},{"title":"Office Hours","section_type":"text","layout":"left"},{"title":"Send Us a Message","section_type":"text","layout":"centered"}]'::jsonb),

('Department Page', 'department', 'Individual department or program page',
 '[{"title":"Department Overview","section_type":"text","layout":"full"},{"title":"Our Programs","section_type":"text","layout":"left"},{"title":"Meet Our Team","section_type":"gallery","layout":"full"},{"title":"Achievements","section_type":"text","layout":"centered"},{"title":"Resources","section_type":"text","layout":"full"}]'::jsonb);
