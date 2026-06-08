-- Migration: 006_create_activity_logs_table
-- Created: 2025-01-01
-- Description: Create activity logs table for audit trail

CREATE TABLE activity_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT DEFAULT NULL,
    description TEXT DEFAULT '',
    ip_address INET DEFAULT NULL,
    user_agent TEXT DEFAULT '',
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_action ON activity_logs(action);
CREATE INDEX idx_activity_logs_entity ON activity_logs(entity_type, entity_id);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);

-- Comments
COMMENT ON TABLE activity_logs IS 'Audit trail for all CMS actions';
