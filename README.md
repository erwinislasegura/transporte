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
4. Configurar el document root del dominio o subdominio para que apunte a la carpeta `public/`.
5. Confirmar PHP 8.2+ y la extensión `pdo_mysql`.
6. Usar `APP_BASE_PATH=/subcarpeta` solamente si la aplicación se publica dentro de una subcarpeta.

Nunca se debe alojar `.env` dentro del directorio público.

## Rutas principales

- `GET /`: dashboard.
- `GET /clients`: listado de clientes.
- `GET /clients/create`: formulario de creación.
- `POST /clients`: guardar cliente.
- `GET /clients/{id}`: detalle de cliente.
- `GET /api/v1/health`: estado de la API.
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
- Encabezados básicos de seguridad en Apache.
