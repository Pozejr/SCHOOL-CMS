-- Migration: 001_create_users_table
-- Created: 2025-01-01
-- Description: Create users table for authentication and user management

CREATE TYPE user_role AS ENUM ('super_admin', 'admin', 'editor');
CREATE TYPE user_status AS ENUM ('active', 'inactive', 'locked');

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role user_role NOT NULL DEFAULT 'editor',
    status user_status NOT NULL DEFAULT 'active',
    first_name VARCHAR(100) NOT NULL DEFAULT '',
    last_name VARCHAR(100) NOT NULL DEFAULT '',
    avatar VARCHAR(500) DEFAULT NULL,
    last_login TIMESTAMP WITH TIME ZONE DEFAULT NULL,
    login_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until TIMESTAMP WITH TIME ZONE DEFAULT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);

-- Comment
COMMENT ON TABLE users IS 'System users for CMS authentication';
COMMENT ON COLUMN users.password IS 'Bcrypt hashed password';
COMMENT ON COLUMN users.role IS 'User role: super_admin, admin, or editor';
COMMENT ON COLUMN users.status IS 'Account status: active, inactive, or locked';
