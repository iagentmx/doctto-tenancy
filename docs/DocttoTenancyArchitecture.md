# Doctto Tenancy Architecture

## 1. Alcance funcional

El servicio `Doctto-Tenancy` centraliza:

- Ingesta de eventos desde EspoCRM (`/webhook/espocrm/*`).
- Persistencia del dominio tenant y sus entidades operativas.
- Exposición de API interna para integraciones.
- Emisión de eventos de integración hacia servicios externos.

## 2. Módulos

- `app/Modules/EspoCrmTenantIngestion`: adaptación de payloads de EspoCRM y orquestación de actualización local.
- `app/Modules/TenantEntities`: casos de uso de dominio para tenant, locations, services, staff, schedules y relaciones.
- `app/Modules/NotifierEvents`: registro, dispatch y entrega de eventos de integración con patrón Outbox.

### 2.1 Eventos de integración por cambios operativos

El módulo `NotifierEvents` registra eventos de integración cuando ocurre una actualización o eliminación en tablas operativas del dominio.

Tablas operativas que disparan notificación:

- `tenants`
- `tenant_locations`
- `services`
- `staff`
- `resources`
- `schedules`
- `tenant_admins`
- `staff_services`

Tablas excluidas por ser catálogo o clasificación:

- `resource_types`
- `service_categories`

Reglas de ejecución:

- El disparo sale desde observers Eloquent registrados en `AppServiceProvider`.
- La publicación real la resuelve `IntegrationEventBusInterface` mediante `IntegrationEventBus`.
- El pipeline central persiste primero el evento en `integration_event_outbox` y crea una fila por destino en `integration_event_deliveries`.
- La entrega externa ocurre únicamente para eventos `updated` y `deleted`.
- El payload canónico del evento usa el shape:
  `{ "event": "service.updated", "tenant_id": 123, "entity_id": 10, "occurred_at": "2026-03-03T10:15:00Z", "metadata": { "changed_fields": ["name", "price"] } }`.
- En eventos `deleted`, `metadata.changed_fields` se envía como arreglo vacío.
- Los nombres de evento siguen el patrón `{entity}.{action}` para `tenant`, `tenant_location`, `service`, `staff`, `resource`, `schedule`, `tenant_admin` y `staff_service`.
- Para `staff_services`, `entity_id` se deriva de la llave compuesta `(staff_id, service_id)` mediante un hash `crc32`, porque la tabla no tiene `id` propio.
- `changed_fields` se construye desde los cambios detectados por Eloquent y excluye timestamps técnicos (`created_at`, `updated_at`, `deleted_at`).
- Cada evento persistido recibe un `event_uuid` único para idempotencia externa.
- No existe deduplicación en memoria por `event + tenant_id + entity_id`; updates legítimos consecutivos deben registrarse como eventos distintos.
- La integridad por destino la garantiza el índice único `integration_event_outbox_id + destination` en `integration_event_deliveries`.
- Cuando un flujo necesite disparar estos observers, los repositorios no deben usar `update()` o `delete()` masivos sobre esas entidades; deben operar por modelo Eloquent para que `updated` y `deleted` realmente se emitan.
- El pipeline ya no depende de `EspoCrmWebhookRouteDetector` ni de rutas específicas de webhook; cualquier cambio persistido válido puede generar evento.
- El transporte real a destinos externos se resuelve por publishers por destino; en la versión vigente solo existe `n8n` como destino habilitado.
- La entrega inmediata se encola con jobs `afterCommit`; además existe barrido de rescate con el comando `notifier-events:dispatch-pending` programado cada minuto.
- El reproceso manual de deliveries fallidas se realiza con `php artisan notifier-events:retry-deliveries`.
- Las respuestas y errores de entrega se almacenan truncados a 8 KB por campo en `integration_event_deliveries`.
- En flujos críticos con múltiples escrituras de negocio, la persistencia de dominio y la generación de eventos deben ejecutarse dentro de transacciones explícitas; por ejemplo, `UpsertTenantFromAccountUseCase` y `UpsertStaffUseCase`.

### 2.2 Webhooks EspoCRM

