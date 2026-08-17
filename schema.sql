
PRAGMA foreign_keys=ON;

CREATE TABLE IF NOT EXISTS users(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 username TEXT UNIQUE NOT NULL,
 full_name TEXT NOT NULL,
 role TEXT NOT NULL,
 active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS clients(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 rut TEXT UNIQUE NOT NULL,
 business_name TEXT NOT NULL,
 payment_condition TEXT NOT NULL,
 requires_oc TEXT NOT NULL DEFAULT 'Opcional',
 requires_hes TEXT NOT NULL DEFAULT 'Opcional',
 status TEXT NOT NULL DEFAULT 'Activo'
);

CREATE TABLE IF NOT EXISTS owners(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 name TEXT NOT NULL,
 owner_type TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS assets(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 asset_type TEXT NOT NULL,
 plate TEXT,
 ownership TEXT NOT NULL,
 owner_id INTEGER,
 operational_status TEXT NOT NULL,
 FOREIGN KEY(owner_id) REFERENCES owners(id)
);

CREATE TABLE IF NOT EXISTS workers(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 user_id INTEGER,
 rut TEXT UNIQUE NOT NULL,
 full_name TEXT NOT NULL,
 position TEXT NOT NULL,
 shift TEXT,
 status TEXT NOT NULL DEFAULT 'Activo',
 FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS journeys(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 worker_id INTEGER NOT NULL,
 date TEXT NOT NULL,
 start_time TEXT NOT NULL,
 end_time TEXT,
 tractor_id INTEGER,
 trailer_id INTEGER,
 status TEXT NOT NULL,
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(tractor_id) REFERENCES assets(id),
 FOREIGN KEY(trailer_id) REFERENCES assets(id)
);

CREATE TABLE IF NOT EXISTS trips(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 journey_id INTEGER NOT NULL,
 trip_no INTEGER NOT NULL,
 client_id INTEGER NOT NULL,
 origin TEXT NOT NULL,
 destination TEXT NOT NULL,
 start_time TEXT NOT NULL,
 end_time TEXT,
 tons REAL DEFAULT 0,
 status TEXT NOT NULL,
 UNIQUE(journey_id,trip_no),
 FOREIGN KEY(journey_id) REFERENCES journeys(id),
 FOREIGN KEY(client_id) REFERENCES clients(id)
);

CREATE TABLE IF NOT EXISTS trip_events(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 trip_id INTEGER NOT NULL,
 event_type TEXT NOT NULL,
 start_time TEXT NOT NULL,
 end_time TEXT,
 duration_minutes INTEGER,
 notes TEXT,
 FOREIGN KEY(trip_id) REFERENCES trips(id)
);

CREATE TABLE IF NOT EXISTS payment_conditions(id INTEGER PRIMARY KEY,name TEXT UNIQUE NOT NULL,days INTEGER NOT NULL,payment_method TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS quotations(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT UNIQUE NOT NULL,client_id INTEGER NOT NULL,issue_date TEXT NOT NULL,service_type TEXT NOT NULL,rate_type TEXT NOT NULL,rate_value REAL NOT NULL,quantity REAL NOT NULL,net_amount REAL NOT NULL,tax_amount REAL NOT NULL,total_amount REAL NOT NULL,status TEXT NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id));
CREATE TABLE IF NOT EXISTS contracts(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT UNIQUE NOT NULL,client_id INTEGER NOT NULL,quotation_id INTEGER,start_date TEXT NOT NULL,end_date TEXT,total_amount REAL NOT NULL,available_balance REAL NOT NULL,requires_oc TEXT NOT NULL,requires_hes TEXT NOT NULL,status TEXT NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id),FOREIGN KEY(quotation_id) REFERENCES quotations(id));
CREATE TABLE IF NOT EXISTS client_purchase_orders(id INTEGER PRIMARY KEY AUTOINCREMENT,contract_id INTEGER NOT NULL,number TEXT NOT NULL,issue_date TEXT NOT NULL,amount REAL NOT NULL,available_balance REAL NOT NULL,status TEXT NOT NULL,UNIQUE(contract_id,number),FOREIGN KEY(contract_id) REFERENCES contracts(id));
CREATE TABLE IF NOT EXISTS client_hes(id INTEGER PRIMARY KEY AUTOINCREMENT,contract_id INTEGER NOT NULL,number TEXT NOT NULL,issue_date TEXT NOT NULL,amount REAL NOT NULL,status TEXT NOT NULL,UNIQUE(contract_id,number),FOREIGN KEY(contract_id) REFERENCES contracts(id));
CREATE TABLE IF NOT EXISTS service_settlements(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT UNIQUE NOT NULL,contract_id INTEGER NOT NULL,period_from TEXT NOT NULL,period_to TEXT NOT NULL,purchase_order_id INTEGER,hes_id INTEGER,net_amount REAL NOT NULL,tax_amount REAL NOT NULL,total_amount REAL NOT NULL,status TEXT NOT NULL,approved_by INTEGER,approved_at TEXT,FOREIGN KEY(contract_id) REFERENCES contracts(id),FOREIGN KEY(purchase_order_id) REFERENCES client_purchase_orders(id),FOREIGN KEY(hes_id) REFERENCES client_hes(id),FOREIGN KEY(approved_by) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS invoices(id INTEGER PRIMARY KEY AUTOINCREMENT,number TEXT UNIQUE NOT NULL,settlement_id INTEGER UNIQUE NOT NULL,issue_date TEXT NOT NULL,due_date TEXT NOT NULL,net_amount REAL NOT NULL,tax_amount REAL NOT NULL,total_amount REAL NOT NULL,payment_status TEXT NOT NULL,FOREIGN KEY(settlement_id) REFERENCES service_settlements(id));
CREATE TABLE IF NOT EXISTS accounts_receivable(id INTEGER PRIMARY KEY AUTOINCREMENT,invoice_id INTEGER UNIQUE NOT NULL,original_amount REAL NOT NULL,paid_amount REAL NOT NULL DEFAULT 0,balance REAL NOT NULL,due_date TEXT NOT NULL,status TEXT NOT NULL,FOREIGN KEY(invoice_id) REFERENCES invoices(id));
CREATE TABLE IF NOT EXISTS client_payments(id INTEGER PRIMARY KEY AUTOINCREMENT,account_receivable_id INTEGER NOT NULL,payment_date TEXT NOT NULL,amount REAL NOT NULL,payment_method TEXT NOT NULL,reference TEXT,FOREIGN KEY(account_receivable_id) REFERENCES accounts_receivable(id));


-- ============================================================
-- v0.5 COSTOS, COMBUSTIBLE, ARRIENDOS Y TALLER
-- ============================================================

CREATE TABLE IF NOT EXISTS cost_centers(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 name TEXT NOT NULL,
 area TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'Activo'
);

CREATE TABLE IF NOT EXISTS suppliers(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 rut TEXT UNIQUE,
 business_name TEXT NOT NULL,
 supplier_type TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'Activo'
);

CREATE TABLE IF NOT EXISTS rental_contracts(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 asset_id INTEGER NOT NULL,
 owner_id INTEGER NOT NULL,
 start_date TEXT NOT NULL,
 end_date TEXT,
 monthly_net REAL NOT NULL,
 tax_amount REAL NOT NULL,
 monthly_total REAL NOT NULL,
 includes_maintenance TEXT NOT NULL DEFAULT 'No',
 maintenance_responsible TEXT NOT NULL,
 parts_responsible TEXT NOT NULL,
 tires_responsible TEXT NOT NULL,
 workshop_id INTEGER,
 status TEXT NOT NULL DEFAULT 'Vigente',
 FOREIGN KEY(asset_id) REFERENCES assets(id),
 FOREIGN KEY(owner_id) REFERENCES owners(id),
 FOREIGN KEY(workshop_id) REFERENCES workshops(id)
);

CREATE TABLE IF NOT EXISTS workshops(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 name TEXT NOT NULL,
 workshop_type TEXT NOT NULL,
 supplier_id INTEGER,
 owner_id INTEGER,
 specialty TEXT,
 status TEXT NOT NULL DEFAULT 'Activo',
 FOREIGN KEY(supplier_id) REFERENCES suppliers(id),
 FOREIGN KEY(owner_id) REFERENCES owners(id)
);

CREATE TABLE IF NOT EXISTS work_orders(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 asset_id INTEGER NOT NULL,
 workshop_id INTEGER NOT NULL,
 request_date TEXT NOT NULL,
 entry_time TEXT,
 exit_time TEXT,
 maintenance_type TEXT NOT NULL,
 reason TEXT NOT NULL,
 diagnosis TEXT,
 requested_work TEXT,
 payment_responsible TEXT NOT NULL,
 warranty_status TEXT NOT NULL DEFAULT 'No',
 quote_number TEXT,
 supplier_po_number TEXT,
 net_cost REAL NOT NULL DEFAULT 0,
 tax_amount REAL NOT NULL DEFAULT 0,
 total_cost REAL NOT NULL DEFAULT 0,
 status TEXT NOT NULL DEFAULT 'Abierta',
 FOREIGN KEY(asset_id) REFERENCES assets(id),
 FOREIGN KEY(workshop_id) REFERENCES workshops(id)
);

CREATE TABLE IF NOT EXISTS fuel_purchases(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 invoice_number TEXT NOT NULL,
 purchase_date TEXT NOT NULL,
 supplier_id INTEGER NOT NULL,
 liters REAL NOT NULL,
 net_amount REAL NOT NULL,
 vat_amount REAL NOT NULL,
 specific_tax_amount REAL NOT NULL,
 recoverable_percentage REAL NOT NULL DEFAULT 0,
 specific_tax_credit REAL NOT NULL DEFAULT 0,
 total_amount REAL NOT NULL,
 status TEXT NOT NULL DEFAULT 'Registrada',
 UNIQUE(supplier_id, invoice_number),
 FOREIGN KEY(supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE IF NOT EXISTS fuel_loads(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 fuel_purchase_id INTEGER,
 load_time TEXT NOT NULL,
 asset_id INTEGER NOT NULL,
 worker_id INTEGER,
 journey_id INTEGER,
 trip_id INTEGER,
 liters REAL NOT NULL,
 odometer REAL,
 notes TEXT,
 FOREIGN KEY(fuel_purchase_id) REFERENCES fuel_purchases(id),
 FOREIGN KEY(asset_id) REFERENCES assets(id),
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(journey_id) REFERENCES journeys(id),
 FOREIGN KEY(trip_id) REFERENCES trips(id)
);

CREATE TABLE IF NOT EXISTS costs(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 cost_date TEXT NOT NULL,
 category TEXT NOT NULL,
 description TEXT NOT NULL,
 net_amount REAL NOT NULL,
 vat_credit REAL NOT NULL DEFAULT 0,
 specific_tax_credit REAL NOT NULL DEFAULT 0,
 total_amount REAL NOT NULL,
 payment_responsible TEXT NOT NULL DEFAULT 'BGV',
 cost_center_id INTEGER NOT NULL,
 client_id INTEGER,
 contract_id INTEGER,
 journey_id INTEGER,
 trip_id INTEGER,
 asset_id INTEGER,
 worker_id INTEGER,
 supplier_id INTEGER,
 work_order_id INTEGER,
 fuel_purchase_id INTEGER,
 rental_contract_id INTEGER,
 status TEXT NOT NULL DEFAULT 'Registrado',
 FOREIGN KEY(cost_center_id) REFERENCES cost_centers(id),
 FOREIGN KEY(client_id) REFERENCES clients(id),
 FOREIGN KEY(contract_id) REFERENCES contracts(id),
 FOREIGN KEY(journey_id) REFERENCES journeys(id),
 FOREIGN KEY(trip_id) REFERENCES trips(id),
 FOREIGN KEY(asset_id) REFERENCES assets(id),
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(supplier_id) REFERENCES suppliers(id),
 FOREIGN KEY(work_order_id) REFERENCES work_orders(id),
 FOREIGN KEY(fuel_purchase_id) REFERENCES fuel_purchases(id),
 FOREIGN KEY(rental_contract_id) REFERENCES rental_contracts(id)
);

CREATE INDEX IF NOT EXISTS idx_costs_date ON costs(cost_date);
CREATE INDEX IF NOT EXISTS idx_costs_asset ON costs(asset_id);
CREATE INDEX IF NOT EXISTS idx_costs_trip ON costs(trip_id);
CREATE INDEX IF NOT EXISTS idx_costs_contract ON costs(contract_id);
CREATE INDEX IF NOT EXISTS idx_fuel_loads_asset ON fuel_loads(asset_id);
CREATE INDEX IF NOT EXISTS idx_work_orders_asset ON work_orders(asset_id);


-- ============================================================
-- v0.6 RR.HH., ASISTENCIA Y REMUNERACIONES
-- ============================================================

CREATE TABLE IF NOT EXISTS shift_patterns(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 name TEXT NOT NULL,
 work_days INTEGER NOT NULL,
 rest_days INTEGER NOT NULL,
 weekly_hours REAL NOT NULL DEFAULT 42,
 daily_ordinary_hours REAL NOT NULL,
 meal_minutes INTEGER NOT NULL DEFAULT 60,
 exceptional_schedule INTEGER NOT NULL DEFAULT 0,
 authorization_reference TEXT,
 status TEXT NOT NULL DEFAULT 'Activo'
);

CREATE TABLE IF NOT EXISTS worker_contracts(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 worker_id INTEGER NOT NULL,
 start_date TEXT NOT NULL,
 end_date TEXT,
 contract_type TEXT NOT NULL,
 base_salary REAL NOT NULL,
 gratification_type TEXT NOT NULL DEFAULT 'Legal',
 shift_pattern_id INTEGER,
 cost_center_id INTEGER NOT NULL,
 bank_name TEXT,
 bank_account TEXT,
 pension_institution TEXT,
 health_institution TEXT,
 unemployment_insurance INTEGER NOT NULL DEFAULT 1,
 status TEXT NOT NULL DEFAULT 'Vigente',
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(shift_pattern_id) REFERENCES shift_patterns(id),
 FOREIGN KEY(cost_center_id) REFERENCES cost_centers(id)
);

CREATE TABLE IF NOT EXISTS attendance(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 worker_id INTEGER NOT NULL,
 journey_id INTEGER UNIQUE,
 attendance_date TEXT NOT NULL,
 shift_pattern_id INTEGER,
 entry_time TEXT,
 exit_time TEXT,
 gross_hours REAL NOT NULL DEFAULT 0,
 meal_hours REAL NOT NULL DEFAULT 0,
 net_hours REAL NOT NULL DEFAULT 0,
 ordinary_hours REAL NOT NULL DEFAULT 0,
 overtime_hours REAL NOT NULL DEFAULT 0,
 absence_type TEXT NOT NULL DEFAULT 'No aplica',
 validation_status TEXT NOT NULL DEFAULT 'Pendiente',
 supervisor_approved_by INTEGER,
 hr_validated_by INTEGER,
 notes TEXT,
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(journey_id) REFERENCES journeys(id),
 FOREIGN KEY(shift_pattern_id) REFERENCES shift_patterns(id),
 FOREIGN KEY(supervisor_approved_by) REFERENCES users(id),
 FOREIGN KEY(hr_validated_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS overtime_approvals(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 attendance_id INTEGER NOT NULL,
 requested_hours REAL NOT NULL,
 approved_hours REAL NOT NULL DEFAULT 0,
 approval_status TEXT NOT NULL DEFAULT 'Pendiente',
 approved_by INTEGER,
 approved_at TEXT,
 reason TEXT,
 FOREIGN KEY(attendance_id) REFERENCES attendance(id),
 FOREIGN KEY(approved_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS per_diems(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 worker_id INTEGER NOT NULL,
 journey_id INTEGER,
 per_diem_date TEXT NOT NULL,
 per_diem_type TEXT NOT NULL,
 amount REAL NOT NULL,
 taxable INTEGER NOT NULL DEFAULT 0,
 status TEXT NOT NULL DEFAULT 'Pendiente',
 approved_by INTEGER,
 notes TEXT,
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(journey_id) REFERENCES journeys(id),
 FOREIGN KEY(approved_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS payroll_parameters(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 effective_from TEXT NOT NULL,
 effective_to TEXT,
 parameter_code TEXT NOT NULL,
 parameter_name TEXT NOT NULL,
 percentage REAL,
 fixed_amount REAL,
 status TEXT NOT NULL DEFAULT 'Vigente',
 UNIQUE(effective_from,parameter_code)
);

CREATE TABLE IF NOT EXISTS payroll_periods(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 period_year INTEGER NOT NULL,
 period_month INTEGER NOT NULL,
 start_date TEXT NOT NULL,
 end_date TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'Abierto',
 UNIQUE(period_year,period_month)
);

CREATE TABLE IF NOT EXISTS payrolls(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 payroll_period_id INTEGER NOT NULL,
 worker_id INTEGER NOT NULL,
 contract_id INTEGER NOT NULL,
 base_salary REAL NOT NULL DEFAULT 0,
 overtime_amount REAL NOT NULL DEFAULT 0,
 bonuses_amount REAL NOT NULL DEFAULT 0,
 per_diem_amount REAL NOT NULL DEFAULT 0,
 taxable_gross REAL NOT NULL DEFAULT 0,
 non_taxable_amount REAL NOT NULL DEFAULT 0,
 pension_deduction REAL NOT NULL DEFAULT 0,
 health_deduction REAL NOT NULL DEFAULT 0,
 unemployment_deduction REAL NOT NULL DEFAULT 0,
 tax_withholding REAL NOT NULL DEFAULT 0,
 other_deductions REAL NOT NULL DEFAULT 0,
 net_pay REAL NOT NULL DEFAULT 0,
 employer_cost REAL NOT NULL DEFAULT 0,
 status TEXT NOT NULL DEFAULT 'Borrador',
 calculated_at TEXT,
 closed_at TEXT,
 UNIQUE(payroll_period_id,worker_id),
 FOREIGN KEY(payroll_period_id) REFERENCES payroll_periods(id),
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(contract_id) REFERENCES worker_contracts(id)
);

CREATE TABLE IF NOT EXISTS payroll_items(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 payroll_id INTEGER NOT NULL,
 item_type TEXT NOT NULL,
 item_code TEXT NOT NULL,
 item_name TEXT NOT NULL,
 amount REAL NOT NULL,
 taxable INTEGER NOT NULL DEFAULT 1,
 affects_employer_cost INTEGER NOT NULL DEFAULT 0,
 notes TEXT,
 FOREIGN KEY(payroll_id) REFERENCES payrolls(id)
);

CREATE INDEX IF NOT EXISTS idx_attendance_worker_date ON attendance(worker_id,attendance_date);
CREATE INDEX IF NOT EXISTS idx_per_diems_worker_date ON per_diems(worker_id,per_diem_date);
CREATE INDEX IF NOT EXISTS idx_payroll_period_worker ON payrolls(payroll_period_id,worker_id);


-- ============================================================
-- v0.7 PREVENCIÓN, MEDIO AMBIENTE, ACREDITACIONES Y DOCUMENTOS
-- ============================================================

CREATE TABLE IF NOT EXISTS documents(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 document_type TEXT NOT NULL,
 file_name TEXT NOT NULL,
 storage_path TEXT NOT NULL,
 entity_type TEXT NOT NULL,
 entity_id INTEGER NOT NULL,
 issue_date TEXT,
 expiry_date TEXT,
 status TEXT NOT NULL DEFAULT 'Vigente',
 uploaded_by INTEGER,
 uploaded_at TEXT NOT NULL,
 notes TEXT,
 FOREIGN KEY(uploaded_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS safety_incidents(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 incident_date TEXT NOT NULL,
 incident_type TEXT NOT NULL,
 severity TEXT NOT NULL,
 worker_id INTEGER,
 asset_id INTEGER,
 journey_id INTEGER,
 location TEXT,
 description TEXT NOT NULL,
 immediate_action TEXT,
 lost_time_hours REAL NOT NULL DEFAULT 0,
 status TEXT NOT NULL DEFAULT 'Abierto',
 reported_by INTEGER NOT NULL,
 reported_at TEXT NOT NULL,
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(asset_id) REFERENCES assets(id),
 FOREIGN KEY(journey_id) REFERENCES journeys(id),
 FOREIGN KEY(reported_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS incident_investigations(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 incident_id INTEGER NOT NULL UNIQUE,
 investigator TEXT NOT NULL,
 investigation_date TEXT NOT NULL,
 root_cause TEXT NOT NULL,
 contributing_factors TEXT,
 conclusions TEXT,
 approved_by INTEGER,
 status TEXT NOT NULL DEFAULT 'Borrador',
 FOREIGN KEY(incident_id) REFERENCES safety_incidents(id),
 FOREIGN KEY(approved_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS corrective_actions(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 source_type TEXT NOT NULL,
 source_id INTEGER NOT NULL,
 action_code TEXT UNIQUE NOT NULL,
 description TEXT NOT NULL,
 responsible_user_id INTEGER,
 due_date TEXT NOT NULL,
 completion_date TEXT,
 effectiveness_check TEXT,
 status TEXT NOT NULL DEFAULT 'Pendiente',
 FOREIGN KEY(responsible_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS inspections(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 inspection_date TEXT NOT NULL,
 inspection_type TEXT NOT NULL,
 worker_id INTEGER,
 asset_id INTEGER,
 location TEXT,
 inspector TEXT NOT NULL,
 score REAL,
 findings TEXT,
 status TEXT NOT NULL DEFAULT 'Cerrada',
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(asset_id) REFERENCES assets(id)
);

CREATE TABLE IF NOT EXISTS training_records(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 worker_id INTEGER NOT NULL,
 training_name TEXT NOT NULL,
 training_date TEXT NOT NULL,
 expiry_date TEXT,
 provider TEXT,
 result TEXT,
 certificate_document_id INTEGER,
 status TEXT NOT NULL DEFAULT 'Vigente',
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(certificate_document_id) REFERENCES documents(id)
);

CREATE TABLE IF NOT EXISTS ppe_items(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 name TEXT NOT NULL,
 useful_life_days INTEGER,
 status TEXT NOT NULL DEFAULT 'Activo'
);

CREATE TABLE IF NOT EXISTS ppe_deliveries(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 worker_id INTEGER NOT NULL,
 ppe_item_id INTEGER NOT NULL,
 delivery_date TEXT NOT NULL,
 quantity INTEGER NOT NULL DEFAULT 1,
 replacement_due_date TEXT,
 returned_date TEXT,
 status TEXT NOT NULL DEFAULT 'Entregado',
 FOREIGN KEY(worker_id) REFERENCES workers(id),
 FOREIGN KEY(ppe_item_id) REFERENCES ppe_items(id)
);

CREATE TABLE IF NOT EXISTS environmental_events(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 code TEXT UNIQUE NOT NULL,
 event_date TEXT NOT NULL,
 event_type TEXT NOT NULL,
 asset_id INTEGER,
 journey_id INTEGER,
 location TEXT,
 quantity REAL,
 unit TEXT,
 description TEXT NOT NULL,
 immediate_action TEXT,
 status TEXT NOT NULL DEFAULT 'Abierto',
 reported_by INTEGER,
 FOREIGN KEY(asset_id) REFERENCES assets(id),
 FOREIGN KEY(journey_id) REFERENCES journeys(id),
 FOREIGN KEY(reported_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS waste_records(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 record_date TEXT NOT NULL,
 waste_type TEXT NOT NULL,
 hazardous INTEGER NOT NULL DEFAULT 0,
 quantity REAL NOT NULL,
 unit TEXT NOT NULL,
 storage_location TEXT,
 disposal_provider TEXT,
 manifest_number TEXT,
 status TEXT NOT NULL DEFAULT 'Pendiente retiro'
);

CREATE TABLE IF NOT EXISTS environmental_metrics(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 metric_date TEXT NOT NULL,
 metric_type TEXT NOT NULL,
 asset_id INTEGER,
 client_id INTEGER,
 contract_id INTEGER,
 quantity REAL NOT NULL,
 unit TEXT NOT NULL,
 source_reference TEXT,
 FOREIGN KEY(asset_id) REFERENCES assets(id),
 FOREIGN KEY(client_id) REFERENCES clients(id),
 FOREIGN KEY(contract_id) REFERENCES contracts(id)
);

CREATE TABLE IF NOT EXISTS accreditation_requirements(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 client_id INTEGER,
 contract_id INTEGER,
 entity_type TEXT NOT NULL,
 requirement_name TEXT NOT NULL,
 mandatory INTEGER NOT NULL DEFAULT 1,
 validity_days INTEGER,
 active INTEGER NOT NULL DEFAULT 1,
 FOREIGN KEY(client_id) REFERENCES clients(id),
 FOREIGN KEY(contract_id) REFERENCES contracts(id)
);

CREATE TABLE IF NOT EXISTS accreditations(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 requirement_id INTEGER NOT NULL,
 entity_type TEXT NOT NULL,
 entity_id INTEGER NOT NULL,
 document_id INTEGER,
 approval_date TEXT,
 expiry_date TEXT,
 status TEXT NOT NULL DEFAULT 'Pendiente',
 observations TEXT,
 FOREIGN KEY(requirement_id) REFERENCES accreditation_requirements(id),
 FOREIGN KEY(document_id) REFERENCES documents(id)
);

CREATE TABLE IF NOT EXISTS alerts(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 alert_type TEXT NOT NULL,
 entity_type TEXT NOT NULL,
 entity_id INTEGER NOT NULL,
 title TEXT NOT NULL,
 message TEXT NOT NULL,
 severity TEXT NOT NULL,
 due_date TEXT,
 status TEXT NOT NULL DEFAULT 'Abierta',
 created_at TEXT NOT NULL,
 closed_at TEXT,
 assigned_user_id INTEGER,
 FOREIGN KEY(assigned_user_id) REFERENCES users(id)
);

CREATE INDEX IF NOT EXISTS idx_documents_expiry ON documents(expiry_date,status);
CREATE INDEX IF NOT EXISTS idx_incidents_date ON safety_incidents(incident_date);
CREATE INDEX IF NOT EXISTS idx_actions_due ON corrective_actions(due_date,status);
CREATE INDEX IF NOT EXISTS idx_accreditations_expiry ON accreditations(expiry_date,status);
CREATE INDEX IF NOT EXISTS idx_alerts_status_due ON alerts(status,due_date);
