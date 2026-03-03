# Doctto Tenancy

Servicio Laravel 12 para gestionar entidades de tenant y sincronizar datos entre EspoCRM, la base de datos local y n8n.

## Propósito

Este proyecto resuelve tres flujos principales:

1. Ingesta de webhooks desde EspoCRM (`/webhook/espocrm/*`).
2. Persistencia/actualización del dominio Tenant (tenant, locations, services, staff, schedules).
3. Notificación de cambios a n8n y exposición de API de consulta para integraciones.

## Arquitectura (fuente de verdad)

Antes de modificar código del dominio Tenant, revisar:

- `docs/DocttoTenancyArchitecture.md` (fuente de verdad funcional de Tenants, webhooks, CRM, n8n, API y modelo de datos).
- `docs/LineamientosModulos.md`
- `docs/LineamientosTenantEntities.md`
- `docs/LineamientosRepositorios.md`
- `docs/LineamientosEndpoints.md`

## Módulos principales

- `app/Modules/EspoCrmTenantIngestion`: ingestión y normalización de eventos EspoCRM.
- `app/Modules/TenantEntities`: casos de uso del dominio TenantEntities.
- `app/Modules/N8nNotifierEvents`: publicación de eventos de integración hacia n8n.

## Endpoints relevantes

### API interna (protegida con `api-secure`)

- `GET /api/v1/tenants/{tenantJid}`
- `GET /api/v1/tenants/by-espocrm-id/{espocrmId}`

### Webhooks EspoCRM

- `POST /webhook/espocrm/account-updated`
- `POST /webhook/espocrm/opportunity-updated`
- `POST /webhook/espocrm/service-created`
- `POST /webhook/espocrm/service-updated`
- `POST /webhook/espocrm/staff-created`
- `POST /webhook/espocrm/staff-updated`

## Requisitos

- PHP `^8.2`
- Composer
- Node.js + npm
- PostgreSQL

## Configuración local

1. Crear configuración local:

```bash
cp .env.example .env
```

2. Ajustar variables de entorno (mínimo):

- `APP_URL`
- `APP_API_TOKEN`
- `DB_*`
- `ESPOCRM_*`
- `N8N_*`

3. Instalar dependencias y preparar entorno:

```bash
composer setup
```

## Ejecución en desarrollo

```bash
composer dev
```

Este comando levanta servidor Laravel, cola, logs y Vite en paralelo.

## Ejecución con Docker

Prerequisitos:

- Docker
- Docker Compose (comando `docker compose`)

Variables útiles en `.env` para contenedores:

- `APP_PORT` (default `8001`): puerto público del contenedor app.
- `DB_PORT_EXTERNAL` (default `5481`): puerto externo de PostgreSQL.
- `BUILD_TARGET` (default `development`): stage del `Dockerfile`.

Levantar stack (app + db + queue + scheduler):

```bash
docker compose up -d --build
```

Notas:

- El contenedor `doctto-tenancy-app` ejecuta migraciones automáticamente al iniciar (`php artisan migrate --force`).
- La app queda disponible en `http://localhost:${APP_PORT}` (si no defines `APP_PORT`, usa `http://localhost:8001`).

Verificar estado:

```bash
docker compose ps
curl -fsS http://localhost:${APP_PORT:-8001}/up
```

Ver logs:

```bash
docker compose logs -f doctto-tenancy-app
```

Levantar también Nginx (perfil producción):

```bash
docker compose --profile production up -d --build
```

Con ese perfil, Nginx expone `80/443` (configurable con `NGINX_PORT` y `NGINX_SSL_PORT`).

## Pruebas

```bash
composer test
```

Resultado esperado: suite de tests en verde y código de salida `0`.

## Estructura base

```text
app/
  Modules/
  Http/
  Repositories/
  Models/
routes/
  api.php
  webhook.php
docs/
```

## Notas operativas

- El identificador estable para integraciones es `tenant.jid`.
- El vínculo con EspoCRM se realiza por `espocrm_id`.
- Si cambias comportamiento del dominio Tenant o de sus flujos de integración, actualiza `docs/DocttoTenancyArchitecture.md` en el mismo cambio.
