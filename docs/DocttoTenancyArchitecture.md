## 1. Mapa de flujos

### 1.1. Webhooks **de entrada** (EspoCRM → este servicio)

Rutas definidas en `routes/webhook.php` (montadas con prefijo `/webhook` desde `bootstrap/app.php`):

- `POST /webhook/espocrm/account-updated`
- `POST /webhook/espocrm/opportunity-updated`
- `POST /webhook/espocrm/service-created`
- `POST /webhook/espocrm/service-updated`
- `POST /webhook/espocrm/staff-created`
- `POST /webhook/espocrm/staff-updated`

Todas apuntan a `EspoCrmWebhookController` y sus métodos `accountUpdated`, `opportunityUpdated`, `serviceCreated`, `serviceUpdated`, `staffCreated`, `staffUpdated`.

Cada acción:

- Usa un **FormRequest** específico (`EspoCrmUpdatedRequest`, `EspoCrmOpportunityUpdatedRequest`, `EspoCrmServiceCreatedRequest`, `EspoCrmStaffCreatedRequest`) que soporta tanto payload `{...}` como `[{...}]` (se detecta array y se usa prefijo `*.` en las reglas).
- Normaliza el payload: si viene un array, toma el primer elemento.
- Llama a `EspoCrmServiceInterface` (módulo `App\Modules\EspoCrmTenantIngestion`, implementado por `EspoCrmService`) para procesar el caso de uso: `handleAccountUpdated`, `handleOpportunityUpdated`, `handleServiceCreated`, `handleServiceUpdated`, `handleStaffCreated`, `handleStaffUpdated`.
- Maneja errores diferenciando `EspoCrmWebhookException` (errores esperados de negocio / mapping) de errores inesperados, devolviendo JSON consistente.

En `EspoCrmService` (módulo `EspoCrmTenantIngestion`):

- `handleAccountUpdated`:
    - Valida `id`, busca tenant por `espocrm_id`; si no existe devuelve 200 con “evento ignorado”.
    - Si existe, vuelve a leer el account desde EspoCRM y llama al use case `UpsertTenantFromAccountUseCase::executeUpdateExisting`.
- `handleOpportunityUpdated`:
    - Valida `id` y `stage`, ignora si `stage` ≠ `Closed Won`.
    - Si sí es `Closed Won`, trae la opportunity, toma `accountId`, vuelve a leer el account y llama `UpsertTenantFromAccountUseCase::execute` para crear/actualizar tenant (incluyendo su **primary location**).
- `handleServiceCreated/Updated`, `handleStaffCreated/Updated` usan repositorios y use cases (`UpsertServiceCategoryUseCase`, `UpsertServiceUseCase`, `UpsertStaffUseCase`) para mantener catálogo de servicios y staff vinculados al tenant.

### 1.2. Notificación **hacia n8n** (este servicio → n8n)

#### Event bus de integración hacia n8n

- **Interface:** `IntegrationEventBusInterface` (`App\Modules\N8nNotifierEvents\Contracts`):
    - `publishTenantUpdated(Tenant $tenant): void`.
- **Implementación:** `N8nEventBus` (`App\Modules\N8nNotifierEvents\Services`):
    - Recibe el `Tenant` y decide si realmente debe lanzar un evento hacia n8n.
    - Solo actúa si `EspoCrmWebhookRouteDetector::shouldNotify()` devuelve `true`.
    - Usa `N8nWebhookOnceGuard::shouldSend("tenant:{$jid}")` para no disparar más de una vez por request y por tenant JID.
    - Si pasa ambas condiciones, construye un DTO `TenantUpdated` y lo pasa al handler `N8nTenantUpdatedHandler`.

#### Handler y DTO `TenantUpdated`

- **DTO:** `TenantUpdated` (`App\Modules\N8nNotifierEvents\DTO`):
    - Contiene el `tenantJid` como string, que es la clave que n8n necesita para consultar al API.
- **Handler:** `N8nTenantUpdatedHandler` (`App\Modules\N8nNotifierEvents\Handlers`):
    - Método principal: `handle(TenantUpdated $event): void`.
    - Invoca al cliente HTTP (`N8nClientInterface`) para mandar el webhook a n8n con el payload `['tenant_jid' => $event->tenantJid]`.

#### Cliente HTTP hacia n8n

- **Interface:** `N8nClientInterface` (`App\Modules\N8nNotifierEvents\Contracts`):
    - `postUpdateTenantWebhook(array $payload): void`.
