
INSERT OR IGNORE INTO users(id,username,full_name,role) VALUES
(1,'maestro','Usuario Maestro','Maestro'),
(2,'gerencia','Usuario Gerencial','Gerencial'),
(3,'operaciones','Supervisor Operativo','Supervisor Operativo'),
(4,'administracion','Usuario Administrativo','Administrativo'),
(5,'taller','Supervisor de Taller','Supervisor Taller'),
(6,'conductor','Juan Pérez','Conductor');

INSERT OR IGNORE INTO clients(id,code,rut,business_name,payment_condition,requires_oc,requires_hes,status) VALUES
(1,'CLI-000001','76.111.111-1','Minera Norte SpA','Transferencia 30 días','Obligatorio','Obligatorio','Activo'),
(2,'CLI-000002','77.222.222-2','Servicios Andinos Ltda.','Transferencia 15 días','Opcional','Opcional','Activo');

INSERT OR IGNORE INTO owners(id,name,owner_type) VALUES
(1,'BGV Servicios Integrales SpA','Propio'),
(2,'Transportes del Norte SpA','Arrendador');

INSERT OR IGNORE INTO assets(id,code,asset_type,plate,ownership,owner_id,operational_status) VALUES
(1,'TRC-012','Tracto','ABCD12','Propio',1,'Disponible'),
(2,'BAT-008','Batea','EFGH34','Arrendado',2,'Disponible'),
(3,'TRC-021','Tracto','IJKL56','Propio',1,'Taller'),
(4,'ALJ-003','Aljibe','MNOP78','Arrendado',2,'Disponible');

INSERT OR IGNORE INTO workers(id,user_id,rut,full_name,position,shift,status) VALUES
(1,6,'15.555.555-5','Juan Pérez','Conductor','7x7','Activo'),
(2,NULL,'16.666.666-6','María Soto','Conductor','7x7','Activo'),
(3,NULL,'17.777.777-7','Carlos Díaz','Conductor','6x1','Activo');

INSERT OR IGNORE INTO payment_conditions(id,name,days,payment_method) VALUES(1,'Transferencia inmediata',0,'Transferencia'),(2,'Transferencia 15 días',15,'Transferencia'),(3,'Transferencia 30 días',30,'Transferencia'),(4,'Efectivo',0,'Efectivo');
INSERT OR IGNORE INTO quotations(id,code,client_id,issue_date,service_type,rate_type,rate_value,quantity,net_amount,tax_amount,total_amount,status) VALUES(1,'COT-2026-000001',1,'2026-07-01','Transporte de mineral','Por tonelada',18500,3240,59940000,11388600,71328600,'Aceptada');
INSERT OR IGNORE INTO contracts(id,code,client_id,quotation_id,start_date,end_date,total_amount,available_balance,requires_oc,requires_hes,status) VALUES(1,'CON-2026-000014',1,1,'2026-08-01','2027-07-31',720000000,720000000,'Obligatorio','Obligatorio','Vigente');
INSERT OR IGNORE INTO client_purchase_orders(id,contract_id,number,issue_date,amount,available_balance,status) VALUES(1,1,'OC-45001984','2026-08-01',180000000,180000000,'Validada');
INSERT OR IGNORE INTO client_hes(id,contract_id,number,issue_date,amount,status) VALUES(1,1,'HES-883740','2026-08-04',44207500,'Validada');


INSERT OR IGNORE INTO cost_centers(id,code,name,area,status) VALUES
(1,'CC-OPER','Operaciones','Operaciones','Activo'),
(2,'CC-TALLER','Taller y Mantenciones','Taller','Activo'),
(3,'CC-ADM','Administración','Administración','Activo');

INSERT OR IGNORE INTO suppliers(id,code,rut,business_name,supplier_type,status) VALUES
(1,'PRO-000001','76.333.333-3','Combustibles Norte SpA','Combustible','Activo'),
(2,'PRO-000002','77.444.444-4','Servicio Técnico Marca','Taller de marca','Activo'),
(3,'PRO-000003','78.555.555-5','Taller Comercial Ruta 5','Taller comercial','Activo');

