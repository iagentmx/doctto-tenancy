# Lineamientos de arquitectura `TenantEntities`

## 1. Objetivo

Establecer reglas claras para que cualquier refactor o nueva funcionalidad relacionada con:

- `tenant_locations`
- `services`
- `staff`
- `staff_schedules`
- `staff_services`
- y consulta de `tenants` (solo lectura)

respete la arquitectura de módulos aprobada para `TenantEntities`.

---

## 2. Estructura de carpetas

La estructura de `app/Modules/TenantEntities`:

```text
app/Modules/TenantEntities/
  Contracts/
    TenantEntitiesServiceInterface.php

  DTO/
    TenantLocationData.php
    ServiceData.php
    StaffData.php
    StaffScheduleData.php
    StaffServiceData.php

  UseCases/
    TenantLocations/
      CreateTenantLocation.php
      UpdateTenantLocation.php
      DeleteTenantLocation.php
      ListTenantLocations.php

    Services/
      CreateService.php
      UpdateService.php
      DeleteService.php
      ListServices.php

    Staff/
      CreateStaff.php
      UpdateStaff.php
      DeleteStaff.php
      ListStaff.php

    StaffSchedules/
      CreateStaffSchedule.php
      UpdateStaffSchedule.php
      DeleteStaffSchedule.php
      ListStaffSchedules.php

    StaffServices/
      AssignStaffService.php
      UpdateStaffService.php
      RemoveStaffServices.php
      ListStaffServices.php

    Tenants/
      GetTenant.php
      GetTenantWithEntities.php

  Services/
    TenantEntitiesService.php

  Providers/
    TenantEntitiesServiceProvider.php
```

---

## 3. Qué sí va dentro del módulo

### 3.1. Contracts

- Interfaces públicas del módulo:
    - `TenantEntitiesServiceInterface`

- Es el **único contrato** que deben usar controladores, jobs, listeners, webhooks, n8n, etc., cuando hablen de:
    - CRUD de:
        - `tenant_locations`
        - `services`
        - `staff`
        - `staff_schedules`
        - `staff_services`

    - Consultas de:
        - `tenants` (solo lectura).

---

### 3.2. DTO

- Objetos de transporte de datos para las entidades de este dominio:
    - `TenantLocationData`
    - `ServiceData`
    - `StaffData`
    - `StaffScheduleData`
    - `StaffServiceData`

- Se utilizan para agrupar datos de entrada/salida de UseCases y de la fachada del módulo.
- No se mezclan `Request` de HTTP ni modelos dentro de estos DTO.

---

### 3.3. UseCases

- Una clase por operación de negocio **específica**, organizada por subcarpeta de entidad.

- Para cada grupo:
    - `TenantLocations/`:
        - Crear, actualizar, eliminar, listar ubicaciones.

    - `Services/`:
        - Crear, actualizar, eliminar, listar servicios.

    - `Staff/`:
        - Crear, actualizar, eliminar, listar staff.

    - `StaffSchedules/`:
        - Crear, actualizar, eliminar, listar horarios.

    - `StaffServices/`:
        - Asignar, actualizar, remover, listar asignaciones staff-servicio.

    - `Tenants/`:
        - Consultar tenant, consultar tenant con entidades relacionadas.

- La lógica de negocio del dominio de “estructura operativa del tenant” debe vivir dentro de estos casos de uso o coordinada por la fachada.

---

### 3.4. Services

- `TenantEntitiesService.php`:
    - Es la **fachada** del módulo.
    - Implementa `TenantEntitiesServiceInterface`.
    - Agrupa y expone los métodos que el resto de la aplicación va a consumir para este dominio.
    - Internamente coordina llamadas a UseCases y uso de DTOs.

> Cualquier nueva operación para este dominio debe aparecer primero en la interfaz `TenantEntitiesServiceInterface` y luego en `TenantEntitiesService`.

---

### 3.5. Providers

- `TenantEntitiesServiceProvider.php`:
    - Es el único lugar dentro del módulo donde se declaran los bindings del módulo hacia el container.
    - Se encarga de vincular la interfaz `TenantEntitiesServiceInterface` con la implementación `TenantEntitiesService`.

---

## 4. Qué NO va dentro del módulo (nunca)

Queda prohibido meter dentro de `app/Modules/TenantEntities`:

- Modelos Eloquent (`Tenant`, `TenantLocation`, `Service`, etc.).
- Migraciones (todas siguen en `database/migrations/`).
- Enums globales (`StaffRole`, `ServiceType`, etc.).
- Repositorios (interfaces e implementaciones).
- Lógica HTTP (Requests, Responses, controladores).

Estos elementos deben seguir viviendo en sus capas globales:

- Modelos: `app/Models/`
- Migraciones: `database/migrations/`
- Enums: `app/Enums/`
- Repositorios: `app/Repositories/...`
- Controladores: `app/Http/Controllers/...`

El módulo **solo** los usa, no los define.

---

## 5. Regla de dependencias

Orden de dependencias esperado:

1. Controladores / capa HTTP
2. `TenantEntitiesServiceInterface` (contrato del módulo)
3. `TenantEntitiesService` (implementación de la fachada)
4. UseCases bajo `UseCases/`
5. Repositorios (interfaces globales)
6. Repositorios Eloquent
7. Modelos / DB

Reglas:

- Controladores NO deben hablar directamente con repositorios para estas entidades; siempre pasan por `TenantEntitiesServiceInterface`.
- UseCases NO interactúan con HTTP ni devuelven respuestas HTTP.
- El módulo NO define repositorios nuevos, solo depende de los existentes.

---

## 6. Cuando se agregue nueva funcionalidad

Siempre seguir este patrón:

1. Determinar si la operación pertenece al dominio de `TenantEntities`.
2. Si sí pertenece:
    - Agregar método a `TenantEntitiesServiceInterface`.
    - Implementar el método en `TenantEntitiesService`.
    - Crear un nuevo UseCase bajo la subcarpeta correspondiente (si es necesario).
    - Crear/ajustar DTO si la operación lo requiere.

3. Asegurarse de no crear nuevas carpetas de nivel superior dentro del módulo que rompan la estructura acordada.

---
