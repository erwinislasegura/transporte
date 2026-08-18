CREATE TABLE IF NOT EXISTS roles (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    permissions_json LONGTEXT NOT NULL,
    protected TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (id, name, description, permissions_json, protected, active) VALUES
(UUID(), 'Super Administrador', 'Acceso total al sistema.', '["*"]', 1, 1),
(UUID(), 'Maestro', 'Acceso total operativo y administrativo.', '["*"]', 1, 1),
(UUID(), 'Gerencial', 'Gestión ejecutiva y acceso transversal.', '["dashboard","commercial","operations","fleet","workshop","fuel","purchasing","billing","finance","hr","hse","environment","documents","bi","approvals"]', 0, 1),
(UUID(), 'Supervisor Operativo', 'Supervisión de operaciones y flota.', '["dashboard","commercial.view","operations","fleet.view","documents","bi.view"]', 0, 1),
(UUID(), 'Administrativo', 'Gestión administrativa, compras y finanzas.', '["dashboard","commercial","purchasing","billing","finance","documents","hr.view","bi.view"]', 0, 1),
(UUID(), 'Supervisor Taller', 'Gestión de flota, taller y abastecimiento.', '["dashboard","fleet","workshop","purchasing.view","documents","bi.view"]', 0, 1),
(UUID(), 'Conductor', 'Acceso personal a operaciones y documentos.', '["dashboard","operations.own","fuel.own","documents.own"]', 0, 1),
(UUID(), 'RR.HH.', 'Gestión de recursos humanos.', '["dashboard","hr","documents","bi.view"]', 0, 1),
(UUID(), 'Prevencionista', 'Gestión HSE y documentación.', '["dashboard","hse","documents","bi.view"]', 0, 1),
(UUID(), 'Medio Ambiente', 'Gestión ambiental y documentación.', '["dashboard","environment","documents","bi.view"]', 0, 1);