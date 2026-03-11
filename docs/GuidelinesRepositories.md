# Lineamientos para el desarrollo de repositorios

## 1. Objetivo

Definir un **estándar obligatorio** para el diseño y nombrado de repositorios e interfaces de repositorio, con énfasis en:

- Estructura de archivos.
- Convenciones de nombres.
- Patrón de nombres de métodos (especialmente upserts).
- Diferencia clara entre acceso a datos y lógica de negocio.
- Evitar casos como `TenantLocationRepository`, que rompe el patrón.

---

## 2. Ubicación y estructura

### 2.1. Rutas de archivos

- Interfaces de repositorio:

```text
app/Repositories/Contracts/{Entity}RepositoryInterface.php
```

- Implementaciones:

```text
app/Repositories/{Entity}Repository.php
```

### 2.2. Convención de nombres de clases

- **Interface**: `{Entity}RepositoryInterface`
  Ejemplos:
    - `TenantRepositoryInterface`
    - `TenantLocationRepositoryInterface`
    - `ServiceRepositoryInterface`

- **Implementación**: `{Entity}Repository`
  Ejemplos:
    - `TenantRepository`
    - `TenantLocationRepository`
    - `ServiceRepository`

---

## 3. Responsabilidad del repositorio

### 3.1. Qué SÍ hace un repositorio

- Encapsular acceso a datos para una entidad específica:
    - Consultas (`find`, `all`, filtros complejos).
    - Escrituras (`create`, `update`, `updateOrCreate`, `delete`).
    - Operaciones sobre relaciones (`sync`, `attach`, `detach`).

- Proveer una API consistente sobre el modelo Eloquent correspondiente.

### 3.2. Qué NO hace un repositorio

- No recibe ni usa `Request` de HTTP.
- No devuelve respuestas HTTP (JSON, Response, etc.).
- No depende de controladores, FormRequests ni middlewares.
- No debería mezclar lógica de presentación ni de transporte.

> **Regla opcional (a definir equipo):**
> Lógica de negocio “fuerte” (reglas complejas: “solo uno es primary”, “no se puede borrar si tiene X”) idealmente vive en **UseCases/Services de dominio**, no en el repositorio.
> Si se decide permitir parte de esa lógica en repositorios, se debe documentar explícitamente.

---

## 4. Convenciones de nombres de métodos

### 4.1. Regla base (obligatoria)

**Todos los métodos de repositorio deben:**

1. Incluir el nombre de la **entidad** en el método.
2. Seguir el patrón:

`Verbo + Entidad [+ Rol/Opción] [+ By + Criterio(s)]`

Ejemplos correctos:

- `findTenantByJid`
- `updateOrCreateTenant`
- `findServiceCategoryByTenantAndName`
- `deleteStaffScheduleByStaff`
- `existsTenantByJid`
- `syncStaffServices`

### 4.2. Verbos permitidos

#### Lectura

- `find{Entity}By...` → Devuelve `?Model`
    - `findTenantByJid(string $jid): ?Tenant`
    - `findStaffByEmail(string $email): ?Staff`

- `all{EntityPlural}()` o `list{EntityPlural}()` → Devuelve `Collection`
    - `allServices(): Collection`
    - `listStaff(): Collection`

#### Escritura

- `create{Entity}(array $data): {Model}`

- `update{Entity}(int $id, array $data): {Model}`

- `updateOrCreate{Entity}(array $where, array $data): {Model}`

    > **ÚNICO nombre permitido para upsert** (queda prohibido usar `upsertX`).

- `delete{Entity}By...(...): void`
    - `deleteStaffScheduleByStaff(int $staffId): void`

#### Existencia

- `exists{Entity}By...(…): bool`
    - `existsTenantByJid(string $jid): bool`

#### Relaciones / pivots

- `sync{EntidadRelacion}(...)`
    - `syncServices(int $staffId, array $serviceIds): void`

- `attach{EntidadRelacion}(...)`
- `detach{EntidadRelacion}(...)`

### 4.3. Criterios / filtros (`By...`)

Cuando el método filtra por campos específicos, se debe usar `By`:

- `findTenantByJid`
- `findServiceByTenant`
- `findServiceCategoryByTenantAndName`
- `findPrimaryTenantLocationByTenantId`

Si hay múltiple criterios, usar `And`:

- `findXByTenantAndEmail(int $tenantId, string $email)`

### 4.4. Roles o variantes de entidad (primary, default, etc.)

Si la entidad tiene un rol especial (primary, default, main, etc.):

- El **rol va después del nombre de la entidad**, nunca suelto:

Correcto:

- `findPrimaryTenantLocationByTenantId(int $tenantId): ?TenantLocation`
- `updateOrCreatePrimaryTenantLocation(int $tenantId, array $data): TenantLocation`

Incorrecto:

- `findPrimaryByTenantId` (no dice qué es “Primary”).
- `upsertPrimaryLocation` (no dice “TenantLocation” ni usa `updateOrCreate`).

---

## 5. Estándar de firmas de métodos

### 5.1. Parámetros

- Filtros: usar tipos escalares tipados:
    - `int $tenantId`
    - `string $email`
    - `string $jid`

- Datos de escritura:
    - `array $data` o `array $attributes`
    - Para upserts: `array $where, array $data`

### 5.2. Tipos de retorno

- Modelo concreto:
    - `Tenant`, `Service`, `TenantLocation`, etc.

- Nullable:
    - `?Model` cuando puede no haber resultado (ej. `find...`).

- Colecciones:
    - `Collection` de modelos.

- Booleano:
    - `bool` para métodos `exists...`.

