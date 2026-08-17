
from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
import sqlite3
from pathlib import Path
from datetime import datetime, timedelta

APP_DIR = Path(__file__).resolve().parent
DB_PATH = APP_DIR / "bgv_enterprise.db"

app = Flask(__name__)
app.secret_key = "bgv-enterprise-dev-secret"

def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def role_required(*roles):
    def decorator(fn):
        def wrapper(*args, **kwargs):
            if "user_id" not in session:
                return redirect(url_for("login"))
            if roles and session.get("role") not in roles:
                flash("No tienes permiso para acceder a este módulo.", "error")
                return redirect(url_for("dashboard"))
            return fn(*args, **kwargs)
        wrapper.__name__ = fn.__name__
        return wrapper
    return decorator

@app.route("/", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        username = request.form["username"].strip()
        conn = get_db()
        user = conn.execute(
            "SELECT id, username, full_name, role FROM users WHERE username=? AND active=1",
            (username,)
        ).fetchone()
        conn.close()
        if user:
            session.update(user_id=user["id"], username=user["username"],
                           full_name=user["full_name"], role=user["role"])
            return redirect(url_for("dashboard"))
        flash("Usuario no encontrado.", "error")
    return render_template("login.html")

@app.route("/logout")
def logout():
    session.clear()
    return redirect(url_for("login"))

@app.route("/dashboard")
@role_required()
def dashboard():
    conn = get_db()
    metrics = {
        "active_journeys": conn.execute("SELECT COUNT(*) FROM journeys WHERE status='Abierta'").fetchone()[0],
        "trips_today": conn.execute("SELECT COUNT(*) FROM trips WHERE date(start_time)=date('now','localtime')").fetchone()[0],
        "active_assets": conn.execute("SELECT COUNT(*) FROM assets WHERE operational_status='Disponible'").fetchone()[0],
        "open_events": conn.execute("SELECT COUNT(*) FROM trip_events WHERE end_time IS NULL").fetchone()[0],
    }
    journeys = conn.execute("""
        SELECT j.id,j.code,j.date,j.start_time,j.end_time,j.status,w.full_name,a.code asset_code
        FROM journeys j
        JOIN workers w ON w.id=j.worker_id
        LEFT JOIN assets a ON a.id=j.tractor_id
        ORDER BY j.id DESC LIMIT 8
    """).fetchall()
    conn.close()
    return render_template("dashboard.html", metrics=metrics, journeys=journeys)

@app.route("/users")
@role_required("Maestro")
def users():
    conn = get_db()
    rows = conn.execute("SELECT * FROM users ORDER BY role, full_name").fetchall()
    conn.close()
    return render_template("users.html", rows=rows)

@app.route("/clients")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo")
def clients():
    conn = get_db()
    rows = conn.execute("SELECT * FROM clients ORDER BY business_name").fetchall()
    conn.close()
    return render_template("clients.html", rows=rows)

@app.route("/clients/new", methods=["POST"])
@role_required("Maestro","Administrativo")
def client_new():
    conn = get_db()
    conn.execute("""
        INSERT INTO clients(code,rut,business_name,payment_condition,requires_oc,requires_hes,status)
        VALUES(?,?,?,?,?,?,?)
    """, (
        request.form["code"], request.form["rut"], request.form["business_name"],
        request.form["payment_condition"], request.form["requires_oc"],
        request.form["requires_hes"], "Activo"
    ))
    conn.commit(); conn.close()
    flash("Cliente creado.", "success")
    return redirect(url_for("clients"))

@app.route("/assets")
@role_required()
def assets():
    conn = get_db()
    rows = conn.execute("""
        SELECT a.*, o.name owner_name
        FROM assets a LEFT JOIN owners o ON o.id=a.owner_id
        ORDER BY a.asset_type,a.code
    """).fetchall()
    conn.close()
    return render_template("assets.html", rows=rows)

@app.route("/workers")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller")
def workers():
    conn = get_db()
    rows = conn.execute("SELECT * FROM workers ORDER BY full_name").fetchall()
    conn.close()
    return render_template("workers.html", rows=rows)

@app.route("/journeys")
@role_required()
def journeys():
    conn = get_db()
    sql = """
        SELECT j.*,w.full_name,a.code tractor_code,b.code trailer_code
        FROM journeys j
        JOIN workers w ON w.id=j.worker_id
        LEFT JOIN assets a ON a.id=j.tractor_id
        LEFT JOIN assets b ON b.id=j.trailer_id
    """
    params = ()
    if session.get("role") == "Conductor":
        sql += " WHERE w.user_id=?"
        params = (session["user_id"],)
    sql += " ORDER BY j.id DESC"
    rows = conn.execute(sql, params).fetchall()
    workers = conn.execute("SELECT id,full_name FROM workers WHERE status='Activo'").fetchall()
    assets = conn.execute("SELECT id,code,asset_type FROM assets WHERE operational_status!='Fuera de servicio'").fetchall()
    conn.close()
    return render_template("journeys.html", rows=rows, workers=workers, assets=assets)

@app.route("/journeys/start", methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Conductor")
def journey_start():
    conn = get_db()
    worker_id = request.form.get("worker_id")
    if session.get("role") == "Conductor":
        row = conn.execute("SELECT id FROM workers WHERE user_id=?", (session["user_id"],)).fetchone()
        worker_id = row["id"]
    code = "JOR-" + datetime.now().strftime("%Y%m%d-%H%M%S")
    conn.execute("""
        INSERT INTO journeys(code,worker_id,date,start_time,tractor_id,trailer_id,status)
        VALUES(?,?,?,?,?,?,?)
    """, (code, worker_id, datetime.now().date().isoformat(), datetime.now().isoformat(timespec="seconds"),
          request.form.get("tractor_id") or None, request.form.get("trailer_id") or None, "Abierta"))
    conn.commit(); conn.close()
    flash("Jornada iniciada.", "success")
    return redirect(url_for("journeys"))

@app.route("/journeys/<int:journey_id>/close", methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Conductor")
def journey_close(journey_id):
    conn = get_db()
    open_trip = conn.execute(
        "SELECT COUNT(*) FROM trips WHERE journey_id=? AND status='Abierto'", (journey_id,)
    ).fetchone()[0]
    if open_trip:
        conn.close()
        flash("No puedes cerrar la jornada: existe un viaje abierto.", "error")
        return redirect(url_for("journeys"))
    conn.execute("UPDATE journeys SET end_time=?, status='Cerrada' WHERE id=?",
                 (datetime.now().isoformat(timespec="seconds"), journey_id))
    conn.commit(); conn.close()
    flash("Jornada cerrada.", "success")
    return redirect(url_for("journeys"))

@app.route("/journeys/<int:journey_id>/trips")
@role_required()
def trips(journey_id):
    conn = get_db()
    journey = conn.execute("""
        SELECT j.*,w.full_name,a.code tractor_code,b.code trailer_code
        FROM journeys j JOIN workers w ON w.id=j.worker_id
        LEFT JOIN assets a ON a.id=j.tractor_id
        LEFT JOIN assets b ON b.id=j.trailer_id WHERE j.id=?
    """,(journey_id,)).fetchone()
    trips = conn.execute("SELECT * FROM trips WHERE journey_id=? ORDER BY trip_no",(journey_id,)).fetchall()
    clients = conn.execute("SELECT id,business_name FROM clients WHERE status='Activo'").fetchall()
    conn.close()
    return render_template("trips.html", journey=journey, trips=trips, clients=clients)

@app.route("/journeys/<int:journey_id>/trips/start", methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Conductor")
def trip_start(journey_id):
    conn = get_db()
    open_trip = conn.execute("SELECT COUNT(*) FROM trips WHERE journey_id=? AND status='Abierto'",(journey_id,)).fetchone()[0]
    if open_trip:
        conn.close(); flash("Ya existe un viaje abierto.", "error")
        return redirect(url_for("trips", journey_id=journey_id))
    next_no = conn.execute("SELECT COALESCE(MAX(trip_no),0)+1 FROM trips WHERE journey_id=?",(journey_id,)).fetchone()[0]
    conn.execute("""
        INSERT INTO trips(journey_id,trip_no,client_id,origin,destination,start_time,status)
        VALUES(?,?,?,?,?,?,?)
    """,(journey_id,next_no,request.form["client_id"],request.form["origin"],request.form["destination"],
         datetime.now().isoformat(timespec="seconds"),"Abierto"))
    conn.commit(); conn.close()
    flash("Viaje iniciado.", "success")
    return redirect(url_for("trips", journey_id=journey_id))

@app.route("/trips/<int:trip_id>")
@role_required()
def trip_detail(trip_id):
    conn = get_db()
    trip = conn.execute("""
        SELECT t.*,c.business_name,j.code journey_code,w.full_name
        FROM trips t JOIN clients c ON c.id=t.client_id
        JOIN journeys j ON j.id=t.journey_id JOIN workers w ON w.id=j.worker_id
        WHERE t.id=?
    """,(trip_id,)).fetchone()
    events = conn.execute("SELECT * FROM trip_events WHERE trip_id=? ORDER BY id DESC",(trip_id,)).fetchall()
    conn.close()
    return render_template("trip_detail.html", trip=trip, events=events)

@app.route("/trips/<int:trip_id>/events", methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Conductor")
def event_add(trip_id):
    event_type = request.form["event_type"]
    conn = get_db()
    conn.execute("""
        INSERT INTO trip_events(trip_id,event_type,start_time,notes)
        VALUES(?,?,?,?)
    """,(trip_id,event_type,datetime.now().isoformat(timespec="seconds"),request.form.get("notes","")))
    conn.commit(); conn.close()
    flash("Evento registrado.", "success")
    return redirect(url_for("trip_detail", trip_id=trip_id))

@app.route("/events/<int:event_id>/close", methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Conductor")
def event_close(event_id):
    conn = get_db()
    row = conn.execute("SELECT trip_id,start_time FROM trip_events WHERE id=?",(event_id,)).fetchone()
    now = datetime.now()
    start = datetime.fromisoformat(row["start_time"])
    mins = int((now-start).total_seconds()/60)
    conn.execute("UPDATE trip_events SET end_time=?,duration_minutes=? WHERE id=?",
                 (now.isoformat(timespec="seconds"),mins,event_id))
    conn.commit(); conn.close()
    return redirect(url_for("trip_detail", trip_id=row["trip_id"]))

@app.route("/trips/<int:trip_id>/close", methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Conductor")
def trip_close(trip_id):
    conn = get_db()
    open_events = conn.execute("SELECT COUNT(*) FROM trip_events WHERE trip_id=? AND end_time IS NULL",(trip_id,)).fetchone()[0]
    if open_events:
        conn.close(); flash("Cierra primero todos los eventos abiertos.", "error")
        return redirect(url_for("trip_detail", trip_id=trip_id))
    conn.execute("UPDATE trips SET end_time=?,tons=?,status='Cerrado' WHERE id=?",
                 (datetime.now().isoformat(timespec="seconds"),request.form.get("tons") or 0,trip_id))
    conn.commit()
    journey_id = conn.execute("SELECT journey_id FROM trips WHERE id=?",(trip_id,)).fetchone()[0]
    conn.close()
    flash("Viaje cerrado.", "success")
    return redirect(url_for("trips", journey_id=journey_id))

@app.route("/api/dashboard")
@role_required()
def api_dashboard():
    conn=get_db()
    data={
        "journeys_open":conn.execute("SELECT COUNT(*) FROM journeys WHERE status='Abierta'").fetchone()[0],
        "trips_open":conn.execute("SELECT COUNT(*) FROM trips WHERE status='Abierto'").fetchone()[0],
        "events_open":conn.execute("SELECT COUNT(*) FROM trip_events WHERE end_time IS NULL").fetchone()[0],
    }
    conn.close()
    return jsonify(data)


@app.route('/commercial')
@role_required('Maestro','Gerencial','Administrativo','Supervisor Operativo')
def commercial():
    conn=get_db()
    q=conn.execute('SELECT q.*,c.business_name FROM quotations q JOIN clients c ON c.id=q.client_id ORDER BY q.id DESC').fetchall()
    x=conn.execute('SELECT x.*,c.business_name FROM contracts x JOIN clients c ON c.id=x.client_id ORDER BY x.id DESC').fetchall()
    conn.close(); return render_template('commercial.html', quotations=q, contracts=x)

@app.route('/quotations/new',methods=['POST'])
@role_required('Maestro','Administrativo')
def quotation_new():
    qty=float(request.form['quantity']); rate=float(request.form['rate_value']); net=qty*rate; tax=round(net*.19,2)
    code='COT-'+datetime.now().strftime('%Y%m%d-%H%M%S'); conn=get_db()
    conn.execute('INSERT INTO quotations(code,client_id,issue_date,service_type,rate_type,rate_value,quantity,net_amount,tax_amount,total_amount,status) VALUES(?,?,?,?,?,?,?,?,?,?,?)',(code,request.form['client_id'],datetime.now().date().isoformat(),request.form['service_type'],request.form['rate_type'],rate,qty,net,tax,net+tax,'Borrador'))
    conn.commit(); conn.close(); flash('Cotización creada.','success'); return redirect(url_for('commercial'))

@app.route('/contracts/new',methods=['POST'])
@role_required('Maestro','Administrativo')
def contract_new():
    amount=float(request.form['total_amount']); code='CON-'+datetime.now().strftime('%Y%m%d-%H%M%S'); conn=get_db()
    conn.execute('INSERT INTO contracts(code,client_id,quotation_id,start_date,end_date,total_amount,available_balance,requires_oc,requires_hes,status) VALUES(?,?,?,?,?,?,?,?,?,?)',(code,request.form['client_id'],request.form.get('quotation_id') or None,request.form['start_date'],request.form.get('end_date') or None,amount,amount,request.form['requires_oc'],request.form['requires_hes'],'Vigente'))
    conn.commit(); conn.close(); flash('Contrato creado.','success'); return redirect(url_for('commercial'))

@app.route('/billing')
@role_required('Maestro','Gerencial','Administrativo','Supervisor Operativo')
def billing():
    conn=get_db(); settlements=conn.execute('SELECT s.*,x.code contract_code,c.business_name FROM service_settlements s JOIN contracts x ON x.id=s.contract_id JOIN clients c ON c.id=x.client_id ORDER BY s.id DESC').fetchall(); contracts=conn.execute('SELECT x.id,x.code,c.business_name FROM contracts x JOIN clients c ON c.id=x.client_id WHERE x.status="Vigente"').fetchall(); invoices=conn.execute('SELECT i.*,c.business_name,a.balance,a.status ar_status FROM invoices i JOIN service_settlements s ON s.id=i.settlement_id JOIN contracts x ON x.id=s.contract_id JOIN clients c ON c.id=x.client_id JOIN accounts_receivable a ON a.invoice_id=i.id ORDER BY i.id DESC').fetchall(); conn.close(); return render_template('billing.html',settlements=settlements,contracts=contracts,invoices=invoices)

@app.route('/settlements/new',methods=['POST'])
@role_required('Maestro','Administrativo','Supervisor Operativo')
def settlement_new():
    net=float(request.form['net_amount']); tax=round(net*.19,2); code='LIQ-'+datetime.now().strftime('%Y%m%d-%H%M%S'); conn=get_db(); conn.execute('INSERT INTO service_settlements(code,contract_id,period_from,period_to,net_amount,tax_amount,total_amount,status) VALUES(?,?,?,?,?,?,?,?)',(code,request.form['contract_id'],request.form['period_from'],request.form['period_to'],net,tax,net+tax,'Borrador')); conn.commit(); conn.close(); flash('Liquidación creada.','success'); return redirect(url_for('billing'))

@app.route('/settlements/<int:sid>/approve',methods=['POST'])
@role_required('Maestro','Gerencial','Supervisor Operativo')
def settlement_approve(sid):
    conn=get_db(); conn.execute('UPDATE service_settlements SET status="Aprobada",approved_by=?,approved_at=? WHERE id=?',(session['user_id'],datetime.now().isoformat(timespec='seconds'),sid)); conn.commit(); conn.close(); flash('Liquidación aprobada.','success'); return redirect(url_for('billing'))

@app.route('/settlements/<int:sid>/invoice',methods=['POST'])
@role_required('Maestro','Administrativo')
def settlement_invoice(sid):
    conn=get_db(); row=conn.execute('SELECT s.*,x.requires_oc,x.requires_hes,c.payment_condition FROM service_settlements s JOIN contracts x ON x.id=s.contract_id JOIN clients c ON c.id=x.client_id WHERE s.id=?',(sid,)).fetchone()
    if row['status']!='Aprobada': conn.close(); flash('La liquidación debe estar aprobada.','error'); return redirect(url_for('billing'))
    if row['requires_oc']=='Obligatorio' and not row['purchase_order_id']: conn.close(); flash('Falta OC obligatoria.','error'); return redirect(url_for('billing'))
    if row['requires_hes']=='Obligatorio' and not row['hes_id']: conn.close(); flash('Falta HES obligatoria.','error'); return redirect(url_for('billing'))
    days=30 if '30' in row['payment_condition'] else 15 if '15' in row['payment_condition'] else 0; issue=datetime.now().date(); due=issue+timedelta(days=days); num='FAC-'+datetime.now().strftime('%Y%m%d-%H%M%S'); cur=conn.execute('INSERT INTO invoices(number,settlement_id,issue_date,due_date,net_amount,tax_amount,total_amount,payment_status) VALUES(?,?,?,?,?,?,?,?)',(num,sid,issue.isoformat(),due.isoformat(),row['net_amount'],row['tax_amount'],row['total_amount'],'Pendiente')); iid=cur.lastrowid; conn.execute('INSERT INTO accounts_receivable(invoice_id,original_amount,balance,due_date,status) VALUES(?,?,?,?,?)',(iid,row['total_amount'],row['total_amount'],due.isoformat(),'Pendiente')); conn.execute('UPDATE service_settlements SET status="Facturada" WHERE id=?',(sid,)); conn.commit(); conn.close(); flash('Factura emitida.','success'); return redirect(url_for('billing'))

@app.route('/settlements/<int:sid>/link-docs',methods=['POST'])
@role_required('Maestro','Administrativo')
def settlement_link_docs(sid):
    conn=get_db(); conn.execute('UPDATE service_settlements SET purchase_order_id=?,hes_id=? WHERE id=?',(request.form.get('purchase_order_id') or None,request.form.get('hes_id') or None,sid)); conn.commit(); conn.close(); flash('Documentos vinculados.','success'); return redirect(url_for('billing'))

@app.route('/receivables')
@role_required('Maestro','Gerencial','Administrativo')
def receivables():
    conn=get_db(); rows=conn.execute('SELECT a.*,i.number,c.business_name FROM accounts_receivable a JOIN invoices i ON i.id=a.invoice_id JOIN service_settlements s ON s.id=i.settlement_id JOIN contracts x ON x.id=s.contract_id JOIN clients c ON c.id=x.client_id ORDER BY a.due_date').fetchall(); conn.close(); return render_template('receivables.html',rows=rows)

@app.route('/receivables/<int:rid>/pay',methods=['POST'])
@role_required('Maestro','Administrativo')
def receivable_pay(rid):
    amount=float(request.form['amount']); conn=get_db(); r=conn.execute('SELECT * FROM accounts_receivable WHERE id=?',(rid,)).fetchone()
    if amount<=0 or amount>r['balance']: conn.close(); flash('Monto inválido.','error'); return redirect(url_for('receivables'))
    bal=r['balance']-amount; paid=r['paid_amount']+amount; status='Pagada' if bal<0.01 else 'Parcial'; conn.execute('INSERT INTO client_payments(account_receivable_id,payment_date,amount,payment_method,reference) VALUES(?,?,?,?,?)',(rid,request.form['payment_date'],amount,request.form['payment_method'],request.form.get('reference'))); conn.execute('UPDATE accounts_receivable SET paid_amount=?,balance=?,status=? WHERE id=?',(paid,bal,status,rid)); conn.execute('UPDATE invoices SET payment_status=? WHERE id=?',(status,r['invoice_id'])); conn.commit(); conn.close(); flash('Pago registrado.','success'); return redirect(url_for('receivables'))


# ============================================================
# v0.5 COSTOS, COMBUSTIBLE, ARRIENDOS Y TALLER
# ============================================================

@app.route("/cost-dashboard")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller")
def cost_dashboard():
    conn=get_db()
    metrics={
        "cost_total":conn.execute("SELECT COALESCE(SUM(total_amount-vat_credit-specific_tax_credit),0) FROM costs").fetchone()[0],
        "fuel_liters":conn.execute("SELECT COALESCE(SUM(liters),0) FROM fuel_loads").fetchone()[0],
        "workshop_cost":conn.execute("SELECT COALESCE(SUM(total_cost),0) FROM work_orders WHERE payment_responsible='BGV'").fetchone()[0],
        "rental_monthly":conn.execute("SELECT COALESCE(SUM(monthly_total),0) FROM rental_contracts WHERE status='Vigente'").fetchone()[0],
    }
    by_category=conn.execute("""SELECT category,SUM(total_amount-vat_credit-specific_tax_credit) amount
        FROM costs GROUP BY category ORDER BY amount DESC""").fetchall()
    by_asset=conn.execute("""SELECT a.code,SUM(c.total_amount-c.vat_credit-c.specific_tax_credit) amount
        FROM costs c JOIN assets a ON a.id=c.asset_id
        GROUP BY a.id,a.code ORDER BY amount DESC LIMIT 10""").fetchall()
    conn.close()
    return render_template("cost_dashboard.html",metrics=metrics,by_category=by_category,by_asset=by_asset)

@app.route("/fuel", methods=["GET"])
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Conductor")
def fuel():
    conn=get_db()
    purchases=conn.execute("""SELECT f.*,s.business_name FROM fuel_purchases f
        JOIN suppliers s ON s.id=f.supplier_id ORDER BY f.id DESC""").fetchall()
    loads=conn.execute("""SELECT l.*,a.code asset_code,w.full_name,j.code journey_code,t.trip_no
        FROM fuel_loads l JOIN assets a ON a.id=l.asset_id
        LEFT JOIN workers w ON w.id=l.worker_id LEFT JOIN journeys j ON j.id=l.journey_id
        LEFT JOIN trips t ON t.id=l.trip_id ORDER BY l.id DESC LIMIT 100""").fetchall()
    suppliers=conn.execute("SELECT id,business_name FROM suppliers WHERE supplier_type='Combustible' AND status='Activo'").fetchall()
    assets=conn.execute("SELECT id,code FROM assets ORDER BY code").fetchall()
    workers=conn.execute("SELECT id,full_name FROM workers WHERE status='Activo'").fetchall()
    journeys=conn.execute("SELECT id,code FROM journeys ORDER BY id DESC LIMIT 100").fetchall()
    purchases_list=conn.execute("SELECT id,invoice_number FROM fuel_purchases ORDER BY id DESC").fetchall()
    conn.close()
    return render_template("fuel.html",purchases=purchases,loads=loads,suppliers=suppliers,assets=assets,
                           workers=workers,journeys=journeys,purchases_list=purchases_list)

@app.route("/fuel/purchase",methods=["POST"])
@role_required("Maestro","Administrativo")
def fuel_purchase_new():
    liters=float(request.form["liters"]); net=float(request.form["net_amount"])
    vat=float(request.form.get("vat_amount") or round(net*.19,2))
    specific=float(request.form.get("specific_tax_amount") or 0)
    pct=float(request.form.get("recoverable_percentage") or 0)
    credit=round(specific*pct/100,2); total=net+vat+specific
    conn=get_db()
    cur=conn.execute("""INSERT INTO fuel_purchases(invoice_number,purchase_date,supplier_id,liters,net_amount,
        vat_amount,specific_tax_amount,recoverable_percentage,specific_tax_credit,total_amount,status)
        VALUES(?,?,?,?,?,?,?,?,?,?,?)""",(request.form["invoice_number"],request.form["purchase_date"],
        request.form["supplier_id"],liters,net,vat,specific,pct,credit,total,"Registrada"))
    fuel_id=cur.lastrowid
    conn.execute("""INSERT INTO costs(cost_date,category,description,net_amount,vat_credit,specific_tax_credit,
        total_amount,payment_responsible,cost_center_id,supplier_id,fuel_purchase_id,status)
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?)""",(request.form["purchase_date"],"Combustible",
        "Compra combustible factura "+request.form["invoice_number"],net,vat,credit,total,"BGV",1,
        request.form["supplier_id"],fuel_id,"Registrado"))
    conn.commit(); conn.close(); flash("Compra de combustible registrada.","success")
    return redirect(url_for("fuel"))

@app.route("/fuel/load",methods=["POST"])
@role_required("Maestro","Administrativo","Supervisor Operativo","Conductor")
def fuel_load_new():
    conn=get_db()
    worker_id=request.form.get("worker_id") or None
    if session.get("role")=="Conductor":
        row=conn.execute("SELECT id FROM workers WHERE user_id=?",(session["user_id"],)).fetchone()
        worker_id=row["id"] if row else None
    conn.execute("""INSERT INTO fuel_loads(fuel_purchase_id,load_time,asset_id,worker_id,journey_id,trip_id,
        liters,odometer,notes) VALUES(?,?,?,?,?,?,?,?,?)""",(request.form.get("fuel_purchase_id") or None,
        datetime.now().isoformat(timespec="seconds"),request.form["asset_id"],worker_id,
        request.form.get("journey_id") or None,request.form.get("trip_id") or None,
        request.form["liters"],request.form.get("odometer") or None,request.form.get("notes")))
    conn.commit(); conn.close(); flash("Carga de combustible registrada.","success")
    return redirect(url_for("fuel"))

@app.route("/rentals")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Taller")
def rentals():
    conn=get_db()
    rows=conn.execute("""SELECT r.*,a.code asset_code,o.name owner_name,w.name workshop_name
        FROM rental_contracts r JOIN assets a ON a.id=r.asset_id JOIN owners o ON o.id=r.owner_id
        LEFT JOIN workshops w ON w.id=r.workshop_id ORDER BY r.id DESC""").fetchall()
    assets=conn.execute("SELECT id,code FROM assets WHERE ownership!='Propio' ORDER BY code").fetchall()
    owners=conn.execute("SELECT id,name FROM owners ORDER BY name").fetchall()
    workshops=conn.execute("SELECT id,name FROM workshops WHERE status='Activo' ORDER BY name").fetchall()
    conn.close()
    return render_template("rentals.html",rows=rows,assets=assets,owners=owners,workshops=workshops)

@app.route("/rentals/new",methods=["POST"])
@role_required("Maestro","Administrativo")
def rental_new():
    net=float(request.form["monthly_net"]); vat=round(net*.19,2)
    conn=get_db()
    conn.execute("""INSERT INTO rental_contracts(code,asset_id,owner_id,start_date,end_date,monthly_net,
        tax_amount,monthly_total,includes_maintenance,maintenance_responsible,parts_responsible,
        tires_responsible,workshop_id,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)""",
        ("ARR-"+datetime.now().strftime("%Y%m%d-%H%M%S"),request.form["asset_id"],request.form["owner_id"],
         request.form["start_date"],request.form.get("end_date") or None,net,vat,net+vat,
         request.form["includes_maintenance"],request.form["maintenance_responsible"],
         request.form["parts_responsible"],request.form["tires_responsible"],
         request.form.get("workshop_id") or None,"Vigente"))
    conn.commit(); conn.close(); flash("Contrato de arriendo creado.","success")
    return redirect(url_for("rentals"))

@app.route("/workshops")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller")
def workshops():
    conn=get_db()
    workshops=conn.execute("""SELECT w.*,s.business_name supplier_name,o.name owner_name FROM workshops w
        LEFT JOIN suppliers s ON s.id=w.supplier_id LEFT JOIN owners o ON o.id=w.owner_id ORDER BY w.name""").fetchall()
    orders=conn.execute("""SELECT o.*,a.code asset_code,w.name workshop_name FROM work_orders o
        JOIN assets a ON a.id=o.asset_id JOIN workshops w ON w.id=o.workshop_id ORDER BY o.id DESC""").fetchall()
    assets=conn.execute("SELECT id,code FROM assets ORDER BY code").fetchall()
    workshops_list=conn.execute("SELECT id,name FROM workshops WHERE status='Activo' ORDER BY name").fetchall()
    conn.close()
    return render_template("workshops.html",workshops=workshops,orders=orders,assets=assets,workshops_list=workshops_list)

@app.route("/work-orders/new",methods=["POST"])
@role_required("Maestro","Supervisor Taller")
def work_order_new():
    conn=get_db()
    code="OT-"+datetime.now().strftime("%Y%m%d-%H%M%S")
    conn.execute("""INSERT INTO work_orders(code,asset_id,workshop_id,request_date,entry_time,maintenance_type,
        reason,diagnosis,requested_work,payment_responsible,warranty_status,quote_number,supplier_po_number,
        net_cost,tax_amount,total_cost,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)""",
        (code,request.form["asset_id"],request.form["workshop_id"],datetime.now().date().isoformat(),
         datetime.now().isoformat(timespec="seconds"),request.form["maintenance_type"],request.form["reason"],
         request.form.get("diagnosis"),request.form.get("requested_work"),request.form["payment_responsible"],
         request.form["warranty_status"],request.form.get("quote_number"),request.form.get("supplier_po_number"),
         0,0,0,"Abierta"))
    conn.execute("UPDATE assets SET operational_status='Taller' WHERE id=?",(request.form["asset_id"],))
    conn.commit(); conn.close(); flash("Orden de trabajo creada.","success")
    return redirect(url_for("workshops"))

@app.route("/work-orders/<int:oid>/close",methods=["POST"])
@role_required("Maestro","Supervisor Taller","Administrativo")
def work_order_close(oid):
    net=float(request.form.get("net_cost") or 0); vat=round(net*.19,2); total=net+vat
    conn=get_db()
    order=conn.execute("SELECT * FROM work_orders WHERE id=?",(oid,)).fetchone()
    conn.execute("""UPDATE work_orders SET exit_time=?,net_cost=?,tax_amount=?,total_cost=?,status='Cerrada'
        WHERE id=?""",(datetime.now().isoformat(timespec="seconds"),net,vat,total,oid))
    conn.execute("UPDATE assets SET operational_status='Disponible' WHERE id=?",(order["asset_id"],))
    if order["payment_responsible"] in ("BGV","Compartido"):
        bgv_total=total if order["payment_responsible"]=="BGV" else total/2
        bgv_net=net if order["payment_responsible"]=="BGV" else net/2
        bgv_vat=vat if order["payment_responsible"]=="BGV" else vat/2
        conn.execute("""INSERT INTO costs(cost_date,category,description,net_amount,vat_credit,total_amount,
            payment_responsible,cost_center_id,asset_id,work_order_id,status)
            VALUES(?,?,?,?,?,?,?,?,?,?,?)""",(datetime.now().date().isoformat(),"Taller",
            "Cierre "+order["code"],bgv_net,bgv_vat,bgv_total,"BGV",2,order["asset_id"],oid,"Registrado"))
    conn.commit(); conn.close(); flash("Orden cerrada y costo distribuido.","success")
    return redirect(url_for("workshops"))

@app.route("/costs")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller")
def costs():
    conn=get_db()
    rows=conn.execute("""SELECT c.*,cc.code cc_code,a.code asset_code,cl.business_name,s.business_name supplier_name
        FROM costs c JOIN cost_centers cc ON cc.id=c.cost_center_id
        LEFT JOIN assets a ON a.id=c.asset_id LEFT JOIN clients cl ON cl.id=c.client_id
        LEFT JOIN suppliers s ON s.id=c.supplier_id ORDER BY c.id DESC""").fetchall()
    centers=conn.execute("SELECT id,code||' · '||name label FROM cost_centers WHERE status='Activo'").fetchall()
    assets=conn.execute("SELECT id,code FROM assets ORDER BY code").fetchall()
    clients=conn.execute("SELECT id,business_name FROM clients WHERE status='Activo'").fetchall()
    suppliers=conn.execute("SELECT id,business_name FROM suppliers WHERE status='Activo'").fetchall()
    conn.close()
    return render_template("costs.html",rows=rows,centers=centers,assets=assets,clients=clients,suppliers=suppliers)

@app.route("/costs/new",methods=["POST"])
@role_required("Maestro","Administrativo")
def cost_new():
    net=float(request.form["net_amount"]); vat=float(request.form.get("vat_credit") or 0)
    total=net+vat
    conn=get_db()
    conn.execute("""INSERT INTO costs(cost_date,category,description,net_amount,vat_credit,total_amount,
        payment_responsible,cost_center_id,client_id,asset_id,supplier_id,status)
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?)""",(request.form["cost_date"],request.form["category"],
        request.form["description"],net,vat,total,request.form["payment_responsible"],
        request.form["cost_center_id"],request.form.get("client_id") or None,
        request.form.get("asset_id") or None,request.form.get("supplier_id") or None,"Registrado"))
    conn.commit(); conn.close(); flash("Costo registrado.","success")
    return redirect(url_for("costs"))

@app.route("/api/costs")
@role_required()
def api_costs():
    conn=get_db()
    data={
      "total":conn.execute("SELECT COALESCE(SUM(total_amount-vat_credit-specific_tax_credit),0) FROM costs").fetchone()[0],
      "fuel":conn.execute("SELECT COALESCE(SUM(total_amount-vat_credit-specific_tax_credit),0) FROM costs WHERE category='Combustible'").fetchone()[0],
      "workshop":conn.execute("SELECT COALESCE(SUM(total_amount-vat_credit-specific_tax_credit),0) FROM costs WHERE category='Taller'").fetchone()[0],
      "rental_monthly":conn.execute("SELECT COALESCE(SUM(monthly_total),0) FROM rental_contracts WHERE status='Vigente'").fetchone()[0],
    }
    conn.close(); return jsonify(data)


# ============================================================
# v0.6 RR.HH., ASISTENCIA Y REMUNERACIONES
# ============================================================

@app.route("/hr-dashboard")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo")
def hr_dashboard():
    conn=get_db()
    metrics={
      "active_workers":conn.execute("SELECT COUNT(*) FROM workers WHERE status='Activo'").fetchone()[0],
      "overtime_pending":conn.execute("SELECT COALESCE(SUM(overtime_hours),0) FROM attendance WHERE validation_status!='Validada'").fetchone()[0],
      "per_diems_pending":conn.execute("SELECT COALESCE(SUM(amount),0) FROM per_diems WHERE status='Pendiente'").fetchone()[0],
      "payroll_cost":conn.execute("SELECT COALESCE(SUM(employer_cost),0) FROM payrolls WHERE status IN ('Calculada','Cerrada')").fetchone()[0],
    }
    attendance=conn.execute("""SELECT a.*,w.full_name,s.name shift_name FROM attendance a
      JOIN workers w ON w.id=a.worker_id LEFT JOIN shift_patterns s ON s.id=a.shift_pattern_id
      ORDER BY a.attendance_date DESC,a.id DESC LIMIT 15""").fetchall()
    conn.close()
    return render_template("hr_dashboard.html",metrics=metrics,attendance=attendance)

@app.route("/shifts")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo")
def shifts():
    conn=get_db()
    rows=conn.execute("SELECT * FROM shift_patterns ORDER BY code").fetchall()
    conn.close(); return render_template("shifts.html",rows=rows)

@app.route("/shifts/new",methods=["POST"])
@role_required("Maestro","Administrativo")
def shift_new():
    conn=get_db()
    conn.execute("""INSERT INTO shift_patterns(code,name,work_days,rest_days,weekly_hours,daily_ordinary_hours,
      meal_minutes,exceptional_schedule,authorization_reference,status) VALUES(?,?,?,?,?,?,?,?,?,?)""",
      (request.form["code"],request.form["name"],request.form["work_days"],request.form["rest_days"],
       request.form["weekly_hours"],request.form["daily_ordinary_hours"],request.form["meal_minutes"],
       1 if request.form.get("exceptional_schedule")=="Sí" else 0,
       request.form.get("authorization_reference"),"Activo"))
    conn.commit(); conn.close(); flash("Turno creado.","success")
    return redirect(url_for("shifts"))

@app.route("/attendance")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Conductor")
def attendance_view():
    conn=get_db()
    sql="""SELECT a.*,w.full_name,s.name shift_name,j.code journey_code
      FROM attendance a JOIN workers w ON w.id=a.worker_id
      LEFT JOIN shift_patterns s ON s.id=a.shift_pattern_id
      LEFT JOIN journeys j ON j.id=a.journey_id"""
    params=()
    if session.get("role")=="Conductor":
        sql+=" WHERE w.user_id=?"; params=(session["user_id"],)
    sql+=" ORDER BY a.attendance_date DESC,a.id DESC"
    rows=conn.execute(sql,params).fetchall()
    conn.close(); return render_template("attendance.html",rows=rows)

@app.route("/attendance/sync",methods=["POST"])
@role_required("Maestro","Administrativo","Supervisor Operativo")
def attendance_sync():
    conn=get_db()
    closed=conn.execute("""SELECT j.*,w.id worker_id,w.shift FROM journeys j
      JOIN workers w ON w.id=j.worker_id
      WHERE j.status='Cerrada' AND j.end_time IS NOT NULL
      AND NOT EXISTS(SELECT 1 FROM attendance a WHERE a.journey_id=j.id)""").fetchall()
    created=0
    for j in closed:
        shift=conn.execute("""SELECT s.* FROM worker_contracts c JOIN shift_patterns s ON s.id=c.shift_pattern_id
          WHERE c.worker_id=? AND c.status='Vigente' ORDER BY c.id DESC LIMIT 1""",(j["worker_id"],)).fetchone()
        if not shift:
            continue
        start=datetime.fromisoformat(j["start_time"]); end=datetime.fromisoformat(j["end_time"])
        gross=max(0,(end-start).total_seconds()/3600)
        meal_minutes=conn.execute("""SELECT COALESCE(SUM(e.duration_minutes),0) FROM trip_events e
          JOIN trips t ON t.id=e.trip_id WHERE t.journey_id=? AND e.event_type='Almuerzo'""",(j["id"],)).fetchone()[0]
        if not meal_minutes:
            meal_minutes=shift["meal_minutes"]
        meal_hours=meal_minutes/60
        net=max(0,gross-meal_hours)
        ordinary=min(net,shift["daily_ordinary_hours"])
        overtime=max(0,net-shift["daily_ordinary_hours"])
        cur=conn.execute("""INSERT INTO attendance(worker_id,journey_id,attendance_date,shift_pattern_id,
          entry_time,exit_time,gross_hours,meal_hours,net_hours,ordinary_hours,overtime_hours,
          validation_status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)""",
          (j["worker_id"],j["id"],j["date"],shift["id"],j["start_time"],j["end_time"],
           round(gross,2),round(meal_hours,2),round(net,2),round(ordinary,2),round(overtime,2),"Pendiente"))
        if overtime>0:
            conn.execute("""INSERT INTO overtime_approvals(attendance_id,requested_hours,approval_status,reason)
              VALUES(?,?,?,?)""",(cur.lastrowid,round(overtime,2),"Pendiente","Generada desde jornada"))
        created+=1
    conn.commit(); conn.close(); flash(f"Se generaron {created} registros de asistencia.","success")
    return redirect(url_for("attendance_view"))

@app.route("/attendance/<int:aid>/approve",methods=["POST"])
@role_required("Maestro","Supervisor Operativo")
def attendance_approve(aid):
    conn=get_db()
    conn.execute("UPDATE attendance SET validation_status='Aprobada Supervisor',supervisor_approved_by=? WHERE id=?",
                 (session["user_id"],aid))
    conn.commit(); conn.close(); flash("Asistencia aprobada por supervisor.","success")
    return redirect(url_for("attendance_view"))

@app.route("/attendance/<int:aid>/validate",methods=["POST"])
@role_required("Maestro","Administrativo")
def attendance_validate(aid):
    conn=get_db()
    conn.execute("UPDATE attendance SET validation_status='Validada',hr_validated_by=? WHERE id=?",
                 (session["user_id"],aid))
    conn.execute("""UPDATE overtime_approvals SET approved_hours=requested_hours,approval_status='Aprobada',
      approved_by=?,approved_at=? WHERE attendance_id=? AND approval_status='Pendiente'""",
      (session["user_id"],datetime.now().isoformat(timespec="seconds"),aid))
    conn.commit(); conn.close(); flash("Asistencia validada por RR.HH.","success")
    return redirect(url_for("attendance_view"))

@app.route("/per-diems")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Conductor")
def per_diems_view():
    conn=get_db()
    sql="""SELECT p.*,w.full_name,j.code journey_code FROM per_diems p
      JOIN workers w ON w.id=p.worker_id LEFT JOIN journeys j ON j.id=p.journey_id"""
    params=()
    if session.get("role")=="Conductor":
        sql+=" WHERE w.user_id=?"; params=(session["user_id"],)
    sql+=" ORDER BY p.per_diem_date DESC,p.id DESC"
    rows=conn.execute(sql,params).fetchall()
    workers=conn.execute("SELECT id,full_name FROM workers WHERE status='Activo'").fetchall()
    journeys=conn.execute("SELECT id,code,worker_id FROM journeys ORDER BY id DESC LIMIT 100").fetchall()
    conn.close(); return render_template("per_diems.html",rows=rows,workers=workers,journeys=journeys)

@app.route("/per-diems/new",methods=["POST"])
@role_required("Maestro","Administrativo","Supervisor Operativo","Conductor")
def per_diem_new():
    conn=get_db()
    worker_id=request.form.get("worker_id")
    if session.get("role")=="Conductor":
        row=conn.execute("SELECT id FROM workers WHERE user_id=?",(session["user_id"],)).fetchone()
        worker_id=row["id"]
    conn.execute("""INSERT INTO per_diems(worker_id,journey_id,per_diem_date,per_diem_type,amount,taxable,status,notes)
      VALUES(?,?,?,?,?,?,?,?)""",(worker_id,request.form.get("journey_id") or None,request.form["per_diem_date"],
      request.form["per_diem_type"],request.form["amount"],1 if request.form.get("taxable")=="Sí" else 0,
      "Pendiente",request.form.get("notes")))
    conn.commit(); conn.close(); flash("Viático registrado.","success")
    return redirect(url_for("per_diems_view"))

@app.route("/per-diems/<int:pid>/approve",methods=["POST"])
@role_required("Maestro","Administrativo","Supervisor Operativo")
def per_diem_approve(pid):
    conn=get_db(); conn.execute("UPDATE per_diems SET status='Aprobado',approved_by=? WHERE id=?",
                                (session["user_id"],pid))
    conn.commit(); conn.close(); flash("Viático aprobado.","success")
    return redirect(url_for("per_diems_view"))

@app.route("/payroll")
@role_required("Maestro","Gerencial","Administrativo")
def payroll_view():
    conn=get_db()
    periods=conn.execute("SELECT * FROM payroll_periods ORDER BY period_year DESC,period_month DESC").fetchall()
    rows=conn.execute("""SELECT p.*,w.full_name,pp.code period_code FROM payrolls p
      JOIN workers w ON w.id=p.worker_id JOIN payroll_periods pp ON pp.id=p.payroll_period_id
      ORDER BY pp.period_year DESC,pp.period_month DESC,w.full_name""").fetchall()
    conn.close(); return render_template("payroll.html",periods=periods,rows=rows)

@app.route("/payroll/period",methods=["POST"])
@role_required("Maestro","Administrativo")
def payroll_period_new():
    year=int(request.form["period_year"]); month=int(request.form["period_month"])
    start=f"{year:04d}-{month:02d}-01"
    if month==12: end=f"{year+1:04d}-01-01"
    else: end=f"{year:04d}-{month+1:02d}-01"
    code=f"REM-{year:04d}-{month:02d}"
    conn=get_db()
    conn.execute("""INSERT INTO payroll_periods(code,period_year,period_month,start_date,end_date,status)
      VALUES(?,?,?,?,?,'Abierto')""",(code,year,month,start,end))
    conn.commit(); conn.close(); flash("Período creado.","success")
    return redirect(url_for("payroll_view"))

@app.route("/payroll/<int:period_id>/calculate",methods=["POST"])
@role_required("Maestro","Administrativo")
def payroll_calculate(period_id):
    conn=get_db()
    period=conn.execute("SELECT * FROM payroll_periods WHERE id=?",(period_id,)).fetchone()
    params={r["parameter_code"]:r["percentage"] or 0 for r in conn.execute(
      "SELECT * FROM payroll_parameters WHERE status='Vigente'").fetchall()}
    contracts=conn.execute("""SELECT c.*,w.full_name FROM worker_contracts c JOIN workers w ON w.id=c.worker_id
      WHERE c.status='Vigente'""").fetchall()
    count=0
    for c in contracts:
        overtime_hours=conn.execute("""SELECT COALESCE(SUM(o.approved_hours),0)
          FROM overtime_approvals o JOIN attendance a ON a.id=o.attendance_id
          WHERE a.worker_id=? AND a.attendance_date>=? AND a.attendance_date<?
          AND o.approval_status='Aprobada'""",(c["worker_id"],period["start_date"],period["end_date"])).fetchone()[0]
        per_diem_taxable=conn.execute("""SELECT COALESCE(SUM(amount),0) FROM per_diems
          WHERE worker_id=? AND per_diem_date>=? AND per_diem_date<? AND status='Aprobado' AND taxable=1""",
          (c["worker_id"],period["start_date"],period["end_date"])).fetchone()[0]
        per_diem_non=conn.execute("""SELECT COALESCE(SUM(amount),0) FROM per_diems
          WHERE worker_id=? AND per_diem_date>=? AND per_diem_date<? AND status='Aprobado' AND taxable=0""",
          (c["worker_id"],period["start_date"],period["end_date"])).fetchone()[0]
        hourly=c["base_salary"]/180
        overtime_amount=round(hourly*(1+params.get("OVERTIME_FACTOR",50)/100)*overtime_hours,2)
        taxable=round(c["base_salary"]+overtime_amount+per_diem_taxable,2)
        pension=round(taxable*params.get("PENSION_WORKER",0)/100,2)
        health=round(taxable*params.get("HEALTH_WORKER",0)/100,2)
        unemployment=round(taxable*params.get("UNEMPLOYMENT_WORKER",0)/100,2)
        net=round(taxable+per_diem_non-pension-health-unemployment,2)
        employer_extra=round(taxable*params.get("EMPLOYER_EXTRA",0)/100,2)
        employer_cost=round(taxable+per_diem_non+employer_extra,2)
        conn.execute("""INSERT INTO payrolls(payroll_period_id,worker_id,contract_id,base_salary,overtime_amount,
          per_diem_amount,taxable_gross,non_taxable_amount,pension_deduction,health_deduction,
          unemployment_deduction,net_pay,employer_cost,status,calculated_at)
          VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
          ON CONFLICT(payroll_period_id,worker_id) DO UPDATE SET
          base_salary=excluded.base_salary,overtime_amount=excluded.overtime_amount,
          per_diem_amount=excluded.per_diem_amount,taxable_gross=excluded.taxable_gross,
          non_taxable_amount=excluded.non_taxable_amount,pension_deduction=excluded.pension_deduction,
          health_deduction=excluded.health_deduction,unemployment_deduction=excluded.unemployment_deduction,
          net_pay=excluded.net_pay,employer_cost=excluded.employer_cost,status='Calculada',
          calculated_at=excluded.calculated_at""",
          (period_id,c["worker_id"],c["id"],c["base_salary"],overtime_amount,
           per_diem_taxable+per_diem_non,taxable,per_diem_non,pension,health,unemployment,
           net,employer_cost,"Calculada",datetime.now().isoformat(timespec="seconds")))
        count+=1
    conn.commit(); conn.close(); flash(f"Se calcularon {count} remuneraciones.","success")
    return redirect(url_for("payroll_view"))

@app.route("/payroll/<int:payroll_id>/close",methods=["POST"])
@role_required("Maestro","Administrativo")
def payroll_close(payroll_id):
    conn=get_db()
    p=conn.execute("""SELECT p.*,pp.end_date,c.cost_center_id FROM payrolls p
      JOIN payroll_periods pp ON pp.id=p.payroll_period_id
      JOIN worker_contracts c ON c.id=p.contract_id WHERE p.id=?""",(payroll_id,)).fetchone()
    if p["status"]=="Cerrada":
        conn.close(); flash("La remuneración ya está cerrada.","error")
        return redirect(url_for("payroll_view"))
    conn.execute("UPDATE payrolls SET status='Cerrada',closed_at=? WHERE id=?",
                 (datetime.now().isoformat(timespec="seconds"),payroll_id))
    conn.execute("""INSERT INTO costs(cost_date,category,description,net_amount,total_amount,
      payment_responsible,cost_center_id,worker_id,status)
      VALUES(?,?,?,?,?,?,?,?,?)""",(p["end_date"],"Remuneraciones","Costo laboral período "+str(p["payroll_period_id"]),
      p["employer_cost"],p["employer_cost"],"BGV",p["cost_center_id"],p["worker_id"],"Registrado"))
    conn.commit(); conn.close(); flash("Remuneración cerrada y enviada a costos.","success")
    return redirect(url_for("payroll_view"))

@app.route("/payroll-parameters")
@role_required("Maestro","Gerencial","Administrativo")
def payroll_parameters_view():
    conn=get_db(); rows=conn.execute("SELECT * FROM payroll_parameters ORDER BY parameter_code,effective_from DESC").fetchall()
    conn.close(); return render_template("payroll_parameters.html",rows=rows)

@app.route("/api/hr")
@role_required()
def api_hr():
    conn=get_db()
    data={
      "workers":conn.execute("SELECT COUNT(*) FROM workers WHERE status='Activo'").fetchone()[0],
      "overtime_approved":conn.execute("SELECT COALESCE(SUM(approved_hours),0) FROM overtime_approvals WHERE approval_status='Aprobada'").fetchone()[0],
      "per_diems_approved":conn.execute("SELECT COALESCE(SUM(amount),0) FROM per_diems WHERE status='Aprobado'").fetchone()[0],
      "employer_cost":conn.execute("SELECT COALESCE(SUM(employer_cost),0) FROM payrolls WHERE status IN ('Calculada','Cerrada')").fetchone()[0],
    }
    conn.close(); return jsonify(data)


# ============================================================
# v0.7 PREVENCIÓN, MEDIO AMBIENTE, ACREDITACIONES Y DOCUMENTOS
# ============================================================

@app.route("/safety-dashboard")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller")
def safety_dashboard():
    conn=get_db()
    metrics={
      "open_incidents":conn.execute("SELECT COUNT(*) FROM safety_incidents WHERE status!='Cerrado'").fetchone()[0],
      "overdue_actions":conn.execute("SELECT COUNT(*) FROM corrective_actions WHERE status!='Cerrada' AND due_date<date('now','localtime')").fetchone()[0],
      "open_environmental":conn.execute("SELECT COUNT(*) FROM environmental_events WHERE status!='Cerrado'").fetchone()[0],
      "expiring_accreditations":conn.execute("SELECT COUNT(*) FROM accreditations WHERE status='Aprobada' AND expiry_date<=date('now','+30 day')").fetchone()[0],
    }
    incidents=conn.execute("""SELECT i.*,w.full_name,a.code asset_code FROM safety_incidents i
      LEFT JOIN workers w ON w.id=i.worker_id LEFT JOIN assets a ON a.id=i.asset_id
      ORDER BY i.incident_date DESC,i.id DESC LIMIT 10""").fetchall()
    actions=conn.execute("""SELECT c.*,u.full_name responsible_name FROM corrective_actions c
      LEFT JOIN users u ON u.id=c.responsible_user_id ORDER BY c.due_date LIMIT 10""").fetchall()
    conn.close()
    return render_template("safety_dashboard.html",metrics=metrics,incidents=incidents,actions=actions)

@app.route("/incidents")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller","Conductor")
def incidents():
    conn=get_db()
    sql="""SELECT i.*,w.full_name,a.code asset_code FROM safety_incidents i
      LEFT JOIN workers w ON w.id=i.worker_id LEFT JOIN assets a ON a.id=i.asset_id"""
    params=()
    if session.get("role")=="Conductor":
        sql+=" WHERE w.user_id=?"; params=(session["user_id"],)
    sql+=" ORDER BY i.incident_date DESC,i.id DESC"
    rows=conn.execute(sql,params).fetchall()
    workers=conn.execute("SELECT id,full_name FROM workers WHERE status='Activo'").fetchall()
    assets=conn.execute("SELECT id,code FROM assets ORDER BY code").fetchall()
    conn.close()
    return render_template("incidents.html",rows=rows,workers=workers,assets=assets)

@app.route("/incidents/new",methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Supervisor Taller","Conductor")
def incident_new():
    conn=get_db()
    worker_id=request.form.get("worker_id") or None
    if session.get("role")=="Conductor":
        row=conn.execute("SELECT id FROM workers WHERE user_id=?",(session["user_id"],)).fetchone()
        worker_id=row["id"] if row else None
    code="INC-"+datetime.now().strftime("%Y%m%d-%H%M%S")
    conn.execute("""INSERT INTO safety_incidents(code,incident_date,incident_type,severity,worker_id,asset_id,
      journey_id,location,description,immediate_action,lost_time_hours,status,reported_by,reported_at)
      VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)""",(code,request.form["incident_date"],request.form["incident_type"],
      request.form["severity"],worker_id,request.form.get("asset_id") or None,request.form.get("journey_id") or None,
      request.form.get("location"),request.form["description"],request.form.get("immediate_action"),
      request.form.get("lost_time_hours") or 0,"Abierto",session["user_id"],datetime.now().isoformat(timespec="seconds")))
    conn.commit(); conn.close(); flash("Incidente registrado.","success")
    return redirect(url_for("incidents"))

@app.route("/incidents/<int:iid>/investigate",methods=["POST"])
@role_required("Maestro","Supervisor Operativo")
def incident_investigate(iid):
    conn=get_db()
    conn.execute("""INSERT INTO incident_investigations(incident_id,investigator,investigation_date,root_cause,
      contributing_factors,conclusions,approved_by,status) VALUES(?,?,?,?,?,?,?,?)
      ON CONFLICT(incident_id) DO UPDATE SET investigator=excluded.investigator,
      investigation_date=excluded.investigation_date,root_cause=excluded.root_cause,
      contributing_factors=excluded.contributing_factors,conclusions=excluded.conclusions,
      approved_by=excluded.approved_by,status=excluded.status""",
      (iid,request.form["investigator"],request.form["investigation_date"],request.form["root_cause"],
       request.form.get("contributing_factors"),request.form.get("conclusions"),session["user_id"],"Aprobada"))
    conn.execute("UPDATE safety_incidents SET status='Investigado' WHERE id=?",(iid,))
    conn.commit(); conn.close(); flash("Investigación registrada.","success")
    return redirect(url_for("incidents"))

@app.route("/corrective-actions")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller")
def corrective_actions():
    conn=get_db()
    rows=conn.execute("""SELECT c.*,u.full_name responsible_name FROM corrective_actions c
      LEFT JOIN users u ON u.id=c.responsible_user_id ORDER BY c.due_date,c.id""").fetchall()
    users=conn.execute("SELECT id,full_name FROM users WHERE active=1 ORDER BY full_name").fetchall()
    conn.close(); return render_template("corrective_actions.html",rows=rows,users=users)

@app.route("/corrective-actions/new",methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Supervisor Taller")
def corrective_action_new():
    conn=get_db()
    code="ACC-"+datetime.now().strftime("%Y%m%d-%H%M%S")
    conn.execute("""INSERT INTO corrective_actions(source_type,source_id,action_code,description,
      responsible_user_id,due_date,status) VALUES(?,?,?,?,?,?,?)""",
      (request.form["source_type"],request.form.get("source_id") or 0,code,request.form["description"],
       request.form.get("responsible_user_id") or None,request.form["due_date"],"Pendiente"))
    conn.commit(); conn.close(); flash("Acción correctiva creada.","success")
    return redirect(url_for("corrective_actions"))

@app.route("/corrective-actions/<int:aid>/close",methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Supervisor Taller")
def corrective_action_close(aid):
    conn=get_db()
    conn.execute("""UPDATE corrective_actions SET completion_date=?,effectiveness_check=?,status='Cerrada'
      WHERE id=?""",(datetime.now().date().isoformat(),request.form.get("effectiveness_check"),aid))
    conn.commit(); conn.close(); flash("Acción cerrada.","success")
    return redirect(url_for("corrective_actions"))

@app.route("/ppe")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller")
def ppe():
    conn=get_db()
    items=conn.execute("SELECT * FROM ppe_items WHERE status='Activo' ORDER BY name").fetchall()
    deliveries=conn.execute("""SELECT d.*,w.full_name,p.name ppe_name FROM ppe_deliveries d
      JOIN workers w ON w.id=d.worker_id JOIN ppe_items p ON p.id=d.ppe_item_id
      ORDER BY d.delivery_date DESC,d.id DESC""").fetchall()
    workers=conn.execute("SELECT id,full_name FROM workers WHERE status='Activo'").fetchall()
    conn.close(); return render_template("ppe.html",items=items,deliveries=deliveries,workers=workers)

@app.route("/ppe/deliver",methods=["POST"])
@role_required("Maestro","Administrativo","Supervisor Operativo")
def ppe_deliver():
    conn=get_db()
    item=conn.execute("SELECT useful_life_days FROM ppe_items WHERE id=?",(request.form["ppe_item_id"],)).fetchone()
    delivery=datetime.fromisoformat(request.form["delivery_date"]).date()
    due=(delivery+timedelta(days=item["useful_life_days"])).isoformat() if item["useful_life_days"] else None
    conn.execute("""INSERT INTO ppe_deliveries(worker_id,ppe_item_id,delivery_date,quantity,replacement_due_date,status)
      VALUES(?,?,?,?,?,?)""",(request.form["worker_id"],request.form["ppe_item_id"],request.form["delivery_date"],
      request.form.get("quantity") or 1,due,"Entregado"))
    conn.commit(); conn.close(); flash("EPP entregado.","success")
    return redirect(url_for("ppe"))

@app.route("/environment")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller","Conductor")
def environment():
    conn=get_db()
    events=conn.execute("""SELECT e.*,a.code asset_code FROM environmental_events e
      LEFT JOIN assets a ON a.id=e.asset_id ORDER BY e.event_date DESC,e.id DESC""").fetchall()
    waste=conn.execute("SELECT * FROM waste_records ORDER BY record_date DESC,id DESC").fetchall()
    assets=conn.execute("SELECT id,code FROM assets ORDER BY code").fetchall()
    conn.close(); return render_template("environment.html",events=events,waste=waste,assets=assets)

@app.route("/environment/event",methods=["POST"])
@role_required("Maestro","Supervisor Operativo","Supervisor Taller","Conductor")
def environmental_event_new():
    conn=get_db()
    code="AMB-"+datetime.now().strftime("%Y%m%d-%H%M%S")
    conn.execute("""INSERT INTO environmental_events(code,event_date,event_type,asset_id,journey_id,location,
      quantity,unit,description,immediate_action,status,reported_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)""",
      (code,request.form["event_date"],request.form["event_type"],request.form.get("asset_id") or None,
       request.form.get("journey_id") or None,request.form.get("location"),request.form.get("quantity") or None,
       request.form.get("unit"),request.form["description"],request.form.get("immediate_action"),
       "Abierto",session["user_id"]))
    conn.commit(); conn.close(); flash("Evento ambiental registrado.","success")
    return redirect(url_for("environment"))

@app.route("/environment/waste",methods=["POST"])
@role_required("Maestro","Administrativo","Supervisor Taller")
def waste_new():
    conn=get_db()
    conn.execute("""INSERT INTO waste_records(record_date,waste_type,hazardous,quantity,unit,storage_location,
      disposal_provider,manifest_number,status) VALUES(?,?,?,?,?,?,?,?,?)""",
      (request.form["record_date"],request.form["waste_type"],1 if request.form.get("hazardous")=="Sí" else 0,
       request.form["quantity"],request.form["unit"],request.form.get("storage_location"),
       request.form.get("disposal_provider"),request.form.get("manifest_number"),request.form["status"]))
    conn.commit(); conn.close(); flash("Residuo registrado.","success")
    return redirect(url_for("environment"))

@app.route("/accreditations")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo")
def accreditations():
    conn=get_db()
    requirements=conn.execute("""SELECT r.*,c.business_name,x.code contract_code FROM accreditation_requirements r
      LEFT JOIN clients c ON c.id=r.client_id LEFT JOIN contracts x ON x.id=r.contract_id
      WHERE r.active=1 ORDER BY r.entity_type,r.requirement_name""").fetchall()
    rows=conn.execute("""SELECT a.*,r.requirement_name,r.entity_type requirement_entity,d.file_name
      FROM accreditations a JOIN accreditation_requirements r ON r.id=a.requirement_id
      LEFT JOIN documents d ON d.id=a.document_id ORDER BY a.expiry_date,a.id""").fetchall()
    conn.close(); return render_template("accreditations.html",requirements=requirements,rows=rows)

@app.route("/accreditations/new",methods=["POST"])
@role_required("Maestro","Administrativo")
def accreditation_new():
    conn=get_db()
    conn.execute("""INSERT INTO accreditations(requirement_id,entity_type,entity_id,approval_date,expiry_date,status,observations)
      VALUES(?,?,?,?,?,?,?)""",(request.form["requirement_id"],request.form["entity_type"],request.form["entity_id"],
      request.form.get("approval_date") or None,request.form.get("expiry_date") or None,request.form["status"],
      request.form.get("observations")))
    conn.commit(); conn.close(); flash("Acreditación registrada.","success")
    return redirect(url_for("accreditations"))

@app.route("/documents")
@role_required("Maestro","Gerencial","Administrativo","Supervisor Operativo","Supervisor Taller","Conductor")
def documents_view():
    conn=get_db()
    rows=conn.execute("""SELECT d.*,u.full_name uploader FROM documents d
      LEFT JOIN users u ON u.id=d.uploaded_by ORDER BY d.uploaded_at DESC,d.id DESC""").fetchall()
    conn.close(); return render_template("documents.html",rows=rows)

@app.route("/documents/new",methods=["POST"])
@role_required("Maestro","Administrativo","Supervisor Operativo","Supervisor Taller","Conductor")
def document_new():
    conn=get_db()
    conn.execute("""INSERT INTO documents(document_type,file_name,storage_path,entity_type,entity_id,
      issue_date,expiry_date,status,uploaded_by,uploaded_at,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?)""",
      (request.form["document_type"],request.form["file_name"],request.form["storage_path"],
       request.form["entity_type"],request.form["entity_id"],request.form.get("issue_date") or None,
       request.form.get("expiry_date") or None,"Vigente",session["user_id"],
       datetime.now().isoformat(timespec="seconds"),request.form.get("notes")))
    conn.commit(); conn.close(); flash("Documento registrado.","success")
    return redirect(url_for("documents_view"))

@app.route("/alerts/generate",methods=["POST"])
@role_required("Maestro","Administrativo","Supervisor Operativo","Supervisor Taller")
def alerts_generate():
    conn=get_db(); now=datetime.now().date(); created=0
    exp_docs=conn.execute("""SELECT * FROM documents WHERE status='Vigente' AND expiry_date IS NOT NULL
      AND expiry_date<=date('now','+30 day')""").fetchall()
    for d in exp_docs:
        exists=conn.execute("""SELECT 1 FROM alerts WHERE alert_type='Documento' AND entity_type='Documento'
          AND entity_id=? AND status='Abierta'""",(d["id"],)).fetchone()
        if not exists:
            conn.execute("""INSERT INTO alerts(alert_type,entity_type,entity_id,title,message,severity,due_date,status,created_at)
              VALUES(?,?,?,?,?,?,?,?,?)""",("Documento","Documento",d["id"],"Documento próximo a vencer",
              d["document_type"]+" · "+d["file_name"],"Alta",d["expiry_date"],"Abierta",
              datetime.now().isoformat(timespec="seconds"))); created+=1
    overdue=conn.execute("""SELECT * FROM corrective_actions WHERE status!='Cerrada'
      AND due_date<=date('now','+7 day')""").fetchall()
    for a in overdue:
        exists=conn.execute("""SELECT 1 FROM alerts WHERE alert_type='Acción correctiva'
          AND entity_type='AccionCorrectiva' AND entity_id=? AND status='Abierta'""",(a["id"],)).fetchone()
        if not exists:
            severity="Crítica" if a["due_date"]<now.isoformat() else "Media"
            conn.execute("""INSERT INTO alerts(alert_type,entity_type,entity_id,title,message,severity,due_date,status,created_at,assigned_user_id)
              VALUES(?,?,?,?,?,?,?,?,?,?)""",("Acción correctiva","AccionCorrectiva",a["id"],
              "Acción correctiva pendiente",a["description"],severity,a["due_date"],"Abierta",
              datetime.now().isoformat(timespec="seconds"),a["responsible_user_id"])); created+=1
    conn.commit(); conn.close(); flash(f"Se generaron {created} alertas nuevas.","success")
    return redirect(url_for("alerts_view"))

@app.route("/alerts")
@role_required()
def alerts_view():
    conn=get_db()
    sql="""SELECT a.*,u.full_name assigned_name FROM alerts a LEFT JOIN users u ON u.id=a.assigned_user_id"""
    params=()
    if session.get("role")=="Conductor":
        sql+=" WHERE a.assigned_user_id=?"; params=(session["user_id"],)
    sql+=" ORDER BY CASE a.severity WHEN 'Crítica' THEN 1 WHEN 'Alta' THEN 2 WHEN 'Media' THEN 3 ELSE 4 END,a.due_date"
    rows=conn.execute(sql,params).fetchall()
    conn.close(); return render_template("alerts.html",rows=rows)

@app.route("/alerts/<int:aid>/close",methods=["POST"])
@role_required("Maestro","Administrativo","Supervisor Operativo","Supervisor Taller")
def alert_close(aid):
    conn=get_db()
    conn.execute("UPDATE alerts SET status='Cerrada',closed_at=? WHERE id=?",
                 (datetime.now().isoformat(timespec="seconds"),aid))
    conn.commit(); conn.close(); flash("Alerta cerrada.","success")
    return redirect(url_for("alerts_view"))

@app.route("/api/compliance")
@role_required()
def api_compliance():
    conn=get_db()
    data={
      "open_incidents":conn.execute("SELECT COUNT(*) FROM safety_incidents WHERE status!='Cerrado'").fetchone()[0],
      "open_actions":conn.execute("SELECT COUNT(*) FROM corrective_actions WHERE status!='Cerrada'").fetchone()[0],
      "open_environmental_events":conn.execute("SELECT COUNT(*) FROM environmental_events WHERE status!='Cerrado'").fetchone()[0],
      "open_alerts":conn.execute("SELECT COUNT(*) FROM alerts WHERE status='Abierta'").fetchone()[0],
    }
    conn.close(); return jsonify(data)

if __name__ == "__main__":
    app.run(debug=True, host="127.0.0.1", port=5000)