Rutas vigentes montadas bajo `/webhook`:

- `POST /webhook/espocrm/account-updated`
- `POST /webhook/espocrm/opportunity-updated`
- `POST /webhook/espocrm/service-created`
- `POST /webhook/espocrm/service-updated`
- `POST /webhook/espocrm/staff-created`
- `POST /webhook/espocrm/staff-updated`

Comportamiento vigente:

- Las rutas se cargan desde `routes/webhook.php`.
- El grupo de webhooks se monta desde `bootstrap/app.php`.
- Actualmente usan middleware `api` y no el grupo `api-secure`.
- `account-updated` sólo actualiza tenants ya existentes; si no existe un tenant con ese `espocrm_id`, el evento se ignora.
- `opportunity-updated` sólo crea o actualiza tenant cuando `stage = 'Closed Won'`.
- `service-updated` y `staff-updated` reconsultan el detalle completo en EspoCRM antes de persistir.
- Cuando EspoCRM envía payload como arreglo con un único objeto (`[{...}]`), el controlador toma el primer elemento.

## 3. Capa de datos (global)

Elementos globales fuera de módulos:

- Modelos Eloquent: `app/Models/*`
- Repositorios: `app/Repositories/*` y `app/Repositories/Contracts/*`
- Enums: `app/Enums/*`
- Migraciones: `database/migrations/*`

## 4. Modelo de datos Tenant

Tablas principales del dominio:

- `tenants`
- `tenant_locations`
- `resource_types`
- `resources`
- `service_categories`
- `services`
- `staff`
- `schedules`
- `staff_services`
- `tenant_admins`
- `integration_event_outbox`
- `integration_event_deliveries`

### 4.1 `tenants`

Propósito: representar la cuenta principal del negocio dentro de Doctto Tenancy.

Campos:

- `id`
- `jid` (identificador público único)
- `name`
- `is_active`
- `espocrm_id` (identificador externo único, nullable)
- `industry_type` (enum de aplicación `IndustryType`, nullable)
- `operation_type` (enum de aplicación `OperationType`)
- `description` (nullable)
- `settings` (jsonb)
- `created_at`, `updated_at`

Constraints:

- `unique(jid)`.
- `unique(espocrm_id)`.

Implementación en código:

- Migración: `create_tenants_table`
- Modelo: `App\Models\Tenant`
- Repositorio: `App\Repositories\TenantRepository`
- Contrato: `App\Repositories\Contracts\TenantRepositoryInterface`
- Enums: `App\Enums\IndustryType`, `App\Enums\OperationType`

Reglas de dominio vigentes:

- `jid` es el identificador público que usan los endpoints `GET /api/v1/tenants/{tenantJid}`.
- `espocrm_id` es la llave de correlación con EspoCRM.
- `settings` se castea a arreglo en Eloquent.
- En el catálogo interno, `settings` no se expone completo; se remapea a `assistant_name`, `url_review_platform` y `features`.

### 4.2 `tenant_locations`

Propósito: representar sedes o ubicaciones operativas de un tenant.

Campos:

- `id`
- `tenant_id` (FK a `tenants.id`)
- `name`
- `address` (nullable)
- `time_zone` (nullable)
- `url_map` (nullable)
- `is_primary`
- `is_active`
- `settings` (jsonb)
- `created_at`, `updated_at`

Constraints:

- `tenant_id` referencia a `tenants.id` con `cascadeOnDelete`.
- `unique(tenant_id, name)`.
- Índice único parcial `tenant_locations_one_primary_per_tenant_idx` para asegurar una sola ubicación primaria por tenant.

Implementación en código:

- Migración: `create_tenant_locations_table`
- Modelo: `App\Models\TenantLocation`
- Repositorio: `App\Repositories\TenantLocationRepository`
- Contrato: `App\Repositories\Contracts\TenantLocationRepositoryInterface`

Reglas de dominio vigentes:

- La ubicación primaria se consulta con la relación `Tenant::primaryLocation()`.
- Los flujos de ingesta desde EspoCRM crean o actualizan la ubicación primaria junto con el tenant dentro de la misma transacción.

