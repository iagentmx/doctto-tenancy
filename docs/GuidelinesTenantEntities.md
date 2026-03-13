# Lineamientos de arquitectura `TenantEntities`

## 1. Objetivo

Establecer el alcance real del dominio `TenantEntities` y las reglas para mantener consistente la documentación y el código relacionado con las tablas tenant vigentes.

Este dominio abarca actualmente:

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

`staff_schedules` ya no forma parte del modelo vigente. Fue reemplazada por `schedules` con relación polimórfica para `staff` y `resources`.

---

## 2. Estructura real del módulo

La estructura actual de `app/Modules/TenantEntities` es:

```text
app/Modules/TenantEntities/
  Contracts/
    TenantEntitiesServiceInterface.php

  DTO/
    StaffData.php
    TenantAdminData.php

  UseCases/
    Staff/
      CreateStaff.php
      DeleteStaff.php
      GetStaff.php
      ListStaff.php
      UpdateStaff.php
    TenantAdmins/
      RegisterTenantAdmin.php

  Services/
    TenantEntitiesService.php

  Providers/
    TenantEntitiesServiceProvider.php
```

Regla:

- La guía debe reflejar la estructura real del módulo, no una estructura ideal o futura.
- Si el módulo se expande, primero se implementa el cambio y después se actualiza este documento para describir únicamente lo que ya existe.

---

## 3. Alcance funcional vigente

### 3.1. Lecturas agregadas del catálogo tenant

`TenantEntitiesServiceInterface` expone actualmente estas lecturas:

- `getByJid(string $jid): array`
- `getCatalogByTenantId(int $tenantId): array`
- `getByEspoCrmId(string $espocrmId): array`
- `listStaffByTenantJid(string $tenantJid): array`
- `getStaffByTenantJidAndId(string $tenantJid, int $staffId): array`

Estas operaciones pueden devolver información combinada de:

- `tenants`
- `tenant_locations`
- `services`
- `service_categories`
- `staff`
- `staff_services`
- `resources`
- `resource_types`
- `schedules`
- `tenant_admins`

### 3.2. Escrituras vigentes dentro del módulo

Actualmente el módulo encapsula estos flujos de escritura:

- Registro / actualización de `tenant_admins` mediante `registerTenantAdmin(TenantAdminData $tenantAdminData): array`
- CRUD de `staff` mediante:
  - `createStaff(string $tenantJid, StaffData $staffData): array`
  - `updateStaff(string $tenantJid, int $staffId, StaffData $staffData): array`
  - `deleteStaff(string $tenantJid, int $staffId): void`

La lógica de negocio asociada vive en:

- `UseCases/Staff/CreateStaff.php`
- `UseCases/Staff/UpdateStaff.php`
- `UseCases/Staff/DeleteStaff.php`
- `UseCases/Staff/ListStaff.php`
- `UseCases/Staff/GetStaff.php`
- `UseCases/TenantAdmins/RegisterTenantAdmin.php`

Reglas vigentes de estos flujos:

- La unicidad funcional de `tenant_admins` se resuelve por `tenant_id + channel_type + jid`.
- Si el tenant aún no tiene owner, el primer admin registrado se promueve a `owner`.
- No se permite degradar al owner actual sin un flujo explícito de transferencia.
- No se permite crear un segundo `owner` activo para el mismo tenant.
- El CRUD de `staff` resuelve el `tenant_id` desde `tenantJid`; el cliente no controla ese campo.
- El CRUD de `staff` sólo edita `name`, `role`, `phone`, `email`, `is_active`, `settings.about` y `settings.specialty`.
- `espocrm_id`, `service_ids` y `schedules` quedan fuera de escritura en este flujo.
- La eliminación de `staff` es hard delete y depende de los cascades del esquema vigente.
- Las escrituras de `staff` deben persistirse por modelo Eloquent para no romper los observers `updated` y `deleted`.

---

## 4. Tablas del dominio y su papel

### 4.1. Tablas principales operativas

