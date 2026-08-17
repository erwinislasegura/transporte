-- Ejecutar solo en instalaciones creadas con una versión anterior de 01-schema.sql.
-- Alinea los índices y DateTime de MySQL con los modelos SQLAlchemy originales.

ALTER TABLE companies
    DROP INDEX idx_companies_active,
    MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE users
    MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE clients
    DROP INDEX uq_clients_company_code,
    DROP INDEX uq_clients_company_rut,
    DROP INDEX idx_clients_business_name,
    DROP INDEX idx_clients_active,
    ADD INDEX idx_clients_company (company_id),
    ADD INDEX idx_clients_code (code),
    ADD INDEX idx_clients_rut (rut),
    MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