### 4.3 `resource_types`

Propósito: catálogo global de tipos de recurso disponibles para `resources`.

Campos:

- `id`
- `name` (string único)
- `is_active`
- `created_at`, `updated_at`

Constraints:

- `unique(name)`.

Implementación en código:

- Migración: `create_resource_types_table`
- Modelo: `App\Models\ResourceType`
- Repositorio: `App\Repositories\ResourceTypeRepository`
- Contrato: `App\Repositories\Contracts\ResourceTypeRepositoryInterface`
- Seeder: `Database\Seeders\ResourceTypeSeeder`

### 4.4 `resources`

Propósito: representar recursos físicos o lógicos que participan en las citas junto con el staff.

Campos:

- `id`
- `tenant_id` (FK a `tenants.id`)
- `tenant_location_id` (FK a `tenant_locations.id`)
- `name`
- `resource_type_id` (FK a `resource_types.id`)
- `description` (nullable)
- `is_active`
- `settings` (jsonb)
- `created_at`, `updated_at`

Constraints:

- `tenant_id` referencia a `tenants.id` con `cascadeOnDelete`.
- `tenant_location_id` referencia a `tenant_locations.id` con `cascadeOnDelete`.
- `resource_type_id` referencia a `resource_types.id` con `restrictOnDelete`.

Implementación en código:

- Migración: `create_resources_table`
- Modelo: `App\Models\Resource`
- Repositorio: `App\Repositories\ResourceRepository`
- Contrato: `App\Repositories\Contracts\ResourceRepositoryInterface`

Reglas de dominio vigentes:

- Cada `resource` pertenece a un `tenant`, a una `tenant_location` y a un `resource_type`.
- Los horarios de recurso se consultan vía relación polimórfica `morphMany` hacia `schedules`.

### 4.5 `service_categories`

Propósito: clasificar servicios por tenant.

Campos:

- `id`
- `tenant_id` (FK a `tenants.id`)
- `name`
- `created_at`, `updated_at`

Constraints:

- `tenant_id` referencia a `tenants.id` con `cascadeOnDelete`.
- `unique(tenant_id, name)`.

Implementación en código:

- Migración: `create_service_categories_table`
- Modelo: `App\Models\ServiceCategory`
- Repositorio: `App\Repositories\ServiceCategoryRepository`
- Contrato: `App\Repositories\Contracts\ServiceCategoryRepositoryInterface`

Reglas de dominio vigentes:

- La categoría se upserta desde la ingesta de EspoCRM antes de persistir el servicio.
- El catálogo `GET /api/v1/tenants/{tenantId}/catalog` expone la colección completa en `service_categories`.

### 4.6 `services`

Propósito: representar servicios ofertados por un tenant.

Campos:

- `id`
- `tenant_id` (FK a `tenants.id`)
- `espocrm_id` (nullable)
- `name`
- `description` (nullable)
- `duration_minutes`
- `price`
- `category_id` (FK nullable a `service_categories.id`)
- `is_active`
- `settings` (jsonb)
- `created_at`, `updated_at`

Constraints:

- `tenant_id` referencia a `tenants.id` con `cascadeOnDelete`.
- `category_id` referencia a `service_categories.id` con `nullOnDelete`.
- `unique(tenant_id, name)`.
- `unique(tenant_id, espocrm_id)`.

Implementación en código:

- Migración: `create_services_table`
- Modelo: `App\Models\Service`
- Repositorio: `App\Repositories\ServiceRepository`
- Contrato: `App\Repositories\Contracts\ServiceRepositoryInterface`

Reglas de dominio vigentes:

- `price` se castea como decimal con dos posiciones.
- La relación con `staff` es many-to-many a través de `staff_services`.
- `service-created` y `service-updated` terminan en el mismo flujo de upsert.

### 4.7 `staff`

Propósito: representar personal operativo del tenant.

Campos:

- `id`
- `tenant_id` (FK a `tenants.id`)
- `espocrm_id` (nullable)
- `name`
- `role` (enum de aplicación `StaffRole`)
- `phone` (nullable)
- `email` (nullable)
- `is_active`
- `settings` (jsonb)
- `created_at`, `updated_at`

