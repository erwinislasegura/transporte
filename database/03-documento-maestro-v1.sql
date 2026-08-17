-- BGV Enterprise · Documento Maestro v1.0
-- Migración incremental para una base creada con 01-schema.sql y 02-seed.sql.
-- Ejecutar una sola vez desde phpMyAdmin o la consola MySQL/MariaDB de XAMPP.
-- Conserva todos los registros existentes; no elimina ni renombra datos.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE companies
    ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE users
    ADD COLUMN last_login_at DATETIME NULL,
    ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE clients
    ADD COLUMN contact_name VARCHAR(180) NULL,
    ADD COLUMN email VARCHAR(180) NULL,
    ADD COLUMN phone VARCHAR(50) NULL,
    ADD COLUMN address VARCHAR(255) NULL,
    ADD COLUMN payment_days INT NULL,
    ADD COLUMN credit_limit DECIMAL(15,2) NULL,
    ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS audit_logs (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NULL,
    user_id CHAR(36) NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(100) NOT NULL,
    record_id CHAR(36) NULL,
    previous_data JSON NULL,
    new_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_company_date (company_id, created_at),
    INDEX idx_audit_record (module, record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_parameters (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL,
    parameter_key VARCHAR(100) NOT NULL, label VARCHAR(180) NOT NULL, value TEXT NOT NULL,
    value_type VARCHAR(30) NOT NULL DEFAULT 'text', description TEXT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_parameters_company_key (company_id, parameter_key), INDEX idx_parameters_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS approval_requests (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL,
    module VARCHAR(100) NOT NULL, record_id CHAR(36) NOT NULL, requested_by CHAR(36) NOT NULL,
    requested_at DATETIME NOT NULL, assigned_role VARCHAR(60) NOT NULL, decision_notes TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Pendiente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_approvals_company (company_id), INDEX idx_approvals_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cost_centers (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, name VARCHAR(180) NOT NULL,
    area VARCHAR(100) NULL, active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cost_centers_company (company_id), UNIQUE KEY uq_cost_center_code (company_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppliers (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, rut VARCHAR(20) NOT NULL,
    business_name VARCHAR(180) NOT NULL, category VARCHAR(100) NULL, contact_name VARCHAR(180) NULL,
    email VARCHAR(180) NULL, phone VARCHAR(50) NULL, payment_condition VARCHAR(100) NULL, active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_suppliers_company (company_id), INDEX idx_suppliers_rut (rut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS owners (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, rut VARCHAR(20) NOT NULL,
    name VARCHAR(180) NOT NULL, email VARCHAR(180) NULL, phone VARCHAR(50) NULL, bank_details TEXT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_owners_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shifts (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, name VARCHAR(180) NOT NULL,
    cycle_type VARCHAR(40) NULL, weekly_hours DECIMAL(8,2) NULL, start_time TIME NULL, end_time TIME NULL,
    break_minutes INT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_shifts_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workers (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, rut VARCHAR(20) NOT NULL,
    full_name VARCHAR(180) NOT NULL, position VARCHAR(120) NOT NULL, area VARCHAR(100) NULL, cost_center_id CHAR(36) NULL,
    shift_id CHAR(36) NULL, email VARCHAR(180) NULL, phone VARCHAR(50) NULL, hire_date DATE NULL,
    contract_type VARCHAR(80) NULL, contract_end_date DATE NULL, pension_fund VARCHAR(100) NULL,
    health_provider VARCHAR(100) NULL, bank_name VARCHAR(100) NULL, bank_account VARCHAR(100) NULL,
    base_salary DECIMAL(15,2) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_workers_company (company_id), INDEX idx_workers_rut (rut), INDEX idx_workers_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS equipment (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, name VARCHAR(180) NOT NULL,
    equipment_type VARCHAR(80) NOT NULL, plate VARCHAR(20) NULL, brand VARCHAR(80) NULL, model VARCHAR(100) NULL,
    year INT NULL, owner_id CHAR(36) NULL, ownership_type VARCHAR(40) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Disponible',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_equipment_company (company_id), INDEX idx_equipment_status (status), INDEX idx_equipment_plate (plate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sites (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, name VARCHAR(180) NOT NULL,
    client_id CHAR(36) NULL, address VARCHAR(255) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Activa',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sites_company (company_id), INDEX idx_sites_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_contracts (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, client_id CHAR(36) NOT NULL,
    name VARCHAR(180) NOT NULL, start_date DATE NOT NULL, end_date DATE NULL, amount_limit DECIMAL(15,2) NULL,
    requires_oc TINYINT(1) NOT NULL DEFAULT 0, requires_hes TINYINT(1) NOT NULL DEFAULT 0, status VARCHAR(40) NOT NULL DEFAULT 'Borrador',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contracts_company (company_id), INDEX idx_contracts_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotations (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, client_id CHAR(36) NOT NULL,
    issue_date DATE NOT NULL, valid_until DATE NULL, description TEXT NOT NULL, net_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0, total DECIMAL(15,2) NOT NULL DEFAULT 0, status VARCHAR(40) NOT NULL DEFAULT 'Borrador',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quotations_company (company_id), INDEX idx_quotations_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tariffs (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, client_id CHAR(36) NOT NULL,
    contract_id CHAR(36) NULL, service_type VARCHAR(120) NOT NULL, origin VARCHAR(180) NULL, destination VARCHAR(180) NULL,
    unit VARCHAR(30) NOT NULL, rate DECIMAL(15,2) NOT NULL, valid_from DATE NOT NULL, valid_until DATE NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Borrador', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tariffs_company (company_id), INDEX idx_tariffs_client (client_id), INDEX idx_tariffs_validity (valid_from, valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_purchase_orders (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(60) NOT NULL, client_id CHAR(36) NOT NULL,
    contract_id CHAR(36) NULL, issue_date DATE NULL, end_date DATE NULL, amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'Borrador', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client_po_company (company_id), INDEX idx_client_po_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hes_records (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(60) NOT NULL, client_id CHAR(36) NOT NULL,
    contract_id CHAR(36) NULL, period CHAR(7) NOT NULL, amount DECIMAL(15,2) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Pendiente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hes_company (company_id), INDEX idx_hes_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rental_contracts (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, owner_id CHAR(36) NOT NULL,
    equipment_id CHAR(36) NOT NULL, start_date DATE NOT NULL, end_date DATE NULL, monthly_amount DECIMAL(15,2) NOT NULL,
    maintenance_responsible VARCHAR(120) NULL, insurance_responsible VARCHAR(120) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Borrador',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rentals_company (company_id), INDEX idx_rentals_equipment (equipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workshops (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, name VARCHAR(180) NOT NULL,
    workshop_type VARCHAR(80) NULL, supplier_id CHAR(36) NULL, phone VARCHAR(50) NULL, active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_workshops_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedules (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(40) NOT NULL, service_date DATE NOT NULL,
    client_id CHAR(36) NOT NULL, site_id CHAR(36) NULL, equipment_id CHAR(36) NULL, worker_id CHAR(36) NULL,
    notes TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'Borrador', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_schedules_company_date (company_id, service_date), INDEX idx_schedules_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workdays (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, worker_id CHAR(36) NOT NULL,
    shift_id CHAR(36) NULL, equipment_id CHAR(36) NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NULL,
    start_odometer DECIMAL(15,1) NULL, end_odometer DECIMAL(15,1) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Abierta',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_workdays_company (company_id), INDEX idx_workdays_worker (worker_id), INDEX idx_workdays_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trips (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, workday_id CHAR(36) NOT NULL,
    client_id CHAR(36) NOT NULL, site_id CHAR(36) NULL, origin VARCHAR(180) NOT NULL, destination VARCHAR(180) NOT NULL,
    scheduled_at DATETIME NULL, tons DECIMAL(15,3) NULL, rate DECIMAL(15,2) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Programado',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_trips_company (company_id), INDEX idx_trips_workday (workday_id), INDEX idx_trips_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trip_events (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, trip_id CHAR(36) NOT NULL, event_type VARCHAR(60) NOT NULL,
    started_at DATETIME NOT NULL, ended_at DATETIME NULL, duration_minutes INT NULL, notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_trip_events_company (company_id), INDEX idx_trip_events_trip (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispatch_guides (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(60) NOT NULL, trip_id CHAR(36) NOT NULL,
    issue_date DATE NOT NULL, quantity DECIMAL(15,3) NULL, unit VARCHAR(30) NULL, received_by VARCHAR(180) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Pendiente', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_guides_company (company_id), INDEX idx_guides_trip (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_settlements (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, client_id CHAR(36) NOT NULL,
    period CHAR(7) NOT NULL, net_amount DECIMAL(15,2) NOT NULL DEFAULT 0, extras_amount DECIMAL(15,2) NULL DEFAULT 0,
    total DECIMAL(15,2) NULL DEFAULT 0, status VARCHAR(40) NOT NULL DEFAULT 'Borrador',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_settlements_company (company_id), INDEX idx_settlements_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS failure_reports (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, equipment_id CHAR(36) NOT NULL,
    reported_at DATETIME NOT NULL, description TEXT NOT NULL, severity VARCHAR(30) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Abierta',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_failures_company (company_id), INDEX idx_failures_equipment (equipment_id), INDEX idx_failures_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_orders (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, equipment_id CHAR(36) NOT NULL,
    workshop_id CHAR(36) NULL, opened_at DATE NOT NULL, diagnosis TEXT NULL, estimated_cost DECIMAL(15,2) NULL,
    actual_cost DECIMAL(15,2) NULL, payment_responsible VARCHAR(60) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Abierta',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_work_orders_company (company_id), INDEX idx_work_orders_equipment (equipment_id), INDEX idx_work_orders_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fuel_records (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, equipment_id CHAR(36) NOT NULL,
    worker_id CHAR(36) NULL, recorded_at DATETIME NOT NULL, odometer DECIMAL(15,1) NULL, liters DECIMAL(15,3) NOT NULL,
    unit_cost DECIMAL(15,2) NULL, total_cost DECIMAL(15,2) NULL, tax_credit DECIMAL(15,2) NULL,
    specific_tax DECIMAL(15,2) NULL, efficiency DECIMAL(15,3) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fuel_company (company_id), INDEX idx_fuel_equipment (equipment_id), INDEX idx_fuel_date (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_requests (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, requested_at DATE NOT NULL,
    requester_id CHAR(36) NOT NULL, cost_center_id CHAR(36) NOT NULL, description TEXT NOT NULL,
    estimated_amount DECIMAL(15,2) NULL, priority VARCHAR(30) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Borrador',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_requests_company (company_id), INDEX idx_requests_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_purchase_orders (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, supplier_id CHAR(36) NOT NULL,
    request_id CHAR(36) NULL, issue_date DATE NOT NULL, description TEXT NULL, net_amount DECIMAL(15,2) NULL,
    tax_amount DECIMAL(15,2) NULL, total DECIMAL(15,2) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Borrador',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_po_company (company_id), INDEX idx_supplier_po_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_invoices (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, folio VARCHAR(60) NOT NULL, supplier_id CHAR(36) NOT NULL,
    purchase_order_id CHAR(36) NULL, issue_date DATE NOT NULL, due_date DATE NULL, net_amount DECIMAL(15,2) NULL,
    tax_amount DECIMAL(15,2) NULL, total DECIMAL(15,2) NOT NULL, status VARCHAR(40) NOT NULL DEFAULT 'Pendiente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_invoices_company (company_id), INDEX idx_supplier_invoices_supplier (supplier_id), INDEX idx_supplier_invoices_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_invoices (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(60) NOT NULL, client_id CHAR(36) NOT NULL,
    settlement_id CHAR(36) NULL, client_order_id CHAR(36) NULL, hes_id CHAR(36) NULL, issue_date DATE NOT NULL,
    due_date DATE NOT NULL, payment_condition VARCHAR(100) NOT NULL, net_amount DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) NULL, total DECIMAL(15,2) NOT NULL, balance DECIMAL(15,2) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Borrador', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_invoices_company (company_id), INDEX idx_customer_invoices_client (client_id), INDEX idx_customer_invoices_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_statements (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, client_id CHAR(36) NOT NULL,
    period CHAR(7) NOT NULL, total DECIMAL(15,2) NOT NULL, paid_amount DECIMAL(15,2) NULL DEFAULT 0,
    retention_amount DECIMAL(15,2) NULL DEFAULT 0, balance DECIMAL(15,2) NULL, due_date DATE NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Pendiente', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payment_statements_company (company_id), INDEX idx_payment_statements_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS collections (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, invoice_id CHAR(36) NOT NULL,
    contacted_at DATETIME NOT NULL, contact_method VARCHAR(60) NULL, commitment_date DATE NULL, notes TEXT NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Abierta', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_collections_company (company_id), INDEX idx_collections_invoice (invoice_id), INDEX idx_collections_commitment (commitment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_movements (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, movement_date DATE NOT NULL,
    movement_type VARCHAR(30) NOT NULL, category VARCHAR(100) NOT NULL, cost_center_id CHAR(36) NOT NULL,
    client_id CHAR(36) NULL, equipment_id CHAR(36) NULL, amount DECIMAL(15,2) NOT NULL, description TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Borrador', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_movements_company_date (company_id, movement_date), INDEX idx_movements_type (movement_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS budgets (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, period CHAR(7) NOT NULL,
    cost_center_id CHAR(36) NOT NULL, client_id CHAR(36) NULL, budget_amount DECIMAL(15,2) NOT NULL,
    actual_amount DECIMAL(15,2) NULL, notes TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'Borrador',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_budgets_company_period (company_id, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, worker_id CHAR(36) NOT NULL,
    workday_id CHAR(36) NULL, attendance_date DATE NOT NULL, check_in TIME NULL, check_out TIME NULL,
    regular_hours DECIMAL(8,2) NULL, overtime_hours DECIMAL(8,2) NULL, incident VARCHAR(255) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Borrador', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_attendance_company_date (company_id, attendance_date), INDEX idx_attendance_worker (worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS per_diems (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, worker_id CHAR(36) NOT NULL,
    trip_id CHAR(36) NULL, expense_date DATE NOT NULL, per_diem_type VARCHAR(60) NULL, amount DECIMAL(15,2) NOT NULL,
    notes TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'Borrador', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_per_diems_company (company_id), INDEX idx_per_diems_worker (worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payrolls (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, worker_id CHAR(36) NOT NULL,
    period CHAR(7) NOT NULL, base_salary DECIMAL(15,2) NULL, overtime_amount DECIMAL(15,2) NULL,
    bonuses DECIMAL(15,2) NULL, deductions DECIMAL(15,2) NULL, gross_amount DECIMAL(15,2) NULL,
    net_amount DECIMAL(15,2) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Borrador',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payrolls_company_period (company_id, period), INDEX idx_payrolls_worker (worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incidents (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, occurred_at DATETIME NOT NULL,
    incident_type VARCHAR(60) NULL, site_id CHAR(36) NULL, worker_id CHAR(36) NULL, description TEXT NOT NULL,
    severity VARCHAR(30) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Abierto', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incidents_company (company_id), INDEX idx_incidents_severity (severity), INDEX idx_incidents_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inspections (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, inspection_date DATE NOT NULL,
    site_id CHAR(36) NULL, equipment_id CHAR(36) NULL, checklist_type VARCHAR(120) NOT NULL,
    result VARCHAR(50) NULL, findings TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'Planificada',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_inspections_company_date (company_id, inspection_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS corrective_actions (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, source_type VARCHAR(100) NOT NULL,
    source_id CHAR(36) NULL, description TEXT NOT NULL, responsible_id CHAR(36) NOT NULL, due_date DATE NOT NULL,
    verification_notes TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'Abierta', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_actions_company (company_id), INDEX idx_actions_due (due_date), INDEX idx_actions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS environmental_events (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, event_date DATE NOT NULL,
    event_type VARCHAR(60) NULL, site_id CHAR(36) NULL, quantity DECIMAL(15,3) NULL, unit VARCHAR(30) NULL,
    description TEXT NULL, disposition TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'Abierto',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_environment_company_date (company_id, event_date), INDEX idx_environment_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accreditations (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, subject_type VARCHAR(40) NULL,
    subject_name VARCHAR(180) NOT NULL, accreditation_type VARCHAR(120) NOT NULL, issued_at DATE NULL, expires_at DATE NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Pendiente', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_accreditations_company (company_id), INDEX idx_accreditations_expiry (expires_at), INDEX idx_accreditations_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, document_type VARCHAR(120) NOT NULL,
    subject_type VARCHAR(60) NULL, subject_id CHAR(36) NULL, file_path VARCHAR(500) NULL, issued_at DATE NULL,
    expires_at DATE NULL, notes TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'Pendiente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_documents_company (company_id), INDEX idx_documents_subject (subject_type, subject_id), INDEX idx_documents_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alerts (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36) NOT NULL, code VARCHAR(50) NOT NULL, alert_type VARCHAR(100) NOT NULL,
    title VARCHAR(180) NOT NULL, message TEXT NOT NULL, severity VARCHAR(30) NULL, due_at DATETIME NULL,
    assigned_to CHAR(36) NULL, status VARCHAR(40) NOT NULL DEFAULT 'Abierta', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_alerts_company (company_id), INDEX idx_alerts_due (due_at), INDEX idx_alerts_status_severity (status, severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO system_parameters (id, company_id, parameter_key, label, value, value_type, description, active)
SELECT UUID(), c.id, seed.parameter_key, seed.label, seed.value, seed.value_type, seed.description, 1
FROM companies c
CROSS JOIN (
    SELECT 'tax.iva_rate' parameter_key, 'Tasa IVA' label, '19' value, 'percent' value_type, 'Porcentaje de IVA usado en documentos comerciales.' description
    UNION ALL SELECT 'fuel.specific_tax', 'Impuesto específico combustible', '0', 'number', 'Valor parametrizable según el combustible y período.'
    UNION ALL SELECT 'hr.max_overtime_daily', 'Máximo horas extra diarias', '2', 'number', 'Límite de control; validar con la normativa y jornada aplicable.'
    UNION ALL SELECT 'alerts.document_days', 'Anticipación vencimiento documentos', '30', 'number', 'Días de anticipación para generar alertas.'
) seed;
