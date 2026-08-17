CREATE TABLE IF NOT EXISTS companies (
    id CHAR(36) PRIMARY KEY,
    rut VARCHAR(12) NOT NULL UNIQUE,
    legal_name VARCHAR(180) NOT NULL,
    trade_name VARCHAR(180) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_companies_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    full_name VARCHAR(180) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(60) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_company (company_id),
    INDEX idx_users_role (role),
    CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clients (
    id CHAR(36) PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    code VARCHAR(30) NOT NULL,
    rut VARCHAR(12) NOT NULL,
    business_name VARCHAR(180) NOT NULL,
    payment_condition VARCHAR(80) NOT NULL DEFAULT 'Transferencia 30 días',
    requires_oc VARCHAR(20) NOT NULL DEFAULT 'Opcional',
    requires_hes VARCHAR(20) NOT NULL DEFAULT 'Opcional',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clients_company_code (company_id, code),
    UNIQUE KEY uq_clients_company_rut (company_id, rut),
    INDEX idx_clients_business_name (business_name),
    INDEX idx_clients_active (active),
    CONSTRAINT fk_clients_company FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