- Vacío:
    - `void` cuando solo hay efectos (`delete...`, `sync...`).

---

## 6. Lógica de negocio vs acceso a datos

### 6.1. Lógica de acceso a datos (repositorio)

Ejemplos válidos en el repositorio:

- “Devuélveme la ubicación con `is_primary = true` y cierto `tenant_id`”.
- “Haz un `updateOrCreate` de la entidad X con estos criterios”.
- “Elimina todos los horarios de staff con cierto `staff_id`”.

### 6.2. Lógica de negocio (mejor en UseCases/Services)

Ejemplos que **idealmente** NO deberían estar en el repositorio:

- “Antes de marcar esta ubicación como primary, desmarca todas las demás”.
- “No permitas borrar un registro si tiene citas activas”.
- “Si el doctor está inactivo, no crees nuevos servicios”.

> Recomendación:
>
> - El repositorio expone métodos simples (`find...`, `updateOrCreate...`, etc.).
> - Un UseCase o Service orquesta:
>     - Leer entidades.
>     - Validar reglas.
>     - Llamar a varios métodos del repositorio.

(Esto se puede flexibilizar, pero hay que documentar claramente cuándo se permite lógica extra dentro de los repositorios.)

---

## 7. Ejemplo: por qué `TenantLocationRepository` rompe el estándar

### 7.1. Interface actual (simplificada)

```php
interface TenantLocationRepositoryInterface
{
    public function upsertPrimaryLocation(int $tenantId, array $data): TenantLocation;

    public function findPrimaryByTenantId(int $tenantId): ?TenantLocation;
}
```

### 7.2. Problemas detectados

1. **No incluye el nombre de la entidad en los métodos**
    - `upsertPrimaryLocation`:
        - No dice “TenantLocation”.

    - `findPrimaryByTenantId`:
        - No dice qué es “Primary”: ¿primary location?, ¿primary tenant?, ¿otra cosa?

    👎 Va en contra de la regla “todos los métodos deben contener el nombre de la entidad”.

2. **Usa `upsert` en lugar de `updateOrCreate`**
    - En todos los demás repos se usa `updateOrCreate{Entity}` para upsert.
    - Aquí aparece un verbo distinto (`upsert`), lo que rompe la consistencia.

    👎 Va en contra de la regla “único verbo permitido para upsert es `updateOrCreate{Entity}`”.

3. **El rol (“Primary”) está antes de la entidad, no después**
    - `upsertPrimaryLocation` / `findPrimaryByTenantId`.
    - Según el estándar el patrón debe ser:
      `Verbo + Entidad + Rol + By + Criterio`.

    Ejemplo correcto:
    `findPrimaryTenantLocationByTenantId`.

4. (Opcional) **Contiene lógica de negocio fuerte**
    - El método que “upsertea” la ubicación primary probablemente:
        - Desmarca otras ubicaciones primary del mismo tenant.
        - Marca la nueva como primary.

    - Eso ya expresa una regla de negocio (“solo puede haber una ubicación primaria por tenant”), y podría ser responsabilidad de un UseCase de dominio, no del repositorio.

---

## 8. Versión corregida del ejemplo (`TenantLocationRepository`)

### 8.1. Interface alineada al estándar

```php
interface TenantLocationRepositoryInterface
{
    public function updateOrCreatePrimaryTenantLocation(int $tenantId, array $data): TenantLocation;

    public function findPrimaryTenantLocationByTenantId(int $tenantId): ?TenantLocation;
}
```

### 8.2. Comentarios

- Se usa **`updateOrCreate`** como verbo estándar para upsert.
- Todos los métodos contienen el nombre de la entidad: `TenantLocation`.
- El rol (`Primary`) aparece **después** de la entidad.
- El criterio de filtro (`TenantId`) aparece después de `By`.

Si se decide mover la lógica “apaga todas las primary salvo esta” a un UseCase, el repositorio podría tener métodos más simples, por ejemplo:

```php
public function unsetPrimaryTenantLocations(int $tenantId): void;

public function updateOrCreateTenantLocation(array $where, array $data): TenantLocation;

public function findPrimaryTenantLocationByTenantId(int $tenantId): ?TenantLocation;
```

Y un UseCase de dominio orquestaría:

1. `unsetPrimaryTenantLocations($tenantId)`
2. `updateOrCreateTenantLocation([...], $data)`

---

## 9. Checklist al crear o modificar un repositorio

Antes de dar por “hecho” un repositorio, validar:

1. ✅ La interface está en `app/Repositories/Contracts/{Entity}RepositoryInterface.php`.
2. ✅ La implementación está en `app/Repositories/{Entity}Repository.php`.
3. ✅ La interface se llama `{Entity}RepositoryInterface`.
4. ✅ La implementación se llama `{Entity}Repository`.
5. ✅ **Todos los métodos**:
    - Incluyen el nombre de la entidad.
    - Usan solo verbos permitidos (`find`, `create`, `update`, `updateOrCreate`, `delete`, `exists`, `sync`, etc.).
    - Usan `updateOrCreate{Entity}` para upsert (no `upsert...`).
    - Usan `By...` para criterios (`ByTenantId`, `ByEmail`, etc.).
    - Colocan cualquier rol (Primary, Default, etc.) después de la entidad.

6. ✅ La firma de métodos está tipada (parámetros y retorno).
7. ✅ No hay `Request`, `Response` ni clases HTTP dentro del repositorio.
8. ✅ Cualquier regla de negocio compleja está, preferentemente, en un UseCase/Service y no dentro del repositorio.
