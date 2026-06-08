-- Migration: 009_seed_data
-- Created: 2025-01-01
-- Description: Seed default data including super admin user and default pages

-- Default Super Admin user
-- Password: Admin@123 (bcrypt hash with cost 12)
INSERT INTO users (username, email, password, role, status, first_name, last_name)
VALUES (
    'superadmin',
    'admin@schoolcms.com',
    '$2y$12$30FzQvlInip2g326LNKM7uYbzW.X984eHCw.vd6bFHw88fgcAo52q',
    'super_admin',
    'active',
    'Super',
    'Admin'
);

-- Default pages
INSERT INTO pages (title, slug, content, status, sort_order, created_by, updated_by)
VALUES 
    ('Home', 'home', '<h1>Welcome to Our School</h1><p>A place of excellence in education.</p>', 'published', 1, 1, 1),
    ('About Us', 'about-us', '<h1>About Our School</h1><p>Learn about our history, mission, and values.</p>', 'published', 2, 1, 1),
    ('Academics', 'academics', '<h1>Academics</h1><p>Discover our academic programs and curriculum.</p>', 'published', 3, 1, 1),
    ('Admissions', 'admissions', '<h1>Admissions</h1><p>Information about the admissions process.</p>', 'published', 4, 1, 1),
    ('Contact', 'contact', '<h1>Contact Us</h1><p>Get in touch with our school.</p>', 'published', 5, 1, 1);

-- Default settings
INSERT INTO settings (key_name, value, type, group_name) VALUES
    ('site_name', 'School CMS', 'string', 'general'),
    ('site_tagline', 'Excellence in Education', 'string', 'general'),
    ('site_description', 'A professional school website powered by our custom CMS.', 'string', 'general'),
    ('site_email', 'info@schoolcms.com', 'string', 'general'),
    ('site_phone', '+254 700 000 000', 'string', 'general'),
    ('site_address', 'Nairobi, Kenya', 'string', 'general'),
    ('posts_per_page', '10', 'integer', 'general'),
    ('enable_registration', 'false', 'boolean', 'general'),
    ('maintenance_mode', 'false', 'boolean', 'advanced');

-- Sample news article
INSERT INTO news (title, slug, content, excerpt, status, published_at, created_by, updated_by)
VALUES 
    ('Welcome to the New School Year', 'welcome-new-school-year', '<h2>A New Beginning</h2><p>We are excited to welcome all students back for another amazing academic year. Our commitment to excellence continues as we introduce new programs and enhanced facilities.</p>', 'Welcome back to an exciting new school year!', 'published', NOW(), 1, 1),
    ('Science Fair 2025 Results', 'science-fair-2025-results', '<h2>Outstanding Achievements</h2><p>Congratulations to all participants in this year''s Science Fair. The projects demonstrated incredible innovation and scientific thinking.</p>', 'Results from the annual Science Fair competition.', 'published', NOW() - INTERVAL '7 days', 1, 1);

-- Sample events
INSERT INTO events (title, slug, description, venue, event_date, end_date, status, created_by, updated_by)
VALUES 
    ('Open Day 2025', 'open-day-2025', '<p>Join us for our annual Open Day. Tour the facilities, meet teachers, and learn about our programs.</p>', 'Main Campus Hall', NOW() + INTERVAL '30 days', NOW() + INTERVAL '30 days' + INTERVAL '8 hours', 'published', 1, 1),
    ('Parent-Teacher Conference', 'parent-teacher-conference', '<p>Schedule meetings with your child''s teachers to discuss academic progress.</p>', 'School Classrooms', NOW() + INTERVAL '14 days', NOW() + INTERVAL '14 days' + INTERVAL '4 hours', 'published', 1, 1);
