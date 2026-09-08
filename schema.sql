-- FanikClean ERP System - Unified Database Schema
-- Last Updated: 2026-04-20

-- 1. Configuration Tables
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO roles (name) VALUES ('Admin'), ('Manager') ON CONFLICT DO NOTHING;

CREATE TABLE worker_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    default_rate NUMERIC(10, 2) NOT NULL
);

INSERT INTO worker_categories (name, default_rate) VALUES 
('Supervisor', 700.00),
('Associate', 500.00),
('Skilled', 600.00),
('Helper', 380.00) ON CONFLICT DO NOTHING;

-- 2. Client & Site Infrastructure
CREATE TABLE clients (
    id SERIAL PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    mobile VARCHAR(15),
    email VARCHAR(100),
    gstin VARCHAR(50),
    address TEXT,
    contract_start DATE,
    contract_end DATE,
    billing_cycle VARCHAR(20) DEFAULT 'Monthly',
    status VARCHAR(20) DEFAULT 'Active' CHECK (status IN ('Active', 'Inactive', 'Renewal Due'))
);

CREATE TABLE sites (
    id SERIAL PRIMARY KEY,
    client_id INT REFERENCES clients(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE
);

-- 3. Identity & Access
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT REFERENCES roles(id),
    site_id INT REFERENCES sites(id) ON DELETE SET NULL, 
    phone VARCHAR(15),
    
    -- Guardian Details
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(15),
    guardian_place VARCHAR(150),
    
    status VARCHAR(20) DEFAULT 'Active' CHECK (status IN ('Active', 'Inactive', 'Deleted')),
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_site_assignments (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    site_id INT REFERENCES sites(id) ON DELETE CASCADE,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, site_id)
);

-- 4. HR & Field Workforce
CREATE TABLE workers (
    id SERIAL PRIMARY KEY,
    worker_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15),
    aadhaar VARCHAR(20),
    doj DATE,
    category_id INT REFERENCES worker_categories(id),
    site_id INT REFERENCES sites(id) ON DELETE SET NULL,
    daily_rate_override NUMERIC(10, 2),
    
    -- HR Fields (Aligned with Worker Model)
    photo_path TEXT,
    esi_number VARCHAR(50),
    pf_number VARCHAR(50),
    age INT,
    experience TEXT,
    
    -- Guardian Details
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(15),
    guardian_place VARCHAR(150),
    
    -- Managed Assets (Legacy fields kept for compatibility)
    uniform_issue_date DATE,
    uniform_details TEXT,
    
    status VARCHAR(20) DEFAULT 'Active' CHECK (status IN ('Active', 'Inactive', 'Removed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE worker_assets (
    id SERIAL PRIMARY KEY,
    worker_id INT REFERENCES workers(id) ON DELETE CASCADE,
    item_name VARCHAR(150) NOT NULL,
    issue_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Attendance & Operations
CREATE TABLE attendance (
    id SERIAL PRIMARY KEY,
    worker_id INT REFERENCES workers(id) ON DELETE CASCADE,
    site_id INT REFERENCES sites(id) ON DELETE CASCADE,
    attendance_date DATE NOT NULL,
    status VARCHAR(10) CHECK (status IN ('p', 'a', 'h', 'off', 'pl', 'sd')) NOT NULL,
    ot_hours NUMERIC(5, 2) DEFAULT 0,
    note TEXT,
    locked BOOLEAN DEFAULT FALSE,
    updated_by INT REFERENCES users(id),
    UNIQUE(worker_id, attendance_date)
);

CREATE TABLE manager_attendance (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    attendance_date DATE NOT NULL,
    status VARCHAR(10) CHECK (status IN ('p', 'a', 'h', 'off', 'pl', 'sd')) NOT NULL,
    note TEXT,
    updated_by INT REFERENCES users(id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, attendance_date)
);

-- 6. Financial & Commercial
CREATE TABLE rate_master (
    id SERIAL PRIMARY KEY,
    client_id INT REFERENCES clients(id) ON DELETE CASCADE,
    category_id INT REFERENCES worker_categories(id),
    rate_per_day NUMERIC(10, 2) NOT NULL,
    UNIQUE(client_id, category_id)
);

CREATE TABLE billing (
    id SERIAL PRIMARY KEY,
    client_id INT REFERENCES clients(id) ON DELETE CASCADE,
    site_id INT REFERENCES sites(id) ON DELETE CASCADE,
    month_year VARCHAR(7), 
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    subtotal NUMERIC(12, 2) NOT NULL,
    cgst NUMERIC(12, 2) NOT NULL,
    sgst NUMERIC(12, 2) NOT NULL,
    grand_total NUMERIC(12, 2) NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending' CHECK (status IN ('Pending', 'Approved', 'Invoiced')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(client_id, site_id, from_date, to_date)
);

CREATE TABLE invoice_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL, 
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO invoice_templates (name, slug, is_default) VALUES 
('Standard Minimal', 'standard', TRUE),
('Modern Vibrant', 'modern', FALSE)
ON CONFLICT (slug) DO NOTHING;

CREATE TABLE invoices (
    id SERIAL PRIMARY KEY,
    billing_id INT REFERENCES billing(id) UNIQUE,
    template_id INT REFERENCES invoice_templates(id),
    invoice_no VARCHAR(50) UNIQUE NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE,
    amount NUMERIC(12, 2) NOT NULL,
    status VARCHAR(20) DEFAULT 'Unpaid' CHECK (status IN ('Unpaid', 'Pending', 'Paid')),
    payment_date DATE,
    payment_mode VARCHAR(50),
    payment_ref VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE payroll (
    id SERIAL PRIMARY KEY,
    worker_id INT REFERENCES workers(id) ON DELETE CASCADE,
    month_year VARCHAR(7) NOT NULL,
    days_worked NUMERIC(5, 2) NOT NULL,
    basic_pay NUMERIC(10, 2) NOT NULL,
    ot_days NUMERIC(5, 2) DEFAULT 0,
    ot_pay NUMERIC(10, 2) DEFAULT 0,
    advance_deduction NUMERIC(10, 2) DEFAULT 0,
    net_pay NUMERIC(10, 2) NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending' CHECK (status IN ('Pending', 'Approved', 'Paid')),
    UNIQUE(worker_id, month_year)
);

-- 7. Audit & Maintenance
CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    action TEXT NOT NULL,
    module VARCHAR(50) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