Constraints:

- `tenant_id` referencia a `tenants.id` con `cascadeOnDelete`.
- `unique(tenant_id, espocrm_id)`.

Implementación en código:

- Migración: `create_staff_table`
- Modelo: `App\Models\Staff`
- Repositorio: `App\Repositories\StaffRepository`
- Contrato: `App\Repositories\Contracts\StaffRepositoryInterface`
- Enum: `App\Enums\StaffRole`

Reglas de dominio vigentes:

- `settings` se expone parcialmente en lecturas públicas y catálogo: sólo `about` y `specialty`.
- La ingesta de staff resuelve dentro de una transacción: upsert de `staff`, reemplazo de schedules y sincronización de `staff_services`.
- La sincronización de servicios usa los `servicesIds` de EspoCRM contra los servicios locales del mismo tenant.

### 4.8 `schedules`

Propósito: representar horarios de disponibilidad polimórficos para entidades agendables (`staff` y `resource`).

Campos:

- `id`
- `tenant_id` (FK a `tenants.id`)
- `schedulable_type` (string; enum de aplicación `SchedulableType`: `staff|resource`)
- `schedulable_id`
- `tenant_location_id` (FK a `tenant_locations.id`)
- `day_of_week`
- `start_time`
- `end_time`
- `is_active`
- `created_at`, `updated_at`

Constraints:

- `tenant_id` referencia a `tenants.id` con `cascadeOnDelete`.
- `tenant_location_id` referencia a `tenant_locations.id` con `cascadeOnDelete`.
- `check(schedulable_type in ('staff','resource'))`.
- Índice de consulta: `(tenant_id, schedulable_type, schedulable_id, day_of_week, start_time, end_time)`.
- Unicidad: `(tenant_id, schedulable_type, schedulable_id, tenant_location_id, day_of_week, start_time, end_time)`.

Implementación en código:

- Migración: `replace_staff_schedules_with_schedules`
- Modelo: `App\Models\Schedule`
- Repositorio: `App\Repositories\ScheduleRepository`
- Contrato: `App\Repositories\Contracts\ScheduleRepositoryInterface`
- Enum: `App\Enums\SchedulableType`

Reglas de dominio vigentes:

- La tabla sustituyó a `staff_schedules` y migra datos existentes en la misma migración.
- El catálogo interno devuelve una sola colección `schedules` combinando horarios de `staff` y `resource`.
- La respuesta de catálogo agrega `day_name` derivado de `day_of_week`.

### 4.9 `staff_services`

Propósito: representar la relación many-to-many entre `staff` y `services`.

Campos:

- `staff_id` (FK a `staff.id`)
- `service_id` (FK a `services.id`)

Constraints:

- PK compuesta `(staff_id, service_id)`.
- `staff_id` referencia a `staff.id` con `cascadeOnDelete`.
- `service_id` referencia a `services.id` con `cascadeOnDelete`.

Implementación en código:

- Migración: `create_staff_services_table`
- Modelo pivot: `App\Models\StaffService`
- Repositorio: `App\Repositories\StaffServiceRepository`
- Contrato: `App\Repositories\Contracts\StaffServiceRepositoryInterface`

Reglas de dominio vigentes:

- La tabla no tiene `id`, timestamps ni payload adicional.
- El alta y sincronización se realizan desde `UpsertStaffUseCase` por medio de `syncServices`.
- Para eventos de integración, el `entity_id` se deriva de `(staff_id, service_id)` usando `crc32`.

### 4.10 `tenant_admins`

Propósito: representar administradores/backoffice/owner de un tenant, incluyendo el identificador del canal de autenticación.

Campos:

- `id`
- `tenant_id` (FK a `tenants.id`)
- `channel_type` (string; enum de aplicación `TenantAdminChannelType`)
- `jid`
- `role` (string; enum de aplicación `TenantAdminRole`)
- `is_active`
- `settings` (jsonb)
- `created_at`, `updated_at`

Constraints:

