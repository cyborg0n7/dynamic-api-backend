-- Fix request_logs table schema for orchestration logging
-- Add missing columns that are required for proper orchestration logging

-- Add api_id column (nullable since orchestration requests don't have a specific API)
ALTER TABLE request_logs ADD COLUMN api_id BIGINT UNSIGNED NULL AFTER id;

-- Add orchestration_id column for tracking orchestration jobs
ALTER TABLE request_logs ADD COLUMN orchestration_id VARCHAR(255) NULL AFTER api_id;

-- Add user column for tracking which user initiated the orchestration
ALTER TABLE request_logs ADD COLUMN user VARCHAR(255) NULL AFTER orchestration_id;

-- Make status_code nullable since it might not always be available
ALTER TABLE request_logs MODIFY COLUMN status_code INT NULL;

-- Add indexes for better performance
CREATE INDEX idx_orchestration_id ON request_logs(orchestration_id);
CREATE INDEX idx_user ON request_logs(user);
CREATE INDEX idx_created_at ON request_logs(created_at);
