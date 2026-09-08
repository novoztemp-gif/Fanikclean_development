-- Adds a terminal 'Deleted' status for users. Deleting a user never removes
-- their row or any related data (attendance, payroll, audit_logs, invoices,
-- etc. all keep referencing users.id) -- it only marks the account so it can
-- no longer log in or be selected anywhere, and it drops out of the User
-- Management list for good. Only reachable from 'Inactive' (Suspended), and
-- irreversible by design.
ALTER TABLE users DROP CONSTRAINT IF EXISTS users_status_check;
ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('Active', 'Inactive', 'Deleted'));