- `tenants`: raíz del dominio tenant.
- `tenant_locations`: sedes operativas del tenant.
- `services`: servicios ofrecidos por el tenant.
- `staff`: personal operativo del tenant.
- `resources`: recursos físicos o lógicos asociados al tenant.
- `schedules`: horarios polimórficos para `staff` y `resources`.
- `staff_services`: relación N:M entre `staff` y `services`.
- `tenant_admins`: administradores por canal del tenant.

### 4.2. Tablas catálogo o clasificación

- `resource_types`: catálogo global de tipos de recurso.
- `service_categories`: clasificación de servicios por tenant.

### 4.3. Tablas de integración

- `integration_event_outbox`: outbox de eventos de integración por cambios operativos.
- `integration_event_deliveries`: estado y reintentos de entrega por destino.

Regla:

- Aunque `integration_event_outbox` e `integration_event_deliveries` no pertenecen al CRUD operativo clásico del tenant, forman parte del modelo de datos del dominio documentado en `DocttoTenancyArchitecture.md` y deben considerarse al describir el ecosistema Tenant.

---

## 5. Qué sí va dentro del módulo

### 5.1. Contracts

- Interfaces públicas del módulo.
- Hoy sólo existe `TenantEntitiesServiceInterface`.
- Controladores, jobs y otros módulos deben depender de este contrato cuando consuman el catálogo agregado tenant, el CRUD de `staff` o el registro de `tenant_admins`.

### 5.2. DTO

- Objetos de transporte para operaciones del módulo.
- Hoy existen `TenantAdminData` y `StaffData`.
- No deben mezclarse `Request` HTTP ni modelos Eloquent dentro del DTO.

### 5.3. UseCases

- Encapsulan reglas de negocio específicas del módulo.
- Hoy existen `TenantAdmins/RegisterTenantAdmin` y `Staff/{Create,Update,Delete,List,Get}Staff`.
- Cualquier nueva escritura del dominio dentro de este módulo debe vivir en un caso de uso explícito antes de exponerse desde la fachada.

### 5.4. Services

- `TenantEntitiesService` es la fachada del módulo.
- Implementa `TenantEntitiesServiceInterface`.
- Coordina consultas agregadas sobre modelos y repositorios globales.
- Coordina los casos de uso `RegisterTenantAdmin` y el CRUD de `staff`.

### 5.5. Providers

- `TenantEntitiesServiceProvider` concentra los bindings del módulo hacia el contenedor.

---

## 6. Qué NO va dentro del módulo

Sigue prohibido meter dentro de `app/Modules/TenantEntities`:

- Modelos Eloquent.
- Migraciones.
- Repositorios e interfaces de repositorio globales.
- Enums globales.
- Controladores, Requests o Responses HTTP.

Estos elementos siguen viviendo en:

- `app/Models/`
- `database/migrations/`
- `app/Repositories/`
- `app/Repositories/Contracts/`
- `app/Enums/`
- `app/Http/Controllers/`

---

## 7. Regla de dependencias

Orden esperado:

1. Capa HTTP / jobs / listeners
2. `TenantEntitiesServiceInterface`
3. `TenantEntitiesService`
4. UseCases del módulo
5. Repositorios globales
6. Modelos Eloquent / DB

Reglas:

- El módulo puede apoyarse en modelos y repositorios globales, pero no redefinirlos.
- Las reglas de negocio del módulo no deben quedar dispersas en controladores.
- Las lecturas agregadas del tenant deben mantener consistencia con el modelo real: `schedules` sustituye a `staff_schedules`, y los horarios pueden pertenecer a `staff` o `resources`.

---

## 8. Regla de actualización documental

Cada vez que cambie alguna de estas condiciones, este documento debe actualizarse:

- Se agrega o elimina una tabla del dominio tenant.
- Cambia una tabla vigente por otra (`staff_schedules` -> `schedules`, por ejemplo).
- Se amplía la superficie pública de `TenantEntitiesServiceInterface`.
- Se agregan nuevos DTO o UseCases dentro de `app/Modules/TenantEntities`.

La lista de tablas y responsabilidades aquí descrita debe mantenerse alineada con:

- `database/migrations/*`
- `app/Models/*`
- `docs/DocttoTenancyArchitecture.md`
