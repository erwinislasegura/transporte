<?php

declare(strict_types=1);

return [
    'Maestro' => ['*'],
    'Gerencial' => ['dashboard', 'commercial', 'operations', 'fleet', 'workshop', 'fuel', 'purchasing', 'billing', 'finance', 'hr', 'hse', 'environment', 'documents', 'bi', 'approvals'],
    'Supervisor Operativo' => ['dashboard', 'commercial.view', 'operations', 'fleet.view', 'documents', 'bi.view'],
    'Administrativo' => ['dashboard', 'commercial', 'purchasing', 'billing', 'finance', 'documents', 'hr.view', 'bi.view'],
    'Supervisor Taller' => ['dashboard', 'fleet', 'workshop', 'purchasing.view', 'documents', 'bi.view'],
    'Conductor' => ['dashboard', 'operations.own', 'fuel.own', 'documents.own'],
    'RR.HH.' => ['dashboard', 'hr', 'documents', 'bi.view'],
    'Prevencionista' => ['dashboard', 'hse', 'documents', 'bi.view'],
    'Medio Ambiente' => ['dashboard', 'environment', 'documents', 'bi.view'],
];
