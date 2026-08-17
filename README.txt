BGV Enterprise · Prototipo Funcional v0.4

NUEVO:
- Cotizaciones y contratos.
- OC y HES en el modelo comercial.
- Liquidaciones con aprobación.
- Bloqueo de facturación por falta de OC/HES obligatoria.
- Factura automática y cuenta por cobrar.
- Pagos parciales y totales.

INSTALACIÓN:
python -m venv .venv
.venv\Scripts\activate (Windows)
source .venv/bin/activate (Linux/macOS)
pip install -r requirements.txt
python init_db.py
python app.py
Abrir http://127.0.0.1:5000

USUARIOS: maestro, gerencia, operaciones, administracion, taller, conductor.


NUEVO EN v0.5
- Centros de costo y proveedores.
- Compra y carga de combustible.
- IVA crédito e impuesto específico recuperable parametrizable.
- Costos automáticos desde compras de combustible.
- Contratos de arriendo de tractos, bateas, aljibes, ramplas, camionetas y van.
- Responsabilidades de mantención, repuestos y neumáticos.
- Taller interno, concesionario de marca, taller comercial y taller del propietario.
- Órdenes de trabajo y cierre con distribución del costo.
- Responsable de pago: BGV, propietario, garantía, seguro, cliente o compartido.
- Costos y gastos multidimensionales.
- Dashboard y API de costos.

NOTA TRIBUTARIA
El porcentaje recuperable del impuesto específico es un parámetro ingresado por el usuario.
Debe ser validado por el contador conforme al régimen y normativa vigente.


NUEVO EN v0.6
- Patrones de turno 5x2, 6x1, 7x7, 14x14 y personalizados.
- Horas semanales y ordinarias diarias parametrizables.
- Jornadas excepcionales con referencia de autorización.
- Contratos laborales y sueldo base.
- Sincronización de jornadas cerradas hacia asistencia.
- Cálculo de horas brutas, colación, netas, ordinarias y extraordinarias.
- Flujo de aprobación Supervisor → RR.HH.
- Viáticos tributables y no tributables.
- Parámetros previsionales con vigencia.
- Períodos de remuneración.
- Cálculo demostrativo de liquidaciones.
- Cierre de remuneración y envío automático al módulo de costos.
- Dashboard y API /api/hr.

ADVERTENCIA LEGAL Y PREVISIONAL
Los porcentajes previsionales, reglas de horas extraordinarias, topes, impuesto único,
gratificación, jornadas excepcionales y demás cálculos son configurables y demostrativos.
Antes de utilizar el sistema para remuneraciones reales deben validarse con profesionales
competentes y fuentes oficiales vigentes.


NUEVO EN v0.7
- Gestión documental por trabajador, activo, cliente, contrato, empresa e incidente.
- Accidentes, incidentes, cuasi accidentes y daños a equipos.
- Investigaciones de causa raíz.
- Acciones correctivas y seguimiento de eficacia.
- Entrega y reposición de EPP.
- Eventos ambientales y derrames.
- Residuos peligrosos y no peligrosos.
- Requisitos de acreditación por cliente y contrato.
- Acreditación de trabajadores, activos y empresa.
- Alertas automáticas por vencimiento documental y acciones pendientes.
- Dashboard y API /api/compliance.

IMPORTANTE
El prototipo registra metadatos de documentos y rutas de archivos, pero todavía no realiza
una carga segura de archivos. En producción se utilizará almacenamiento privado, control
de acceso, hash de integridad, respaldos y políticas de retención.