INSERT OR IGNORE INTO workshops(id,code,name,workshop_type,supplier_id,owner_id,specialty,status) VALUES
(1,'TAL-001','Taller Interno BGV','Interno',NULL,1,'Mantención general','Activo'),
(2,'TAL-002','Servicio Técnico Marca','Concesionario de marca',2,NULL,'Diagnóstico y garantía','Activo'),
(3,'TAL-003','Taller Comercial Ruta 5','Taller comercial',3,NULL,'Mecánica y electricidad','Activo'),
(4,'TAL-004','Taller del Arrendador','Taller del dueño del equipo',NULL,2,'Equipos arrendados','Activo');

INSERT OR IGNORE INTO rental_contracts(id,code,asset_id,owner_id,start_date,end_date,monthly_net,tax_amount,monthly_total,includes_maintenance,maintenance_responsible,parts_responsible,tires_responsible,workshop_id,status) VALUES
(1,'ARR-2026-000001',2,2,'2026-01-01','2026-12-31',3500000,665000,4165000,'Sí','Propietario/Arrendador','Propietario/Arrendador','Compartido',4,'Vigente'),
(2,'ARR-2026-000002',4,2,'2026-03-01','2027-02-28',2800000,532000,3332000,'Parcial','Compartido','BGV','BGV',4,'Vigente');

INSERT OR IGNORE INTO fuel_purchases(id,invoice_number,purchase_date,supplier_id,liters,net_amount,vat_amount,specific_tax_amount,recoverable_percentage,specific_tax_credit,total_amount,status) VALUES
(1,'F-102884','2026-08-01',1,5000,4750000,902500,640000,25,160000,6292500,'Registrada');

INSERT OR IGNORE INTO work_orders(id,code,asset_id,workshop_id,request_date,entry_time,maintenance_type,reason,diagnosis,requested_work,payment_responsible,warranty_status,quote_number,supplier_po_number,net_cost,tax_amount,total_cost,status) VALUES
(1,'OT-2026-000412',3,2,'2026-08-03','2026-08-03T10:20:00','Correctiva','Pérdida de potencia','Diagnóstico electrónico pendiente','Revisar sistema de inyección','Garantía de marca','En evaluación','P-8891','OC-PRO-0121',0,0,0,'Diagnóstico'),
(2,'OT-2026-000407',2,4,'2026-08-01','2026-08-01T08:10:00','Correctiva','Fisura estructural','Fisura en soporte posterior','Soldadura y control dimensional','Propietario del equipo','No','P-7774','',850000,161500,1011500,'En ejecución');


INSERT OR IGNORE INTO shift_patterns(id,code,name,work_days,rest_days,weekly_hours,daily_ordinary_hours,meal_minutes,exceptional_schedule,authorization_reference,status) VALUES
(1,'TUR-6X1','Turno 6x1',6,1,42,7,60,0,NULL,'Activo'),
(2,'TUR-7X7','Turno 7x7',7,7,42,12,60,1,'Configurar resolución aplicable','Activo'),
(3,'TUR-14X14','Turno 14x14',14,14,42,12,60,1,'Configurar resolución aplicable','Activo'),
(4,'TUR-5X2','Turno 5x2',5,2,42,8.4,60,0,NULL,'Activo');

INSERT OR IGNORE INTO worker_contracts(id,worker_id,start_date,contract_type,base_salary,gratification_type,shift_pattern_id,cost_center_id,bank_name,pension_institution,health_institution,status) VALUES
(1,1,'2026-01-01','Indefinido',950000,'Legal',2,1,'Banco Estado','AFP configurable','FONASA/ISAPRE configurable','Vigente'),
(2,2,'2026-02-01','Indefinido',950000,'Legal',2,1,'Banco Estado','AFP configurable','FONASA/ISAPRE configurable','Vigente'),
(3,3,'2026-03-01','Indefinido',900000,'Legal',1,1,'Banco Estado','AFP configurable','FONASA/ISAPRE configurable','Vigente');

