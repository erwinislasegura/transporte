# BGV Enterprise · Foundation 1.0

Plataforma empresarial PHP MVC + MySQL para transporte y logística, implementada a partir del **Documento Maestro v1.0**. La interfaz usa Bootstrap 5.3, una identidad visual responsive y navegación protegida por roles.

## Requisitos

- PHP 8.2 o superior con `pdo_mysql` y `fileinfo`.
- MySQL 8 o MariaDB 10.6+.
- Apache con `mod_rewrite` y `mod_headers`.
- Conexión a internet para cargar Bootstrap 5.3, Bootstrap Icons y la fuente Inter desde CDN.

No requiere Composer ni Node.js.

## Instalación en XAMPP para macOS

1. Copia la carpeta del proyecto como `/Applications/XAMPP/xamppfiles/htdocs/transporte`.
2. Inicia **Apache** y **MySQL** desde XAMPP Manager.
3. En `http://localhost/phpmyadmin`, crea una base UTF-8 llamada `bgv_enterprise`.
4. Importa, en este orden:
   - `database/01-schema.sql`
   - `database/02-seed.sql`
   - `database/03-documento-maestro-v1.sql`
5. Copia `.env.example` como `.env` y usa los datos de XAMPP. Por defecto normalmente son:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bgv_enterprise
DB_USERNAME=root
DB_PASSWORD=
APP_BASE_PATH=
```

6. Desde Terminal, crea o restablece de forma segura la empresa y el Super Administrador:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/transporte
/Applications/XAMPP/xamppfiles/bin/php scripts/create-super-admin.php
```

7. Escribe una contraseña inicial de al menos 12 caracteres cuando el instalador la solicite. No se mostrará en pantalla ni quedará guardada en el repositorio.
8. Abre `http://localhost/transporte/` e ingresa con el usuario `superadmin`.

El `index.php` de la raíz y `.htaccess` resuelven automáticamente la subcarpeta `/transporte`. Los CSS y JavaScript locales se sirven desde `/transporte/assets/*`, por lo que no es necesario mover `public/`.

## Actualizar una instalación existente

Haz primero una copia de seguridad desde phpMyAdmin. Si aún no aplicaste `03`, impórtala primero. La migración conserva empresas, usuarios y clientes; amplía sus columnas y crea las tablas del Documento Maestro.

Después ejecuta `scripts/create-super-admin.php`. El instalador crea la empresa BGV o actualiza la existente y crea/restablece la cuenta `superadmin` sin exponer su contraseña. Si la instalación proviene de una versión PHP anterior y todavía no aplicaste la alineación de índices, importa antes `database/migrations/20260817_align_original.sql`.

## Instalación con Docker

```bash
cp .env.example .env
docker compose up --build
```

Abre `http://localhost:8080`. Los scripts `01`, `02` y `03` se ejecutan automáticamente al crear un volumen nuevo. Luego puedes ejecutar `docker compose exec app php scripts/create-super-admin.php`.

## Instalación en hosting/cPanel

1. Crea la base y el usuario MySQL.
2. Importa los scripts `01`, `02` y `03` en orden.
3. Crea `.env` con las credenciales reales.
4. Sube el proyecto completo a `public_html` o a una subcarpeta.
5. Ejecuta `php scripts/create-super-admin.php` desde la terminal del hosting.
6. Verifica PHP 8.2+, `pdo_mysql`, `fileinfo` y `mod_rewrite`.

La raíz ya contiene `index.php`. Su `.htaccess` bloquea el acceso web a `.env`, `app`, `config`, `database` y `storage`, y publica solo los recursos de `public/assets`. Si el hosting permite cambiar el document root, también puede apuntarse a `public/`.

## Módulos incluidos

- Configuración: usuarios, diez roles, aprobaciones, centros de costo, faenas, parámetros y auditoría.
- Comercial: clientes, proveedores, tarifas, cotizaciones, contratos, OC cliente y HES.
- Operación: programación, jornadas, múltiples viajes, eventos, guías y liquidaciones.
- Flota y taller: equipos, propietarios, arriendos, talleres, fallas, OT y combustible.
- Compras y finanzas: solicitudes, OC proveedor, facturas, estados de pago, cobranza, movimientos y presupuestos.
- Personas: trabajadores, turnos, asistencia, horas extra, viáticos y remuneraciones informativas.
- HSE y ambiente: incidentes, inspecciones, acciones correctivas, eventos ambientales y acreditaciones.
- Control documental: archivos, vencimientos, responsables y alertas.
- Centro de Control: KPIs operacionales, cartera, costos, alertas y actividad auditada.

## Roles

Super Administrador, Maestro, Gerencial, Supervisor Operativo, Administrativo, Supervisor Taller, Conductor, RR.HH., Prevencionista y Medio Ambiente. El menú y las operaciones de creación/edición se filtran según el rol activo.

## Rutas principales

- `GET|POST /setup`: configuración inicial.
- `GET|POST /login`, `POST /logout`: autenticación.
- `GET /`: Centro de Control.
- `GET /modules/{modulo}`: listado y búsqueda.
- `GET|POST /modules/{modulo}/create`: creación.
- `GET /modules/{modulo}/{id}`: detalle.
- `GET|POST /modules/{modulo}/{id}/edit`: edición.
- `POST /modules/{modulo}/{id}/archive`: cierre lógico sin borrar trazabilidad.
- `GET /api/v1/docs`: documentación de la API existente.

## Seguridad y trazabilidad

- PDO con consultas preparadas y alcance por empresa.
- Contraseñas con `password_hash`/`password_verify`.
- CSRF en formularios y sesión `HttpOnly`, `SameSite=Lax` y modo estricto.
- Escape HTML centralizado y validación de identificadores SQL.
- Carga documental validada por MIME, tamaño máximo de 15 MB y almacenamiento fuera de `public/`.
- Bitácora antes/después para creación, edición, cierre e inicio/cierre de sesión.
- Registros cerrados o anulados en vez de borrado físico.

## Conexión a la base de datos

La conexión quedó aislada del núcleo MVC en una carpeta exclusiva:

- `app/Database/Connection.php`: crea y reutiliza la conexión PDO.
- `config/database/connection.php`: lee host, puerto, base, usuario y contraseña desde `.env`.

No deben escribirse credenciales dentro de controladores o modelos. Para cambiar la conexión en XAMPP solo se modifica `.env`.

`app/Core/Database.php` y `config/database.php` se conservan únicamente como puentes de compatibilidad para instalaciones actualizadas parcialmente. La conexión oficial continúa en las carpetas separadas indicadas arriba.

### Diagnóstico de error 500

Si XAMPP muestra un error 500 después de actualizar, confirma que existan `app/Database/Connection.php` y `config/database/connection.php`. Temporalmente configura `APP_DEBUG=true` en `.env`, recarga una vez y revisa el diagnóstico mostrado o `storage/logs/app.log`. Al terminar, vuelve a usar `APP_DEBUG=false`.

## Alcance de integraciones

La versión deja preparados datos y flujos internos. Las integraciones bancarias, SII, Previred, firma electrónica, geolocalización, app móvil nativa, IA predictiva y contabilidad estatutaria completa siguen como extensiones posteriores, tal como establece el Documento Maestro.
