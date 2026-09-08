-- Postgres doesn't auto-index foreign keys (unlike primary keys), and this
-- schema had zero explicit indexes beyond what PRIMARY KEY/UNIQUE create.
-- Every one of these columns is filtered or joined on somewhere in the app
-- (site scoping, attendance lookups, billing/payroll listings, audit log).
-- Purely additive -- no data is touched, safe to run any time.
CREATE INDEX IF NOT EXISTS idx_sites_client_id ON sites(client_id);
CREATE INDEX IF NOT EXISTS idx_sites_is_active ON sites(is_active);

CREATE INDEX IF NOT EXISTS idx_users_role_id ON users(role_id);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);

CREATE INDEX IF NOT EXISTS idx_usa_user_id ON user_site_assignments(user_id);
CREATE INDEX IF NOT EXISTS idx_usa_site_id ON user_site_assignments(site_id);

CREATE INDEX IF NOT EXISTS idx_workers_site_id ON workers(site_id);
CREATE INDEX IF NOT EXISTS idx_workers_category_id ON workers(category_id);
CREATE INDEX IF NOT EXISTS idx_workers_status ON workers(status);

CREATE INDEX IF NOT EXISTS idx_attendance_worker_id ON attendance(worker_id);
CREATE INDEX IF NOT EXISTS idx_attendance_site_id ON attendance(site_id);
CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(attendance_date);

CREATE INDEX IF NOT EXISTS idx_manager_attendance_user_id ON manager_attendance(user_id);

CREATE INDEX IF NOT EXISTS idx_billing_client_id ON billing(client_id);
CREATE INDEX IF NOT EXISTS idx_billing_site_id ON billing(site_id);

CREATE INDEX IF NOT EXISTS idx_payroll_worker_id ON payroll(worker_id);

CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_timestamp ON audit_logs(timestamp DESC);