- `tenant_id` referencia a `tenants.id` con `cascadeOnDelete`.
- `unique(tenant_id, channel_type, jid)`.
- Índice único parcial `tenant_admins_unique_owner_per_tenant` para `role = 'owner'`.

Implementación en código:

- Migración: `create_tenant_admins_table`
- Modelo: `App\Models\TenantAdmin`
- Repositorio: `App\Repositories\TenantAdminRepository`
- Contrato: `App\Repositories\Contracts\TenantAdminRepositoryInterface`
- Enums: `App\Enums\TenantAdminChannelType`, `App\Enums\TenantAdminRole`

Reglas de dominio vigentes:

- Debe existir como máximo un `tenant_admin` con `role = 'owner'` por `tenant_id`.
- El alta de `tenant_admins` se resuelve desde `App\Modules\TenantEntities` mediante `registerTenantAdmin`.
- El primer `tenant_admin` registrado para un tenant se persiste forzosamente con `role = 'owner'`, aunque el payload solicite `admin`.
- Si el tenant ya tiene owner, cualquier intento de registrar otro `owner` falla con error de dominio `409`.
- Mientras no exista un flujo explícito de transferencia, tampoco se permite degradar al owner vigente a `admin`.

### 4.11 `integration_event_outbox`

Propósito: persistir el evento canónico de integración generado por cambios del dominio antes de intentar cualquier transporte externo.

Campos:

- `id`
- `event_uuid`
- `event_name`
- `tenant_id` (FK a `tenants.id`)
- `entity_type`
- `entity_id`
- `payload` (jsonb)
- `occurred_at`
- `correlation_id` (nullable)
- `source` (nullable)
- `dispatched_at` (nullable)
- `created_at`, `updated_at`

Constraints:

- `unique(event_uuid)`.
- `tenant_id` referencia a `tenants.id` con `cascadeOnDelete`.
- Índices por `(event_name, tenant_id)`, `(entity_type, entity_id)` y `occurred_at`.

Implementación en código:

- Migración: `create_integration_event_outbox_table`
- Modelo: `App\Models\IntegrationEventOutbox`
- Repositorio: `App\Repositories\IntegrationEventOutboxRepository`
- Contrato: `App\Repositories\Contracts\IntegrationEventOutboxRepositoryInterface`

Reglas de dominio vigentes:

- `payload` se castea a arreglo en Eloquent.
- `occurred_at` y `dispatched_at` se castean a datetime.
- La relación `deliveries()` conecta con `integration_event_deliveries`.

### 4.12 `integration_event_deliveries`

Propósito: representar el estado de entrega del evento por destino externo.

Campos:

- `id`
- `integration_event_outbox_id` (FK a `integration_event_outbox.id`)
- `destination`
- `status`
- `attempts`
- `next_retry_at`
- `last_attempt_at`
- `delivered_at`
- `last_error`
- `response_status_code`
- `response_body`
- `created_at`, `updated_at`

Constraints:

- `integration_event_outbox_id` referencia a `integration_event_outbox.id` con `cascadeOnDelete`.
- Índices por `status`, `destination` y `next_retry_at`.
- Índice único por `integration_event_outbox_id + destination`.

Implementación en código:

- Migración: `create_integration_event_deliveries_table`
- Modelo: `App\Models\IntegrationEventDelivery`
- Repositorio: `App\Repositories\IntegrationEventDeliveryRepository`
- Contrato: `App\Repositories\Contracts\IntegrationEventDeliveryRepositoryInterface`
- Enum: `App\Enums\IntegrationEventDeliveryStatus`

Reglas de dominio vigentes:

- `status` usa el enum `pending|processing|delivered|failed`.
- `attempts` y `response_status_code` se castean a entero.
- `next_retry_at`, `last_attempt_at` y `delivered_at` se castean a datetime.

### 4.13 Convención de defaults JSON del dominio

- Las columnas `settings` del dominio Tenant se mantienen como `jsonb` en PostgreSQL.
- El valor por defecto en migraciones base debe declararse con el literal portable `'{}'`; no se permite usar casts crudos exclusivos de PostgreSQL como `::jsonb`.
- Esta convención existe para mantener compatibilidad con los flujos locales y de pruebas que usan `sqlite` por defecto.

