<?php

declare(strict_types=1);

$status = static fn(array $options): array => [
    'label' => 'Estado', 'type' => 'select', 'required' => true, 'options' => array_combine($options, $options),
];
$relation = static fn(string $label, string $table, string $display, bool $required = true): array => [
    'label' => $label, 'type' => 'select', 'required' => $required,
    'relation' => ['table' => $table, 'value' => 'id', 'label' => $display],
];
$module = static function (
    string $title, string $singular, string $group, string $area, string $icon,
    string $table, array $columns, array $fields, ?string $prefix = null,
    string $subtitle = '', bool $companyScoped = true
): array {
    return compact('title', 'singular', 'group', 'area', 'icon', 'table', 'columns', 'fields', 'prefix', 'subtitle', 'companyScoped');
};

return [
    'approvals' => $module('Aprobaciones', 'Aprobación', 'Configuración', 'approvals', 'bi-check2-circle', 'approval_requests',
        ['code'=>'Solicitud','module'=>'Módulo','requested_by'=>'Solicitante','requested_at'=>'Fecha','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'module'=>['label'=>'Módulo','required'=>true], 'record_id'=>['label'=>'ID del registro','required'=>true],
            'requested_by'=>$relation('Solicitante','users','full_name'), 'requested_at'=>['label'=>'Fecha','type'=>'datetime-local','required'=>true],
            'assigned_role'=>['label'=>'Rol aprobador','required'=>true], 'decision_notes'=>['label'=>'Observación','type'=>'textarea'],
            'status'=>$status(['Pendiente','Aprobada','Rechazada','Anulada']),
        ], 'APR', 'Bandeja transversal para decisiones y segregación de funciones.'),

    'system-parameters' => $module('Parámetros del sistema', 'Parámetro', 'Configuración', 'security', 'bi-sliders2', 'system_parameters',
        ['parameter_key'=>'Clave','label'=>'Parámetro','value'=>'Valor','value_type'=>'Tipo','active'=>'Activo'], [
            'parameter_key'=>['label'=>'Clave técnica','required'=>true], 'label'=>['label'=>'Nombre','required'=>true],
            'value'=>['label'=>'Valor','required'=>true], 'value_type'=>['label'=>'Tipo','type'=>'select','options'=>['text'=>'Texto','number'=>'Número','percent'=>'Porcentaje','boolean'=>'Sí/No']],
            'description'=>['label'=>'Descripción','type'=>'textarea'], 'active'=>['label'=>'Activo','type'=>'checkbox','default'=>1],
        ], null, 'Impuestos, límites, folios y reglas configurables sin editar código.'),

    'users' => $module('Usuarios', 'Usuario', 'Configuración', 'security', 'bi-people', 'users',
        ['full_name' => 'Nombre', 'username' => 'Usuario', 'email' => 'Correo', 'role' => 'Rol', 'active' => 'Activo'], [
            'full_name' => ['label' => 'Nombre completo', 'required' => true],
            'username' => ['label' => 'Usuario', 'required' => true],
            'email' => ['label' => 'Correo', 'type' => 'email', 'required' => true],
            'password' => ['label' => 'Contraseña', 'type' => 'password', 'required_on_create' => true, 'virtual' => true],
            'role' => ['label' => 'Rol', 'type' => 'select', 'required' => true, 'options' => array_combine(
                ['Maestro','Gerencial','Supervisor Operativo','Administrativo','Supervisor Taller','Conductor','RR.HH.','Prevencionista','Medio Ambiente'],
                ['Maestro','Gerencial','Supervisor Operativo','Administrativo','Supervisor Taller','Conductor','RR.HH.','Prevencionista','Medio Ambiente']
            )],
            'active' => ['label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
        ], null, 'Accesos, roles y segregación de funciones.'),

    'cost-centers' => $module('Centros de costo', 'Centro de costo', 'Configuración', 'finance', 'bi-diagram-3', 'cost_centers',
        ['code' => 'Código', 'name' => 'Nombre', 'area' => 'Área', 'active' => 'Activo'], [
            'code' => ['label' => 'Código', 'required' => true], 'name' => ['label' => 'Nombre', 'required' => true],
            'area' => ['label' => 'Área'], 'active' => ['label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
        ], 'CC', 'Dimensión obligatoria para ingresos, costos y gastos.'),

    'sites' => $module('Faenas', 'Faena', 'Configuración', 'operations', 'bi-geo-alt', 'sites',
        ['code' => 'Código', 'name' => 'Faena', 'client_id' => 'Cliente', 'status' => 'Estado'], [
            'code' => ['label' => 'Código'], 'name' => ['label' => 'Nombre', 'required' => true],
            'client_id' => $relation('Cliente', 'clients', 'business_name', false), 'address' => ['label' => 'Dirección'],
            'status' => $status(['Activa','Suspendida','Cerrada']),
        ], 'FAE', 'Faenas, ubicaciones y contratos operativos.'),

    'clients' => $module('Clientes', 'Cliente', 'Comercial', 'commercial', 'bi-buildings', 'clients',
        ['code' => 'Código', 'rut' => 'RUT', 'business_name' => 'Razón social', 'payment_condition' => 'Pago', 'active' => 'Activo'], [
            'code' => ['label' => 'Código'], 'rut' => ['label' => 'RUT', 'required' => true],
            'business_name' => ['label' => 'Razón social', 'required' => true], 'contact_name' => ['label' => 'Contacto'],
            'email' => ['label' => 'Correo', 'type' => 'email'], 'phone' => ['label' => 'Teléfono'], 'address'=>['label'=>'Dirección'],
            'payment_condition' => ['label' => 'Condición de pago', 'required' => true, 'default' => 'Transferencia 30 días'],
            'payment_days'=>['label'=>'Plazo de pago (días)','type'=>'number'], 'credit_limit'=>['label'=>'Límite de crédito','type'=>'number','step'=>'0.01'],
            'requires_oc' => ['label' => 'Requiere OC', 'type' => 'select', 'options' => ['Opcional'=>'Opcional','Sí'=>'Sí','No'=>'No']],
            'requires_hes' => ['label' => 'Requiere HES', 'type' => 'select', 'options' => ['Opcional'=>'Opcional','Sí'=>'Sí','No'=>'No']],
            'active' => ['label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
        ], 'CLI', 'Prospectos, contactos y condiciones comerciales.'),

    'tariffs' => $module('Tarifas comerciales', 'Tarifa', 'Comercial', 'commercial', 'bi-tags', 'tariffs',
        ['code'=>'Código','client_id'=>'Cliente','service_type'=>'Servicio','unit'=>'Unidad','rate'=>'Tarifa','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'client_id'=>$relation('Cliente','clients','business_name'),
            'contract_id'=>$relation('Contrato','client_contracts','name',false), 'service_type'=>['label'=>'Tipo de servicio','required'=>true],
            'origin'=>['label'=>'Origen'], 'destination'=>['label'=>'Destino'], 'unit'=>['label'=>'Unidad','required'=>true],
            'rate'=>['label'=>'Tarifa neta','type'=>'number','step'=>'0.01','required'=>true], 'valid_from'=>['label'=>'Vigente desde','type'=>'date','required'=>true],
            'valid_until'=>['label'=>'Vigente hasta','type'=>'date'], 'status'=>$status(['Borrador','Vigente','Suspendida','Vencida']),
        ], 'TAR', 'Tarifas por cliente, contrato, ruta, servicio y vigencia.'),

    'suppliers' => $module('Proveedores', 'Proveedor', 'Comercial', 'purchasing', 'bi-truck', 'suppliers',
        ['code'=>'Código','rut'=>'RUT','business_name'=>'Razón social','category'=>'Categoría','active'=>'Activo'], [
            'code'=>['label'=>'Código'], 'rut'=>['label'=>'RUT','required'=>true], 'business_name'=>['label'=>'Razón social','required'=>true],
            'category'=>['label'=>'Categoría'], 'contact_name'=>['label'=>'Contacto'], 'email'=>['label'=>'Correo','type'=>'email'],
            'phone'=>['label'=>'Teléfono'], 'payment_condition'=>['label'=>'Condición de pago'], 'active'=>['label'=>'Activo','type'=>'checkbox','default'=>1],
        ], 'PRO', 'Proveedores, talleres, arrendadores y aseguradoras.'),

    'quotations' => $module('Cotizaciones', 'Cotización', 'Comercial', 'commercial', 'bi-file-earmark-text', 'quotations',
        ['code'=>'Folio','client_id'=>'Cliente','issue_date'=>'Emisión','total'=>'Total','status'=>'Estado'], [
            'code'=>['label'=>'Folio'], 'client_id'=>$relation('Cliente','clients','business_name'),
            'issue_date'=>['label'=>'Fecha de emisión','type'=>'date','required'=>true], 'valid_until'=>['label'=>'Válida hasta','type'=>'date'],
            'description'=>['label'=>'Descripción','type'=>'textarea','required'=>true], 'net_amount'=>['label'=>'Neto','type'=>'number','step'=>'0.01','required'=>true],
            'tax_amount'=>['label'=>'IVA','type'=>'number','step'=>'0.01'], 'total'=>['label'=>'Total','type'=>'number','step'=>'0.01'],
            'status'=>$status(['Borrador','Enviada','Aceptada','Rechazada','Vencida']),
        ], 'COT', 'Seguimiento comercial desde borrador hasta aceptación.'),

    'client-contracts' => $module('Contratos de clientes', 'Contrato', 'Comercial', 'commercial', 'bi-file-earmark-check', 'client_contracts',
        ['code'=>'Contrato','client_id'=>'Cliente','start_date'=>'Inicio','end_date'=>'Término','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'client_id'=>$relation('Cliente','clients','business_name'), 'name'=>['label'=>'Nombre del contrato','required'=>true],
            'start_date'=>['label'=>'Inicio','type'=>'date','required'=>true], 'end_date'=>['label'=>'Término','type'=>'date'],
            'amount_limit'=>['label'=>'Monto máximo','type'=>'number','step'=>'0.01'], 'requires_oc'=>['label'=>'OC obligatoria','type'=>'checkbox'],
            'requires_hes'=>['label'=>'HES obligatoria','type'=>'checkbox'], 'status'=>$status(['Borrador','Vigente','Suspendido','Finalizado']),
        ], 'CON', 'Contratos, vigencias, límites y requisitos documentales.'),

    'client-orders' => $module('Órdenes de compra cliente', 'OC cliente', 'Comercial', 'commercial', 'bi-receipt', 'client_purchase_orders',
        ['code'=>'OC','client_id'=>'Cliente','contract_id'=>'Contrato','amount'=>'Monto','status'=>'Estado'], [
            'code'=>['label'=>'Número OC','required'=>true], 'client_id'=>$relation('Cliente','clients','business_name'),
            'contract_id'=>$relation('Contrato','client_contracts','name',false), 'issue_date'=>['label'=>'Emisión','type'=>'date'],
            'end_date'=>['label'=>'Vencimiento','type'=>'date'], 'amount'=>['label'=>'Monto','type'=>'number','step'=>'0.01','required'=>true],
            'status'=>$status(['Borrador','Vigente','Agotada','Vencida','Anulada']),
        ], 'OCC', 'Control de vigencia, monto consumido y saldo de OC.'),

    'hes' => $module('HES', 'HES', 'Comercial', 'commercial', 'bi-file-check', 'hes_records',
        ['code'=>'HES','client_id'=>'Cliente','period'=>'Período','amount'=>'Monto','status'=>'Estado'], [
            'code'=>['label'=>'Número HES','required'=>true], 'client_id'=>$relation('Cliente','clients','business_name'),
            'contract_id'=>$relation('Contrato','client_contracts','name',false), 'period'=>['label'=>'Período','type'=>'month','required'=>true],
            'amount'=>['label'=>'Monto','type'=>'number','step'=>'0.01'], 'status'=>$status(['Pendiente','Recibida','Validada','Rechazada','Anulada']),
        ], 'HES', 'Hojas de entrada de servicio vinculadas al ciclo de facturación.'),

    'schedules' => $module('Programación', 'Programación', 'Operaciones', 'operations', 'bi-calendar3', 'schedules',
        ['code'=>'Código','service_date'=>'Fecha','client_id'=>'Cliente','site_id'=>'Faena','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'service_date'=>['label'=>'Fecha','type'=>'date','required'=>true],
            'client_id'=>$relation('Cliente','clients','business_name'), 'site_id'=>$relation('Faena','sites','name',false),
            'equipment_id'=>$relation('Equipo','equipment','name',false), 'worker_id'=>$relation('Conductor','workers','full_name',false),
            'notes'=>['label'=>'Instrucciones','type'=>'textarea'], 'status'=>$status(['Borrador','Confirmada','En ejecución','Completada','Anulada']),
        ], 'PRG', 'Programación y asignación diaria de recursos.'),

    'workdays' => $module('Jornadas', 'Jornada', 'Operaciones', 'operations', 'bi-clock-history', 'workdays',
        ['code'=>'Jornada','worker_id'=>'Conductor','start_at'=>'Inicio','end_at'=>'Término','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'worker_id'=>$relation('Conductor','workers','full_name'),
            'shift_id'=>$relation('Turno','shifts','name',false), 'equipment_id'=>$relation('Equipo','equipment','name'),
            'start_at'=>['label'=>'Inicio','type'=>'datetime-local','required'=>true], 'end_at'=>['label'=>'Término','type'=>'datetime-local'],
            'start_odometer'=>['label'=>'Km inicial','type'=>'number'], 'end_odometer'=>['label'=>'Km final','type'=>'number'],
            'status'=>$status(['Abierta','Cerrada','Observada','Aprobada','Anulada']),
        ], 'JOR', 'Jornadas con múltiples viajes, aprobación y trazabilidad.'),

    'trips' => $module('Viajes', 'Viaje', 'Operaciones', 'operations', 'bi-signpost-split', 'trips',
        ['code'=>'Viaje','workday_id'=>'Jornada','client_id'=>'Cliente','origin'=>'Origen','destination'=>'Destino','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'workday_id'=>$relation('Jornada','workdays','code'), 'client_id'=>$relation('Cliente','clients','business_name'),
            'site_id'=>$relation('Faena','sites','name',false), 'origin'=>['label'=>'Origen','required'=>true], 'destination'=>['label'=>'Destino','required'=>true],
            'scheduled_at'=>['label'=>'Programado','type'=>'datetime-local'], 'tons'=>['label'=>'Toneladas','type'=>'number','step'=>'0.001'],
            'rate'=>['label'=>'Tarifa','type'=>'number','step'=>'0.01'], 'status'=>$status(['Programado','Asignado','Iniciado','En carga','En tránsito','En descarga','Terminado','Anulado']),
        ], 'VIA', 'Viajes, producción, tarifa y trazabilidad operacional.'),

    'trip-events' => $module('Eventos de viaje', 'Evento', 'Operaciones', 'operations', 'bi-stopwatch', 'trip_events',
        ['trip_id'=>'Viaje','event_type'=>'Tipo','started_at'=>'Inicio','ended_at'=>'Término','duration_minutes'=>'Minutos'], [
            'trip_id'=>$relation('Viaje','trips','code'), 'event_type'=>['label'=>'Tipo','type'=>'select','required'=>true,'options'=>array_combine(
                ['Conducción','Carga','Descarga','Espera','Colación','Detención'],['Conducción','Carga','Descarga','Espera','Colación','Detención'])],
            'started_at'=>['label'=>'Inicio','type'=>'datetime-local','required'=>true], 'ended_at'=>['label'=>'Término','type'=>'datetime-local'],
            'duration_minutes'=>['label'=>'Duración (min)','type'=>'number'], 'notes'=>['label'=>'Observación','type'=>'textarea'],
        ], 'EVT', 'Eventos de inicio y término para calcular tiempos operativos.'),

    'dispatch-guides' => $module('Guías de despacho', 'Guía', 'Operaciones', 'operations', 'bi-journal-text', 'dispatch_guides',
        ['code'=>'Guía','trip_id'=>'Viaje','issue_date'=>'Fecha','quantity'=>'Cantidad','status'=>'Estado'], [
            'code'=>['label'=>'Número de guía','required'=>true], 'trip_id'=>$relation('Viaje','trips','code'),
            'issue_date'=>['label'=>'Fecha','type'=>'date','required'=>true], 'quantity'=>['label'=>'Cantidad','type'=>'number','step'=>'0.001'],
            'unit'=>['label'=>'Unidad','default'=>'ton'], 'received_by'=>['label'=>'Recepción conforme'], 'status'=>$status(['Pendiente','Emitida','Recibida','Observada','Anulada']),
        ], 'GUI', 'Guías, cantidades y recepción conforme.'),

    'settlements' => $module('Liquidaciones', 'Liquidación', 'Operaciones', 'billing', 'bi-calculator', 'service_settlements',
        ['code'=>'Liquidación','client_id'=>'Cliente','period'=>'Período','total'=>'Total','status'=>'Estado'], [
            'code'=>['label'=>'Folio'], 'client_id'=>$relation('Cliente','clients','business_name'), 'period'=>['label'=>'Período','type'=>'month','required'=>true],
            'net_amount'=>['label'=>'Neto','type'=>'number','step'=>'0.01','required'=>true], 'extras_amount'=>['label'=>'Adicionales','type'=>'number','step'=>'0.01'],
            'total'=>['label'=>'Total','type'=>'number','step'=>'0.01'], 'status'=>$status(['Borrador','Preparada','Aprobada','Observada','Facturada']),
        ], 'LIQ', 'Consolidación de viajes, horas, esperas y adicionales.'),

    'owners' => $module('Propietarios de equipos', 'Propietario', 'Flota y Taller', 'fleet', 'bi-person-badge', 'owners',
        ['code'=>'Código','rut'=>'RUT','name'=>'Propietario','phone'=>'Teléfono','active'=>'Activo'], [
            'code'=>['label'=>'Código'], 'rut'=>['label'=>'RUT','required'=>true], 'name'=>['label'=>'Nombre o razón social','required'=>true],
            'email'=>['label'=>'Correo','type'=>'email'], 'phone'=>['label'=>'Teléfono'], 'bank_details'=>['label'=>'Datos de pago','type'=>'textarea'],
            'active'=>['label'=>'Activo','type'=>'checkbox','default'=>1],
        ], 'PRP', 'Propietarios, arrendadores y responsables contractuales.'),

    'equipment' => $module('Flota y equipos', 'Equipo', 'Flota y Taller', 'fleet', 'bi-truck-front', 'equipment',
        ['code'=>'Código','name'=>'Equipo','equipment_type'=>'Tipo','ownership_type'=>'Propiedad','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'name'=>['label'=>'Nombre / identificación','required'=>true],
            'equipment_type'=>['label'=>'Tipo','type'=>'select','required'=>true,'options'=>array_combine(
                ['Tracto','Camión rígido','Batea','Aljibe','Rampla','Tolva','Cama baja','Camioneta','Van','Otro'],
                ['Tracto','Camión rígido','Batea','Aljibe','Rampla','Tolva','Cama baja','Camioneta','Van','Otro'])],
            'plate'=>['label'=>'Patente'], 'brand'=>['label'=>'Marca'], 'model'=>['label'=>'Modelo'], 'year'=>['label'=>'Año','type'=>'number'],
            'owner_id'=>$relation('Propietario','owners','name',false),
            'ownership_type'=>['label'=>'Propiedad','type'=>'select','options'=>array_combine(['Propio','Arrendado','Leasing','Comodato','Otro'],['Propio','Arrendado','Leasing','Comodato','Otro'])],
            'status'=>$status(['Disponible','Asignado','En taller','Fuera de servicio','Vendido']),
        ], 'EQU', 'Equipos propios, arrendados, leasing y comodato.'),

    'rental-contracts' => $module('Contratos de arriendo', 'Contrato de arriendo', 'Flota y Taller', 'fleet', 'bi-file-earmark-lock', 'rental_contracts',
        ['code'=>'Contrato','owner_id'=>'Propietario','equipment_id'=>'Equipo','start_date'=>'Inicio','monthly_amount'=>'Renta','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'owner_id'=>$relation('Propietario','owners','name'), 'equipment_id'=>$relation('Equipo','equipment','name'),
            'start_date'=>['label'=>'Inicio','type'=>'date','required'=>true], 'end_date'=>['label'=>'Término','type'=>'date'],
            'monthly_amount'=>['label'=>'Renta mensual','type'=>'number','step'=>'0.01','required'=>true],
            'maintenance_responsible'=>['label'=>'Responsable de mantenimiento'], 'insurance_responsible'=>['label'=>'Responsable de seguro'],
            'status'=>$status(['Borrador','Vigente','Suspendido','Finalizado']),
        ], 'ARR', 'Vigencias, renta, responsabilidades y condiciones de equipos externos.'),

    'workshops' => $module('Talleres', 'Taller', 'Flota y Taller', 'workshop', 'bi-tools', 'workshops',
        ['code'=>'Código','name'=>'Taller','workshop_type'=>'Tipo','phone'=>'Teléfono','active'=>'Activo'], [
            'code'=>['label'=>'Código'], 'name'=>['label'=>'Nombre','required'=>true],
            'workshop_type'=>['label'=>'Tipo','type'=>'select','options'=>array_combine(['Interno','Concesionario','Comercial','Propietario','Servicio móvil'],['Interno','Concesionario','Comercial','Propietario','Servicio móvil'])],
            'supplier_id'=>$relation('Proveedor','suppliers','business_name',false), 'phone'=>['label'=>'Teléfono'], 'active'=>['label'=>'Activo','type'=>'checkbox','default'=>1],
        ], 'TAL', 'Talleres internos, externos, de marca y móviles.'),

    'failure-reports' => $module('Avisos de falla', 'Aviso de falla', 'Flota y Taller', 'workshop', 'bi-exclamation-triangle', 'failure_reports',
        ['code'=>'Aviso','equipment_id'=>'Equipo','reported_at'=>'Fecha','severity'=>'Severidad','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'equipment_id'=>$relation('Equipo','equipment','name'), 'reported_at'=>['label'=>'Fecha','type'=>'datetime-local','required'=>true],
            'description'=>['label'=>'Descripción','type'=>'textarea','required'=>true], 'severity'=>['label'=>'Severidad','type'=>'select','options'=>['Baja'=>'Baja','Media'=>'Media','Alta'=>'Alta','Crítica'=>'Crítica']],
            'status'=>$status(['Abierta','Diagnosticada','Derivada','Cerrada']),
        ], 'FAL', 'Novedades, fallas críticas y derivación a orden de trabajo.'),

    'work-orders' => $module('Órdenes de trabajo', 'Orden de trabajo', 'Flota y Taller', 'workshop', 'bi-wrench-adjustable', 'work_orders',
        ['code'=>'OT','equipment_id'=>'Equipo','workshop_id'=>'Taller','opened_at'=>'Apertura','estimated_cost'=>'Presupuesto','status'=>'Estado'], [
            'code'=>['label'=>'Número OT'], 'equipment_id'=>$relation('Equipo','equipment','name'), 'workshop_id'=>$relation('Taller','workshops','name',false),
            'opened_at'=>['label'=>'Apertura','type'=>'date','required'=>true], 'diagnosis'=>['label'=>'Diagnóstico','type'=>'textarea'],
            'estimated_cost'=>['label'=>'Costo estimado','type'=>'number','step'=>'0.01'], 'actual_cost'=>['label'=>'Costo real','type'=>'number','step'=>'0.01'],
            'payment_responsible'=>['label'=>'Responsable de pago','type'=>'select','options'=>array_combine(['BGV','Propietario','Arrendador','Garantía','Seguro','Cliente','Compartido'],['BGV','Propietario','Arrendador','Garantía','Seguro','Cliente','Compartido'])],
            'status'=>$status(['Abierta','Diagnosticada','Cotizada','Aprobada','En reparación','Terminada','Cerrada']),
        ], 'OT', 'Diagnóstico, aprobación, reparación, costo y cierre técnico.'),

    'fuel' => $module('Combustible', 'Registro de combustible', 'Flota y Taller', 'fuel', 'bi-fuel-pump', 'fuel_records',
        ['code'=>'Registro','equipment_id'=>'Equipo','recorded_at'=>'Fecha','liters'=>'Litros','total_cost'=>'Costo','efficiency'=>'Rendimiento'], [
            'code'=>['label'=>'Código'], 'equipment_id'=>$relation('Equipo','equipment','name'), 'worker_id'=>$relation('Conductor','workers','full_name',false),
            'recorded_at'=>['label'=>'Fecha','type'=>'datetime-local','required'=>true], 'odometer'=>['label'=>'Kilometraje','type'=>'number'],
            'liters'=>['label'=>'Litros','type'=>'number','step'=>'0.001','required'=>true], 'unit_cost'=>['label'=>'Costo/L','type'=>'number','step'=>'0.01'],
            'total_cost'=>['label'=>'Costo total','type'=>'number','step'=>'0.01'], 'tax_credit'=>['label'=>'IVA recuperable','type'=>'number','step'=>'0.01'],
            'specific_tax'=>['label'=>'Impuesto específico','type'=>'number','step'=>'0.01'], 'efficiency'=>['label'=>'Km/L','type'=>'number','step'=>'0.001'],
        ], 'COM', 'Compras, cargas, rendimiento, IVA, impuesto y emisiones.'),

    'purchase-requests' => $module('Solicitudes de compra', 'Solicitud', 'Compras', 'purchasing', 'bi-cart-plus', 'purchase_requests',
        ['code'=>'Solicitud','requested_at'=>'Fecha','requester_id'=>'Solicitante','estimated_amount'=>'Monto','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'requested_at'=>['label'=>'Fecha','type'=>'date','required'=>true],
            'requester_id'=>$relation('Solicitante','users','full_name'), 'cost_center_id'=>$relation('Centro de costo','cost_centers','name'),
            'description'=>['label'=>'Descripción','type'=>'textarea','required'=>true], 'estimated_amount'=>['label'=>'Monto estimado','type'=>'number','step'=>'0.01'],
            'priority'=>['label'=>'Prioridad','type'=>'select','options'=>['Baja'=>'Baja','Normal'=>'Normal','Alta'=>'Alta','Urgente'=>'Urgente']],
            'status'=>$status(['Borrador','En aprobación','Aprobada','Rechazada','Comprada','Anulada']),
        ], 'SOL', 'Solicitud, aprobación y trazabilidad de abastecimiento.'),

    'supplier-orders' => $module('OC de proveedores', 'OC proveedor', 'Compras', 'purchasing', 'bi-cart-check', 'supplier_purchase_orders',
        ['code'=>'OC','supplier_id'=>'Proveedor','issue_date'=>'Emisión','total'=>'Total','status'=>'Estado'], [
            'code'=>['label'=>'Número OC'], 'supplier_id'=>$relation('Proveedor','suppliers','business_name'),
            'request_id'=>$relation('Solicitud','purchase_requests','code',false), 'issue_date'=>['label'=>'Emisión','type'=>'date','required'=>true],
            'description'=>['label'=>'Descripción','type'=>'textarea'], 'net_amount'=>['label'=>'Neto','type'=>'number','step'=>'0.01'],
            'tax_amount'=>['label'=>'IVA','type'=>'number','step'=>'0.01'], 'total'=>['label'=>'Total','type'=>'number','step'=>'0.01'],
            'status'=>$status(['Borrador','Emitida','Aceptada','Recepcionada','Cerrada','Anulada']),
        ], 'OCP', 'Orden, recepción y control de compras a proveedores.'),

    'supplier-invoices' => $module('Facturas de proveedores', 'Factura proveedor', 'Compras', 'purchasing', 'bi-receipt-cutoff', 'supplier_invoices',
        ['folio'=>'Folio','supplier_id'=>'Proveedor','issue_date'=>'Emisión','due_date'=>'Vencimiento','total'=>'Total','status'=>'Estado'], [
            'folio'=>['label'=>'Folio','required'=>true], 'supplier_id'=>$relation('Proveedor','suppliers','business_name'),
            'purchase_order_id'=>$relation('OC proveedor','supplier_purchase_orders','code',false), 'issue_date'=>['label'=>'Emisión','type'=>'date','required'=>true],
            'due_date'=>['label'=>'Vencimiento','type'=>'date'], 'net_amount'=>['label'=>'Neto','type'=>'number','step'=>'0.01'],
            'tax_amount'=>['label'=>'IVA','type'=>'number','step'=>'0.01'], 'total'=>['label'=>'Total','type'=>'number','step'=>'0.01','required'=>true],
            'status'=>$status(['Pendiente','Parcial','Pagada','Vencida','Observada','Anulada']),
        ], 'FPR', 'Recepción, cuenta por pagar, pago y conciliación.'),

    'customer-invoices' => $module('Facturación', 'Factura cliente', 'Facturación y Finanzas', 'billing', 'bi-file-earmark-spreadsheet', 'customer_invoices',
        ['code'=>'Factura','client_id'=>'Cliente','issue_date'=>'Emisión','due_date'=>'Vencimiento','total'=>'Total','balance'=>'Saldo','status'=>'Estado'], [
            'code'=>['label'=>'Folio'], 'client_id'=>$relation('Cliente','clients','business_name'),
            'settlement_id'=>$relation('Liquidación','service_settlements','code',false), 'client_order_id'=>$relation('OC cliente','client_purchase_orders','code',false),
            'hes_id'=>$relation('HES','hes_records','code',false), 'issue_date'=>['label'=>'Emisión','type'=>'date','required'=>true],
            'due_date'=>['label'=>'Vencimiento','type'=>'date','required'=>true], 'payment_condition'=>['label'=>'Condición de pago','required'=>true],
            'net_amount'=>['label'=>'Neto','type'=>'number','step'=>'0.01','required'=>true], 'tax_amount'=>['label'=>'IVA','type'=>'number','step'=>'0.01'],
            'total'=>['label'=>'Total','type'=>'number','step'=>'0.01','required'=>true], 'balance'=>['label'=>'Saldo','type'=>'number','step'=>'0.01'],
            'status'=>$status(['Borrador','Emitida','Aceptada','Observada','Anulada']),
        ], 'FAC', 'Facturación, OC/HES, vencimiento, saldo y cobranza.'),

    'payment-statements' => $module('Estados de pago', 'Estado de pago', 'Facturación y Finanzas', 'billing', 'bi-cash-stack', 'payment_statements',
        ['code'=>'Estado','client_id'=>'Cliente','period'=>'Período','total'=>'Total','balance'=>'Saldo','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'client_id'=>$relation('Cliente','clients','business_name'), 'period'=>['label'=>'Período','type'=>'month','required'=>true],
            'total'=>['label'=>'Total','type'=>'number','step'=>'0.01','required'=>true], 'paid_amount'=>['label'=>'Abonos','type'=>'number','step'=>'0.01'],
            'retention_amount'=>['label'=>'Retenciones','type'=>'number','step'=>'0.01'], 'balance'=>['label'=>'Saldo','type'=>'number','step'=>'0.01'],
            'due_date'=>['label'=>'Fecha de pago','type'=>'date'], 'status'=>$status(['Pendiente','Parcial','Pagado','Vencido','En cobranza']),
        ], 'EDP', 'Aprobación, abonos, retenciones y saldos.'),

    'collections' => $module('Cobranza', 'Gestión de cobro', 'Facturación y Finanzas', 'billing', 'bi-telephone-outbound', 'collections',
        ['code'=>'Gestión','invoice_id'=>'Factura','contacted_at'=>'Fecha','commitment_date'=>'Compromiso','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'invoice_id'=>$relation('Factura','customer_invoices','code'),
            'contacted_at'=>['label'=>'Fecha de gestión','type'=>'datetime-local','required'=>true], 'contact_method'=>['label'=>'Medio'],
            'commitment_date'=>['label'=>'Compromiso de pago','type'=>'date'], 'notes'=>['label'=>'Resultado','type'=>'textarea','required'=>true],
            'status'=>$status(['Abierta','En seguimiento','Compromiso','Pagada','Incobrable','Cerrada']),
        ], 'COB', 'Alertas, compromisos y seguimiento de saldos.'),

    'financial-movements' => $module('Movimientos financieros', 'Movimiento', 'Facturación y Finanzas', 'finance', 'bi-arrow-left-right', 'financial_movements',
        ['code'=>'Código','movement_date'=>'Fecha','movement_type'=>'Tipo','category'=>'Categoría','amount'=>'Monto','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'movement_date'=>['label'=>'Fecha','type'=>'date','required'=>true],
            'movement_type'=>['label'=>'Tipo','type'=>'select','required'=>true,'options'=>['Ingreso'=>'Ingreso','Costo'=>'Costo','Gasto'=>'Gasto','Inversión'=>'Inversión']],
            'category'=>['label'=>'Categoría','required'=>true], 'cost_center_id'=>$relation('Centro de costo','cost_centers','name'),
            'client_id'=>$relation('Cliente','clients','business_name',false), 'equipment_id'=>$relation('Equipo','equipment','name',false),
            'amount'=>['label'=>'Monto','type'=>'number','step'=>'0.01','required'=>true], 'description'=>['label'=>'Descripción','type'=>'textarea'],
            'status'=>$status(['Borrador','Confirmado','Conciliado','Anulado']),
        ], 'MOV', 'Ingresos, costos, gastos e inversiones con dimensiones analíticas.'),

    'budgets' => $module('Presupuestos', 'Presupuesto', 'Facturación y Finanzas', 'finance', 'bi-pie-chart', 'budgets',
        ['code'=>'Código','period'=>'Período','cost_center_id'=>'Centro de costo','budget_amount'=>'Presupuesto','actual_amount'=>'Real','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'period'=>['label'=>'Período','type'=>'month','required'=>true],
            'cost_center_id'=>$relation('Centro de costo','cost_centers','name'), 'client_id'=>$relation('Cliente','clients','business_name',false),
            'budget_amount'=>['label'=>'Presupuesto','type'=>'number','step'=>'0.01','required'=>true], 'actual_amount'=>['label'=>'Real','type'=>'number','step'=>'0.01'],
            'notes'=>['label'=>'Observaciones','type'=>'textarea'], 'status'=>$status(['Borrador','Aprobado','Cerrado']),
        ], 'PRE', 'Presupuesto mensual/anual y comparación real.'),

    'workers' => $module('Trabajadores', 'Trabajador', 'RR.HH.', 'hr', 'bi-person-vcard', 'workers',
        ['code'=>'Código','rut'=>'RUT','full_name'=>'Nombre','position'=>'Cargo','shift_id'=>'Turno','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'rut'=>['label'=>'RUT','required'=>true], 'full_name'=>['label'=>'Nombre completo','required'=>true],
            'position'=>['label'=>'Cargo','required'=>true], 'area'=>['label'=>'Área'], 'cost_center_id'=>$relation('Centro de costo','cost_centers','name',false),
            'shift_id'=>$relation('Turno','shifts','name',false), 'email'=>['label'=>'Correo','type'=>'email'], 'phone'=>['label'=>'Teléfono'],
            'hire_date'=>['label'=>'Ingreso','type'=>'date'], 'contract_type'=>['label'=>'Tipo de contrato'], 'contract_end_date'=>['label'=>'Término de contrato','type'=>'date'],
            'pension_fund'=>['label'=>'AFP'], 'health_provider'=>['label'=>'Salud'], 'bank_name'=>['label'=>'Banco'],
            'bank_account'=>['label'=>'Cuenta bancaria'], 'base_salary'=>['label'=>'Sueldo base','type'=>'number','step'=>'0.01'],
            'status'=>$status(['Activo','Licencia','Vacaciones','Suspendido','Desvinculado']),
        ], 'TRA', 'Ficha laboral, contrato, previsión, banco y turno.'),

    'shifts' => $module('Turnos', 'Turno', 'RR.HH.', 'hr', 'bi-calendar-week', 'shifts',
        ['code'=>'Código','name'=>'Turno','cycle_type'=>'Ciclo','weekly_hours'=>'Horas','active'=>'Activo'], [
            'code'=>['label'=>'Código'], 'name'=>['label'=>'Nombre','required'=>true],
            'cycle_type'=>['label'=>'Ciclo','type'=>'select','options'=>array_combine(['5x2','6x1','7x7','14x14','Personalizado'],['5x2','6x1','7x7','14x14','Personalizado'])],
            'weekly_hours'=>['label'=>'Horas semanales','type'=>'number','step'=>'0.01'], 'start_time'=>['label'=>'Hora inicio','type'=>'time'],
            'end_time'=>['label'=>'Hora término','type'=>'time'], 'break_minutes'=>['label'=>'Colación (min)','type'=>'number'],
            'active'=>['label'=>'Activo','type'=>'checkbox','default'=>1],
        ], 'TUR', 'Ciclos parametrizables, descansos y jornadas excepcionales.'),

    'attendance' => $module('Asistencia', 'Registro de asistencia', 'RR.HH.', 'hr', 'bi-calendar-check', 'attendance',
        ['code'=>'Registro','worker_id'=>'Trabajador','attendance_date'=>'Fecha','regular_hours'=>'Ordinarias','overtime_hours'=>'Extra','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'worker_id'=>$relation('Trabajador','workers','full_name'), 'workday_id'=>$relation('Jornada','workdays','code',false),
            'attendance_date'=>['label'=>'Fecha','type'=>'date','required'=>true], 'check_in'=>['label'=>'Entrada','type'=>'time'], 'check_out'=>['label'=>'Salida','type'=>'time'],
            'regular_hours'=>['label'=>'Horas ordinarias','type'=>'number','step'=>'0.01'], 'overtime_hours'=>['label'=>'Horas extra','type'=>'number','step'=>'0.01'],
            'incident'=>['label'=>'Incidencia'], 'status'=>$status(['Borrador','Calculada','Observada','Aprobada','Cerrada']),
        ], 'ASI', 'Asistencia alimentada por jornadas y cálculo de horas.'),

    'per-diems' => $module('Viáticos', 'Viático', 'RR.HH.', 'hr', 'bi-cup-hot', 'per_diems',
        ['code'=>'Código','worker_id'=>'Trabajador','expense_date'=>'Fecha','per_diem_type'=>'Tipo','amount'=>'Monto','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'worker_id'=>$relation('Trabajador','workers','full_name'), 'trip_id'=>$relation('Viaje','trips','code',false),
            'expense_date'=>['label'=>'Fecha','type'=>'date','required'=>true], 'per_diem_type'=>['label'=>'Tipo','type'=>'select','options'=>['Alimentación'=>'Alimentación','Alojamiento'=>'Alojamiento','Traslado'=>'Traslado','Otro'=>'Otro']],
            'amount'=>['label'=>'Monto','type'=>'number','step'=>'0.01','required'=>true], 'notes'=>['label'=>'Observaciones','type'=>'textarea'],
            'status'=>$status(['Borrador','Presentado','Aprobado','Rechazado','Pagado']),
        ], 'VIT', 'Viáticos vinculados a trabajador, viaje, aprobación y remuneración.'),

    'payrolls' => $module('Remuneraciones', 'Remuneración', 'RR.HH.', 'hr', 'bi-wallet2', 'payrolls',
        ['code'=>'Liquidación','worker_id'=>'Trabajador','period'=>'Período','gross_amount'=>'Haberes','net_amount'=>'Líquido','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'worker_id'=>$relation('Trabajador','workers','full_name'), 'period'=>['label'=>'Período','type'=>'month','required'=>true],
            'base_salary'=>['label'=>'Sueldo base','type'=>'number','step'=>'0.01'], 'overtime_amount'=>['label'=>'Horas extra','type'=>'number','step'=>'0.01'],
            'bonuses'=>['label'=>'Bonos','type'=>'number','step'=>'0.01'], 'deductions'=>['label'=>'Descuentos','type'=>'number','step'=>'0.01'],
            'gross_amount'=>['label'=>'Total haberes','type'=>'number','step'=>'0.01'], 'net_amount'=>['label'=>'Líquido','type'=>'number','step'=>'0.01'],
            'status'=>$status(['Borrador','Calculada','Revisada','Aprobada','Pagada','Anulada']),
        ], 'REM', 'Haberes, descuentos, imposiciones y costo laboral.'),

    'incidents' => $module('Incidentes', 'Incidente', 'HSE y Ambiente', 'hse', 'bi-shield-exclamation', 'incidents',
        ['code'=>'Incidente','occurred_at'=>'Fecha','incident_type'=>'Tipo','severity'=>'Severidad','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'occurred_at'=>['label'=>'Fecha','type'=>'datetime-local','required'=>true],
            'incident_type'=>['label'=>'Tipo','type'=>'select','options'=>array_combine(['Accidente','Incidente','Cuasi accidente','Hallazgo'],['Accidente','Incidente','Cuasi accidente','Hallazgo'])],
            'site_id'=>$relation('Faena','sites','name',false), 'worker_id'=>$relation('Trabajador','workers','full_name',false),
            'description'=>['label'=>'Descripción','type'=>'textarea','required'=>true], 'severity'=>['label'=>'Severidad','type'=>'select','options'=>['Baja'=>'Baja','Media'=>'Media','Alta'=>'Alta','Crítica'=>'Crítica']],
            'status'=>$status(['Abierto','En investigación','Con acciones','Verificado','Cerrado']),
        ], 'INC', 'Accidentes, incidentes, investigación y acciones.'),

    'inspections' => $module('Inspecciones', 'Inspección', 'HSE y Ambiente', 'hse', 'bi-clipboard2-check', 'inspections',
        ['code'=>'Inspección','inspection_date'=>'Fecha','site_id'=>'Faena','result'=>'Resultado','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'inspection_date'=>['label'=>'Fecha','type'=>'date','required'=>true],
            'site_id'=>$relation('Faena','sites','name',false), 'equipment_id'=>$relation('Equipo','equipment','name',false),
            'checklist_type'=>['label'=>'Tipo de checklist','required'=>true], 'result'=>['label'=>'Resultado','type'=>'select','options'=>['Conforme'=>'Conforme','Con observaciones'=>'Con observaciones','No conforme'=>'No conforme']],
            'findings'=>['label'=>'Hallazgos','type'=>'textarea'], 'status'=>$status(['Planificada','Ejecutada','Con acciones','Cerrada']),
        ], 'INS', 'Checklists, hallazgos y cumplimiento operacional.'),

    'corrective-actions' => $module('Acciones correctivas', 'Acción correctiva', 'HSE y Ambiente', 'hse', 'bi-check2-square', 'corrective_actions',
        ['code'=>'Acción','source_type'=>'Origen','responsible_id'=>'Responsable','due_date'=>'Compromiso','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'source_type'=>['label'=>'Origen','required'=>true], 'source_id'=>['label'=>'ID de origen'],
            'description'=>['label'=>'Acción','type'=>'textarea','required'=>true], 'responsible_id'=>$relation('Responsable','users','full_name'),
            'due_date'=>['label'=>'Fecha compromiso','type'=>'date','required'=>true], 'verification_notes'=>['label'=>'Verificación','type'=>'textarea'],
            'status'=>$status(['Abierta','En proceso','Vencida','Verificada','Cerrada']),
        ], 'ACC', 'Responsables, compromisos, evidencias y verificación.'),

    'environmental-events' => $module('Gestión ambiental', 'Evento ambiental', 'HSE y Ambiente', 'environment', 'bi-tree', 'environmental_events',
        ['code'=>'Evento','event_date'=>'Fecha','event_type'=>'Tipo','quantity'=>'Cantidad','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'event_date'=>['label'=>'Fecha','type'=>'date','required'=>true],
            'event_type'=>['label'=>'Tipo','type'=>'select','options'=>array_combine(['Residuo','Derrame','Emisión','Agua','Aceite','Filtro','Neumático'],['Residuo','Derrame','Emisión','Agua','Aceite','Filtro','Neumático'])],
            'site_id'=>$relation('Faena','sites','name',false), 'quantity'=>['label'=>'Cantidad','type'=>'number','step'=>'0.001'], 'unit'=>['label'=>'Unidad'],
            'description'=>['label'=>'Descripción','type'=>'textarea'], 'disposition'=>['label'=>'Disposición / cierre','type'=>'textarea'],
            'status'=>$status(['Abierto','Clasificado','Gestionado','Evidenciado','Cerrado']),
        ], 'AMB', 'Residuos, derrames, emisiones, medición y disposición.'),

    'accreditations' => $module('Acreditaciones', 'Acreditación', 'HSE y Ambiente', 'hse', 'bi-patch-check', 'accreditations',
        ['code'=>'Código','subject_type'=>'Tipo','subject_name'=>'Sujeto','expires_at'=>'Vencimiento','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'subject_type'=>['label'=>'Tipo','type'=>'select','options'=>array_combine(['Persona','Vehículo','Equipo','Empresa'],['Persona','Vehículo','Equipo','Empresa'])],
            'subject_name'=>['label'=>'Persona o activo','required'=>true], 'accreditation_type'=>['label'=>'Acreditación','required'=>true],
            'issued_at'=>['label'=>'Emisión','type'=>'date'], 'expires_at'=>['label'=>'Vencimiento','type'=>'date'],
            'status'=>$status(['Pendiente','Vigente','Observada','Vencida','Rechazada']),
        ], 'ACR', 'Personas, equipos, documentos y vencimientos.'),

    'documents' => $module('Gestión documental', 'Documento', 'Documentos y Control', 'documents', 'bi-folder2-open', 'documents',
        ['code'=>'Código','document_type'=>'Tipo','subject_type'=>'Entidad','expires_at'=>'Vencimiento','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'document_type'=>['label'=>'Tipo de documento','required'=>true],
            'subject_type'=>['label'=>'Entidad','type'=>'select','options'=>array_combine(['Cliente','Contrato','Equipo','Trabajador','Taller','Incidente','Ambiente'],['Cliente','Contrato','Equipo','Trabajador','Taller','Incidente','Ambiente'])],
            'subject_id'=>['label'=>'ID relacionado'], 'file_path'=>['label'=>'Archivo','type'=>'file'],
            'issued_at'=>['label'=>'Emisión','type'=>'date'], 'expires_at'=>['label'=>'Vencimiento','type'=>'date'],
            'notes'=>['label'=>'Observaciones','type'=>'textarea'], 'status'=>$status(['Pendiente','Recibido','Validado','Rechazado','Vencido','No aplica']),
        ], 'DOC', 'Contratos, permisos, certificados, fotografías y evidencias.'),

    'alerts' => $module('Alertas', 'Alerta', 'Documentos y Control', 'documents', 'bi-bell', 'alerts',
        ['code'=>'Alerta','alert_type'=>'Tipo','severity'=>'Severidad','due_at'=>'Vencimiento','status'=>'Estado'], [
            'code'=>['label'=>'Código'], 'alert_type'=>['label'=>'Tipo','required'=>true], 'title'=>['label'=>'Título','required'=>true],
            'message'=>['label'=>'Detalle','type'=>'textarea','required'=>true], 'severity'=>['label'=>'Severidad','type'=>'select','options'=>['Info'=>'Info','Advertencia'=>'Advertencia','Alta'=>'Alta','Crítica'=>'Crítica']],
            'due_at'=>['label'=>'Vencimiento','type'=>'datetime-local'], 'assigned_to'=>$relation('Responsable','users','full_name',false),
            'status'=>$status(['Abierta','En gestión','Resuelta','Descartada']),
        ], 'ALE', 'Vencimientos, faltantes, desviaciones y riesgos críticos.'),
];