- **Implementación:** `HttpN8nClient` (`App\Modules\N8nNotifierEvents\Infrastructure\Http`):
    - Usa `config('n8n.webhook.update_tenant')` y `config('n8n.api_key')`.
    - Envía `tenant_jid` en el body, con header `x-api-key`.
    - Maneja timeouts configurables y lanza `ApiServiceException` en caso de error HTTP o de conexión.

#### Detección de “de dónde viene” el request

- `EspoCrmWebhookRouteDetector::shouldNotify()` (`App\Modules\N8nNotifierEvents\Support`):
    - Ignora si `app()->runningInConsole()`.
    - Toma `request()->path()` y verifica `Str::endsWith` con las rutas:
        - `espocrm/account-updated`
        - `espocrm/opportunity-updated`
        - `espocrm/service-created`
        - `espocrm/service-updated`
        - `espocrm/staff-created`
        - `espocrm/staff-updated`

    - Es decir, **solo las operaciones de creación/actualización** provenientes de EspoCRM pueden disparar la notificación hacia n8n.

- `N8nWebhookOnceGuard` (`App\Modules\N8nNotifierEvents\Support`):
    - Mantiene un registro estático por request de las claves ya enviadas y evita duplicados (`tenant:{tenantJid}`, etc.).

#### Observers que disparan la notificación

En `AppServiceProvider::boot()` se registran observers para `Tenant`, `Service`, `Staff` y `TenantLocation`, todos inyectando `IntegrationEventBusInterface`:

- `TenantObserver::saved`:
    - Toma `tenant->jid`; si existe, llama a `IntegrationEventBusInterface::publishTenantUpdated($tenant)`.
- `ServiceObserver::saved`:
    - Carga `tenant`, toma `tenant->jid` y llama a `publishTenantUpdated($service->tenant)`.
- `StaffObserver::saved`:
    - Igual que el de Service pero con `Staff`.
- `TenantLocationObserver::saved`:
    - Obtiene el tenant asociado a la ubicación y llama a `publishTenantUpdated($tenantLocation->tenant)`.

En todos los casos, el EventBus aplica de nuevo la lógica de ruta (`EspoCrmWebhookRouteDetector`) y el guard de “una sola vez por request” antes de llegar al handler de n8n.

### 1.3. API de consulta para n8n (n8n → este servicio)

En `routes/api.php`:

- `GET /api/v1/tenants/{tenantJid}`
- `GET /api/v1/tenants/by-espocrm-id/{espocrmId}` con middleware `api-secure`.

`TenantEntitiesService::getByJid` usa `TenantRepository::findTenantByJid` que eagerly carga:

- `services`, `staff`, `staff.schedules`, `staff.services`, `primaryLocation`.

Esto se alinea con el uso típico desde n8n: recibir solo `tenant_jid` en el webhook y luego ir a `/api/v1/tenants/{tenantJid}` para obtener toda la estructura para el agente.

---

## 2. Riesgos y consideraciones técnicas detectadas

### 2.1. Notificación a n8n dentro de transacción (riesgo, no bug)

En `UpsertTenantFromAccountUseCase` se usa `DB::transaction` para upsertear Tenant y primary location:

- Los observers (`saved`) se disparan **dentro** de esa transacción.
- Esos observers llaman al `IntegrationEventBusInterface::publishTenantUpdated($tenant)`, que eventualmente puede disparar el webhook hacia n8n.

Riesgo:

- Si algo revierte la transacción después de publicar el evento (por cualquier cambio futuro en la lógica), n8n habrá recibido un webhook de un cambio que realmente nunca se confirmó en BD.

Hoy no parece que se estén haciendo rollbacks manuales complejos, así que **no es un bug práctico**, pero:

- Si en el futuro se amplía la lógica dentro de esa transacción, podría valer la pena mover la notificación a un hook “after commit” o a un job en cola para evitar inconsistencias temporales entre n8n y la BD.

### 2.2. Seguridad de los webhooks de EspoCRM

- Los endpoints `/webhook/espocrm/*` definidos en `routes/webhook.php` **no tienen middleware explícito** (tipo `api-secure` o firma).
- Los endpoints de API para n8n (`/tenants/*`) sí van con middleware `api-secure`.

Esto no rompe nada funcionalmente, pero a nivel de “auditoría”:

- Si el servicio está expuesto públicamente, cualquier tercero podría pegar a esas URLs y tratar de simular eventos de EspoCRM.
- Lo normal sería:
    - Restringir por IP (nivel Nginx o LB) **y/o**
    - Añadir un header firmado / token compartido entre Espo y este servicio.

---

## 3. Tablas y modelo de dominio

### 3.1. Tablas principales