### 4.14 Tabla legacy `staff_schedules`

Estado vigente:

- La tabla histórica `staff_schedules` fue reemplazada por `schedules` en la migración `replace_staff_schedules_with_schedules`.
- La migración `create_staff_schedules_table` permanece como antecedente histórico del esquema.
- En instalaciones nuevas, el esquema objetivo es `schedules`; `staff_schedules` no forma parte del modelo operativo vigente.

## 5. Convenciones de repositorio

Los repositorios deben seguir:

- Métodos con patrón `Verbo + Entidad + By...`
- Upsert únicamente como `updateOrCreate{Entity}`
- Sin lógica HTTP en capa de repositorio
- `TenantRepository::findTenantById()` debe cargar el catálogo agregado con relaciones `primaryLocation`, `tenantLocations`, `services.category`, `serviceCategories`, `staff.schedules.tenantLocation`, `staff.services`, `resources.resourceType`, `resources.schedules.tenantLocation` y `tenantAdmins`

## 6. API de lectura tenant

Endpoints vigentes de consulta:

- `GET /api/v1/tenants/{tenantJid}`
- `GET /api/v1/tenants/{tenantId}/catalog`
- `GET /api/v1/tenants/by-espocrm-id/{espocrmId}`

Reglas de acceso:

- Las rutas de lectura usan el grupo middleware `api-secure`.
- La autenticación de estas rutas depende de `App\Http\Middleware\ValidateApiToken`.

### 6.1 Shape de `GET /api/v1/tenants/{tenantJid}`

La respuesta exitosa del controlador usa el envelope:

- `status`: `success`
- `data`: objeto del tenant

El objeto `data` expone identificadores separados:

- `id`: identificador interno del registro `tenants.id`
- `espocrm_id`: identificador externo del tenant en EspoCRM
- `jid`: identificador público usado en la ruta

Reglas de contrato:

- `id` nunca debe reutilizar el valor de `espocrm_id`.
- `espocrm_id` siempre debe enviarse en un campo independiente cuando exista.
- `address`, `time_zone` y `url_map` salen de `primaryLocation`.
- La colección `staff` sólo expone `name`, `phone`, `email`, `settings`, `schedules` y `services`.

### 6.2 Shape de `GET /api/v1/tenants/{tenantId}/catalog`

Endpoint interno de lectura agregada por identificador interno del tenant.

La respuesta exitosa del controlador usa el envelope:

- `status`: `success`
- `data`: objeto con el catálogo completo

El objeto `data` expone:

- `tenant`: datos base del tenant
- `locations`: colección de `tenant_locations`
- `staff`: colección de `staff`
- `services`: colección de `services`
- `service_categories`: colección de `service_categories`
- `tenant_admins`: colección de `tenant_admins`
- `resources`: colección de `resources`
- `schedules`: colección agregada de horarios de `staff` y `resource`

Reglas de contrato:

- La ruta usa `tenantId` interno del registro `tenants.id`.
- `tenant.settings` sólo expone datos útiles de consumo y excluye secretos de integración; actualmente incluye `assistant_name`, `url_review_platform` y `features`.
- `staff.settings` expone únicamente `about` y `specialty`.
- `schedules` se entrega como colección plana con `schedulable_type`, `schedulable_id`, `tenant_location_id`, `day_of_week`, `day_name`, `start_time`, `end_time` e `is_active`.
- `resources[*].resource_type` se expone embebido con `id` y `name`.

### 6.3 Shape de `GET /api/v1/tenants/by-espocrm-id/{espocrmId}`

La respuesta exitosa vigente no usa el mismo envelope que los otros dos endpoints. Actualmente devuelve:

- `status`: `success`
- `message`: `Tenant found successfully.`
- `result`: `tenant->toArray()`

Reglas de contrato:

- La ruta busca por `tenants.espocrm_id`.
- La respuesta actual expone el modelo crudo serializado y no la versión remapeada de catálogo.
- Si este endpoint cambia de envelope en el futuro, este documento debe actualizarse para reflejar el contrato real.