INSERT OR IGNORE INTO payroll_parameters(id,effective_from,parameter_code,parameter_name,percentage,fixed_amount,status) VALUES
(1,'2026-01-01','PENSION_WORKER','Cotización previsional trabajador',10.00,NULL,'Vigente'),
(2,'2026-01-01','HEALTH_WORKER','Cotización salud trabajador',7.00,NULL,'Vigente'),
(3,'2026-01-01','UNEMPLOYMENT_WORKER','Seguro cesantía trabajador',0.60,NULL,'Vigente'),
(4,'2026-01-01','EMPLOYER_EXTRA','Costo patronal adicional estimado',3.00,NULL,'Vigente'),
(5,'2026-01-01','OVERTIME_FACTOR','Recargo hora extraordinaria',50.00,NULL,'Vigente');

INSERT OR IGNORE INTO payroll_periods(id,code,period_year,period_month,start_date,end_date,status) VALUES
(1,'REM-2026-08',2026,8,'2026-08-01','2026-08-31','Abierto');


INSERT OR IGNORE INTO ppe_items(id,code,name,useful_life_days,status) VALUES
(1,'EPP-001','Casco de seguridad',730,'Activo'),
(2,'EPP-002','Chaleco reflectante',365,'Activo'),
(3,'EPP-003','Guantes de trabajo',90,'Activo'),
(4,'EPP-004','Calzado de seguridad',365,'Activo'),
(5,'EPP-005','Lentes de seguridad',180,'Activo');

INSERT OR IGNORE INTO accreditation_requirements(id,client_id,contract_id,entity_type,requirement_name,mandatory,validity_days,active) VALUES
(1,1,1,'Trabajador','Licencia de conducir vigente',1,365,1),
(2,1,1,'Trabajador','Examen preocupacional',1,365,1),
(3,1,1,'Activo','Revisión técnica vigente',1,365,1),
(4,1,1,'Activo','Seguro obligatorio y póliza',1,365,1),
(5,1,1,'Empresa','Certificado de accidentabilidad',1,90,1);

INSERT OR IGNORE INTO safety_incidents(id,code,incident_date,incident_type,severity,worker_id,asset_id,location,description,immediate_action,status,reported_by,reported_at) VALUES
(1,'INC-2026-000018','2026-08-02','Cuasi accidente','Media',1,1,'Faena Norte','Desplazamiento inesperado durante espera de carga.','Detención del equipo y charla inmediata.','En investigación',3,'2026-08-02T11:20:00');

INSERT OR IGNORE INTO corrective_actions(id,source_type,source_id,action_code,description,responsible_user_id,due_date,status) VALUES
(1,'Incidente',1,'ACC-2026-000041','Actualizar procedimiento de inmovilización durante carga.',3,'2026-08-08','Pendiente'),
(2,'Inspección',1,'ACC-2026-000042','Reforzar revisión diaria de elementos reflectantes.',5,'2026-08-10','Pendiente');

INSERT OR IGNORE INTO environmental_events(id,code,event_date,event_type,asset_id,location,quantity,unit,description,immediate_action,status,reported_by) VALUES
(1,'AMB-2026-000006','2026-07-28','Derrame menor',3,'Taller externo',2.5,'L','Goteo de aceite hidráulico.','Uso de kit de derrame y disposición de absorbentes.','Cerrado',5);

INSERT OR IGNORE INTO waste_records(id,record_date,waste_type,hazardous,quantity,unit,storage_location,disposal_provider,manifest_number,status) VALUES
(1,'2026-08-01','Aceite usado',1,180,'L','Bodega residuos peligrosos','Gestor autorizado pendiente',NULL,'Pendiente retiro'),
(2,'2026-08-01','Neumáticos fuera de uso',0,18,'unidades','Patio taller','Valorizador autorizado','MAN-2026-118','Enviado a valorización');