- **`tenants`**
    - Campos clave: `id`, `jid` (string, único), `name`, `is_active`, `espocrm_id` (único y con índice), `industry_type`, `operation_type`, `description`, `settings` (JSONB), timestamps.
    - `jid` es el identificador estable que usa n8n; `espocrm_id` enlaza con EspoCRM.
    - `operation_type` se castea a `OperationType` y `industry_type` a `IndustryType`.

- **`tenant_locations`**
    - Campos clave: `id`, `tenant_id` (FK a `tenants`), `name`, `address`, `time_zone`, `url_map`, `is_primary`, `is_active`, `settings` (JSONB), timestamps.
    - Restricciones típicas:
        - `unique(['tenant_id', 'name'])`.
        - Índice único parcial para garantizar **solo una ubicación primaria** por tenant (`WHERE is_primary = true`).

- **`service_categories`**
    - Campos: `id`, `tenant_id` (FK a `tenants`), `name`, timestamps.
    - Restricción: `unique(['tenant_id', 'name'])`.

- **`services`**
    - Campos clave: `id`, `tenant_id` (FK), `espocrm_id`, `name`, `description`, `duration_minutes`, `price`, `category_id` (FK a `service_categories`, `nullOnDelete`), `is_active`, `settings` (JSONB), timestamps.
    - Restricciones:
        - `unique(['tenant_id', 'name'])`.
        - `unique(['tenant_id', 'espocrm_id'])`.
    - Casts: `price` a `decimal:2`, `is_active` boolean, `settings` array.

- **`staff`**
    - Campos clave: `id`, `tenant_id` (FK), `espocrm_id`, `name`, `role`, `phone`, `email`, `is_active`, `settings` (JSONB), timestamps.
    - Restricción: `unique(['tenant_id', 'espocrm_id'])`.
    - Casts: `role` a `StaffRole`, `is_active` boolean, `settings` array.

- **`staff_schedules`**
    - Campos clave: `id`, `staff_id` (FK a `staff`), `tenant_location_id` (FK a `tenant_locations`), `day_of_week` (`1=Lun` … `7=Dom`), `start_time`, `end_time`, `is_active`, timestamps.
    - Restricción: `unique(['staff_id', 'tenant_location_id', 'day_of_week', 'start_time', 'end_time'])`.

- **`staff_services`** (tabla pivote)
    - Campos: `staff_id` (FK a `staff`), `service_id` (FK a `services`).
    - PK compuesta: `primary(['staff_id', 'service_id'])`.

### 3.2. Relaciones de dominio

- **Tenant**
    - `hasMany(Service::class)` → `services`.
    - `hasMany(ServiceCategory::class)` → `serviceCategories`.
    - `hasMany(Staff::class)` → `staff`.
    - `hasMany(TenantLocation::class)` → `tenantLocations`.
    - `hasOne(TenantLocation::class)->where('is_primary', true)` → `primaryLocation`.

- **TenantLocation**
    - `belongsTo(Tenant::class)` → `tenant`.

- **ServiceCategory**
    - `belongsTo(Tenant::class)` → `tenant`.
    - `hasMany(Service::class)` → `services`.

- **Service**
    - `belongsTo(Tenant::class)` → `tenant`.
    - `belongsTo(ServiceCategory::class)` → `category`.
    - `belongsToMany(Staff::class, 'staff_services')` → `staff`.

- **Staff**
    - `belongsTo(Tenant::class)` → `tenant`.
    - `belongsToMany(Service::class, 'staff_services')` → `services`.
    - `hasMany(StaffSchedule::class)` → `schedules`.

- **StaffSchedule**
    - `belongsTo(Staff::class)` → `staff`.
    - `belongsTo(TenantLocation::class)` → `tenantLocation`.

### 3.3. Enums de dominio

- **`IndustryType`** (`App\Enums\IndustryType`)
    - `Healthcare`, `Other`.
- **`OperationType`** (`App\Enums\OperationType`)
    - `single_staff`, `multi_staff`, `multi_resource`, `multi_location`.
    - Se usa en `tenants.operation_type` para describir el modelo operativo del negocio.
- **`StaffRole`** (`App\Enums\StaffRole`)
    - `doctor`, `stylist`, `therapist`, `mechanic`, `consultant`.
    - Se usa en `staff.role` para especializar al staff.

### 3.4. Campos JSONB y extensiones

- **Campos `settings`**:
    - `tenants.settings`, `tenant_locations.settings`, `services.settings`, `staff.settings` son JSONB, casteados a `array` en Eloquent para configuración flexible por tenant/ubicación/servicio/staff.
