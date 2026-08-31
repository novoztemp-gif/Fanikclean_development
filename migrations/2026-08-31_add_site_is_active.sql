-- Adds soft-delete support to sites.
-- Idempotent: safe to run more than once.
ALTER TABLE sites ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT TRUE;
