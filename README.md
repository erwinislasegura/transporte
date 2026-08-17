# BGV Enterprise · PHP MVC

Migración del proyecto Transporte a PHP MVC y MySQL, conservando la interfaz visual original.

## Requisitos

- PHP 8.2 o superior con `pdo_mysql`
- MySQL 8 o MariaDB 10.6+
- Apache con `mod_rewrite` (recomendado)

No requiere Composer ni Node.js.

## Instalación local con Docker

```bash
cp .env.example .env
docker compose up --build
```

Abrir `http://localhost:8080`. MySQL crea las tablas y la empresa base la primera vez que se inicia el volumen.

## Instalación en cPanel

1. Crear una base de datos MySQL y un usuario desde **MySQL Databases**.
2. Importar `database/01-schema.sql` y `database/02-seed.sql` desde phpMyAdmin.
3. Copiar `.env.example` como `.env` y completar las credenciales reales.
4. Subir todo el proyecto al directorio del dominio. El `index.php` y `.htaccess` de la raíz permiten ejecutarlo directamente desde `public_html` y protegen las carpetas internas.
5. Como alternativa más estricta, configurar el document root para que apunte a `public/`; ambos modos están soportados.
6. Confirmar PHP 8.2+, `pdo_mysql`, `mod_rewrite` y que cPanel permita reglas `.htaccess`.
7. `APP_BASE_PATH` puede quedar vacío: la aplicación detecta automáticamente `/transporte` al ejecutarse desde `htdocs/transporte`. También puede definirse manualmente como `/transporte`.

El `.htaccess` de la raíz bloquea el acceso web a `.env`, `app`, `config`, `database` y `storage`. Siempre que el hosting permita elegir el document root, apuntar a `public/` continúa siendo la opción más aislada.

## Comprobación de estilos

La vista genera las rutas con `APP_BASE_PATH` o con la subcarpeta detectada automáticamente:

- Dominio raíz: `/assets/styles.css` y `/assets/app.js`.
- Subcarpeta con `APP_BASE_PATH=/transporte`: `/transporte/assets/styles.css` y `/transporte/assets/app.js`.
- Document root en `public/`: los archivos existen físicamente dentro de `public/assets/`.
- Document root en la raíz: `.htaccess` reescribe internamente `assets/*` hacia `public/assets/*`.

## Rutas principales

- `GET /`: dashboard.
- `GET /clients`: listado de clientes.
- `GET /clients/create`: formulario de creación.
- `POST /clients`: guardar cliente.
- `GET /clients/{id}`: detalle de cliente.
- `GET /api/v1/health`: estado de la API.
- `GET /api/v1`: información base de la API.
- `GET /api/v1/docs`: documentación navegable.
- `GET /api/v1/openapi.json`: especificación OpenAPI.
- `GET|POST /api/v1/clients`: listar o crear clientes.
- `GET /api/v1/clients/{id}`: consultar un cliente.

## Arquitectura

- `app/Controllers`: controladores web y API.
- `app/Models`: acceso a MySQL mediante PDO.
- `app/Views`: vistas PHP del dashboard y clientes.
- `app/Core`: router, conexión, entorno, CSRF y controlador base.
- `public`: único directorio expuesto por el servidor web.
- `database`: esquema y datos iniciales.

## Seguridad incluida

- Consultas preparadas con PDO.
- Escape HTML centralizado.
- Protección CSRF para formularios.
- Cookies de sesión `HttpOnly` y `SameSite=Lax`.
- Contraseñas mediante `password_hash` y `password_verify`.
- Creación de tokens JWT HS256 equivalente a la utilidad del backend original.
- CORS configurable mediante `CORS_ORIGINS` y soporte de solicitudes `OPTIONS`.
- Encabezados básicos de seguridad en Apache.

## Paridad con el proyecto original

El código original implementaba únicamente Dashboard, Clientes, salud de API, modelos de empresa/usuario y utilidades de seguridad. La versión PHP conserva esas funciones. Los módulos de flota, trabajadores, viajes, facturación, taller, RR. HH., prevención, documentos y BI solo estaban enumerados en `docs/ROADMAP.md`; no existían como funciones ni pantallas en el código original.