- **Extensiones e índices Postgres** (según migración de extensiones):
    - `CREATE EXTENSION IF NOT EXISTS pgcrypto` (para futuros UUIDs).
    - `CREATE EXTENSION IF NOT EXISTS unaccent`.
    - `CREATE EXTENSION IF NOT EXISTS pg_trgm`.
    - Función SQL `immutable_unaccent(text)` para permitir estrategias de indexación/búsqueda acento-insensible.
    - Índices GIN típicos:
        - `idx_tenants_settings_gin` en `tenants(settings)`.
        - `idx_services_settings_gin` en `services(settings)`.
        - `idx_staff_settings_gin` en `staff(settings)`.

---

## 4. Contratos externos (APIs)

### 4.1. Cliente HTTP hacia EspoCRM

**Interface:** `EspoCrmClientInterface` (`App\Modules\EspoCrmTenantIngestion\Contracts`):

- `getAccountById(string $id): array`
- `getOpportunityById(string $id): array`
- `getServiceById(string $id): array`
- `getStaffById(string $id): array`

**Implementación:** `HttpEspoCrmClient` (`App\Modules\EspoCrmTenantIngestion\Infrastructure\Http`):

- Configuración vía `EspoCrmConfigProviderInterface` (`espocrm.base_url`, `espocrm.username`, `espocrm.password`, `espocrm.timeout_seconds`).
- Autenticación: **Basic Auth**.
- Endpoints REST consumidos:
    - `GET /api/v1/Account/{id}`
    - `GET /api/v1/Opportunity/{id}`
    - `GET /api/v1/CService/{id}`
    - `GET /api/v1/CStaff/{id}`
- Headers:
    - `Accept: application/json`
    - `Content-Type: application/json`
- Manejo de errores:
    - Lanza `ApiServiceException` si el `id` es inválido, hay error de conexión o EspoCRM responde con error.
    - Intenta extraer mensaje de error de `message` o `error` en el JSON.

### 4.2. Cliente HTTP hacia n8n

**Interface:** `N8nClientInterface` (`App\Modules\N8nNotifierEvents\Contracts`):

- `postUpdateTenantWebhook(array $payload): void`.

**Implementación:** `HttpN8nClient` (`App\Modules\N8nNotifierEvents\Infrastructure\Http`):

- Configuración:
    - URL desde `config('n8n.webhook.update_tenant')`.
    - API key desde `config('n8n.api_key')`.
- Request:
    - Método: `POST`.
    - Body típico: `['tenant_jid' => '...']`.
    - Header: `x-api-key: {api_key}`.
    - Timeouts basados en `config('n8n.connect_timeout')` y `config('n8n.timeout')`.
- Errores:
    - `ApiServiceException` si la URL o API key no están configuradas, si hay fallo de conexión o si la respuesta no es `2xx`.
    - Logging detallado (`status`, `body`, `payload`) cuando n8n responde error.

### 4.3. API propia expuesta a n8n

- Endpoints:
    - `GET /api/v1/tenants/{tenantJid}` → devuelve un `Tenant` con `services`, `staff`, `staff.schedules`, `staff.services`, `primaryLocation`.
    - `GET /api/v1/tenants/by-espocrm-id/{espocrmId}` → búsqueda por `espocrm_id`.
- Seguridad:
    - Protegidos con middleware `api-secure` (`ValidateApiToken`).
    - Requiere header `X-Api-Token` con el mismo valor de `APP_API_TOKEN`.

---

## 5. Reglas transversales y manejo de errores

- **Observers + EventBus hacia n8n**
    - `TenantObserver`, `ServiceObserver`, `StaffObserver`, `TenantLocationObserver` centralizan la publicación de eventos de tipo “tenant actualizado” al `IntegrationEventBusInterface` cada vez que se persiste algo relevante del tenant.
    - El `N8nEventBus` solo reenvía al handler de n8n si:
        - La petición actual proviene de alguna ruta `/espocrm/...` (`EspoCrmWebhookRouteDetector::shouldNotify()`).
        - `N8nWebhookOnceGuard::shouldSend("tenant:{jid}")` devuelve `true` (garantiza **máximo un webhook por tenant y request**).

- **Detección de origen de request**
    - `EspoCrmWebhookRouteDetector`:
        - Ignora ejecución en consola.
        - Usa `request()->path()` + `Str::endsWith` para detectar si el flujo viene de un webhook de EspoCRM.

- **Errores y logging**
    - Errores de integración externa (`EspoCRM`, `n8n`) se encapsulan en `ApiServiceException`.
    - Errores de negocio / mapping en los webhooks se encapsulan en `EspoCrmWebhookException` y se devuelven al cliente con status controlado.
    - Errores inesperados se loguean con `message`, `trace` y contexto (ids relevantes) y se responde con HTTP 500.
