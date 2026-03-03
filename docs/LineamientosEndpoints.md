# Lineamientos para el diseño de endpoints de la API

## 1. Objetivo

Definir un estándar claro para el diseño de endpoints de la API, con el fin de:

- Mantener consistencia entre módulos actuales y futuros (especialmente `TenantEntities`).
- Facilitar la integración con otros servicios (n8n, CRMs, etc.).
- Evitar endpoints improvisados, nombres incoherentes y contratos difíciles de mantener.

---

## 2. Alcance y definiciones

- Aplica a todos los endpoints HTTP definidos en `routes/api.php` y archivos relacionados.
- Se considera **API interna de integración** (consumida por n8n, otros servicios y, eventualmente, frontend).
- “Recurso” = entidad principal del dominio expuesta por la API (ej.: `tenants`, `locations`, `services`, `staff`, etc.).
- “Sub-recurso” = entidad dependiente de otra (ej.: `locations` de un tenant).

---

## 3. Convenciones generales de rutas

### 3.1. Versionado

- Todos los endpoints deben vivir bajo un prefijo de versión:

```text
/api/v1/...
```

- Versiones futuras deben usar prefijos nuevos (`/api/v2/...`) cuando haya cambios incompatibles.

### 3.2. Nombres de recursos (plural)

- Los recursos de primer nivel se nombran en **plural**:

```text
/api/v1/tenants
/api/v1/locations
/api/v1/services
/api/v1/staff
```

- Los elementos individuales se acceden con un identificador:

```text
/api/v1/tenants/{tenantJid}
/api/v1/services/{serviceId}
```

### 3.3. Identificador principal del recurso

- Cada recurso expuesto debe tener un **ID público estable** (ej.: `tenantJid` para tenants).
- Ese ID principal se usa siempre como path param, nunca como query param.

Ejemplo:

```text
GET /api/v1/tenants/{tenantJid}
```

---

## 4. Uso de path params vs query params

### 4.1. Path params (identidad del recurso)

**Regla:**

- Si el cliente ya conoce el identificador principal del recurso y espera **un solo recurso**, el ID va en el **path**.

Ejemplos:

```text
GET /api/v1/tenants/{tenantJid}
GET /api/v1/tenants/{tenantJid}/locations
GET /api/v1/tenants/{tenantJid}/services/{serviceId}
```

### 4.2. Query params (filtros y forma de la respuesta)

Los query params se usan para:

- Filtrar colecciones:

```text
GET /api/v1/tenants?status=active&city=Pachuca
```

- Paginación:

```text
GET /api/v1/tenants?page=2&per_page=20
```

- Orden:

```text
GET /api/v1/tenants?sort=created_at
```

- Inclusión de relaciones / campos extra:

```text
GET /api/v1/tenants/{tenantJid}?include=locations,services,staff
```

**Prohibido:**

- Usar query params para representar el identificador principal del recurso (ej.: `GET /api/v1/tenants?jid=...` para obtener uno solo).

---

## 5. Recursos principales y sub-recursos (`TenantEntities`)

### 5.1. Recurso `tenants`

```text
GET    /api/v1/tenants                 // listar (futuro)
POST   /api/v1/tenants                 // crear (si aplica)
GET    /api/v1/tenants/{tenantJid}     // detalle de un tenant
PATCH  /api/v1/tenants/{tenantJid}     // actualizar
DELETE /api/v1/tenants/{tenantJid}     // eliminar (si aplica)
```

Por defecto, `GET /api/v1/tenants/{tenantJid}` puede devolver:

- Solo datos básicos del tenant, o
- Tenant expandido, según lineamientos del punto 6.3.

### 5.2. Sub-recursos de `TenantEntities`

#### Locations

```text
GET    /api/v1/tenants/{tenantJid}/locations
GET    /api/v1/tenants/{tenantJid}/locations/{locationId}
POST   /api/v1/tenants/{tenantJid}/locations
PATCH  /api/v1/tenants/{tenantJid}/locations/{locationId}
DELETE /api/v1/tenants/{tenantJid}/locations/{locationId}
```

#### Services

```text
GET    /api/v1/tenants/{tenantJid}/services
GET    /api/v1/tenants/{tenantJid}/services/{serviceId}
POST   /api/v1/tenants/{tenantJid}/services
PATCH  /api/v1/tenants/{tenantJid}/services/{serviceId}
DELETE /api/v1/tenants/{tenantJid}/services/{serviceId}
```

#### Staff

```text
GET    /api/v1/tenants/{tenantJid}/staff
GET    /api/v1/tenants/{tenantJid}/staff/{staffId}
POST   /api/v1/tenants/{tenantJid}/staff
PATCH  /api/v1/tenants/{tenantJid}/staff/{staffId}
DELETE /api/v1/tenants/{tenantJid}/staff/{staffId}
```

#### Staff schedules

```text
GET    /api/v1/tenants/{tenantJid}/staff/{staffId}/schedules
POST   /api/v1/tenants/{tenantJid}/staff/{staffId}/schedules
PATCH  /api/v1/tenants/{tenantJid}/staff/{staffId}/schedules/{scheduleId}
DELETE /api/v1/tenants/{tenantJid}/staff/{staffId}/schedules/{scheduleId}
```

---

## 6. Búsquedas y lookups por otros identificadores

### 6.1. Patrón `by-{campo}`

Cuando se requiere buscar un recurso por otro identificador “fuerte” (ej.: ID de CRM externo), usar:

```text
GET /api/v1/tenants/by-espocrm-id/{espocrmId}
GET /api/v1/tenants/by-internal-id/{id}
GET /api/v1/tenants/by-phone/{phone}
```

Reglas:

- La ruta siempre parte del recurso (`/tenants`).
- El segmento `by-{algo}` deja claro que es un **lookup específico** de un solo recurso.
- El último segmento es el valor del identificador alternativo.

### 6.2. Endpoints de búsqueda (colecciones)

Para búsquedas más flexibles (textos, múltiples filtros, etc.):

```text
GET /api/v1/tenants/search?name=Gomez&city=Pachuca
```

- `search` implica potencialmente múltiples resultados (colección).
- Se diferencia de los `by-{campo}` que asumen un único recurso.

### 6.3. Lecturas agregadas (tenant expandido)

Cuando se devuelve un tenant con todo el grafo de `TenantEntities`, se recomienda uno de estos enfoques:

**Opción A – Expansión por defecto:**

- `GET /api/v1/tenants/{tenantJid}` → devuelve tenant + `locations`, `services`, `staff`, `schedules`, etc.

**Opción B – Expansión controlada con `include`:**

- `GET /api/v1/tenants/{tenantJid}` → datos básicos.
- `GET /api/v1/tenants/{tenantJid}?include=locations,services,staff,staff.schedules`

Se debe documentar en el proyecto cuál opción se adopta y mantenerla consistente.

---

## 7. Endpoints de acciones / comandos

Algunas operaciones no encajan en un CRUD simple (ej.: sincronizar desde un tercero, activar/desactivar).
Para esas, se adopta el patrón:

```text
POST /api/v1/{recurso}/{id}/actions/{accion}
```

Ejemplos:

- Re-sincronizar datos de un tenant desde un CRM:

```text
POST /api/v1/tenants/{tenantJid}/actions/sync-from-crm
```

- Activar/desactivar tenant:

```text
POST /api/v1/tenants/{tenantJid}/actions/activate
POST /api/v1/tenants/{tenantJid}/actions/deactivate
```

Reglas:

- El verbo HTTP para acciones no idempotentes será normalmente `POST`.
- El nombre de la acción debe ser **verbo en inglés o español consistente**, en kebab-case si son varias palabras (`sync-from-crm`, `close-day`, etc.).
- No crear rutas sueltas tipo `/api/v1/sync-tenant-from-xxx`; siempre colgar la acción del recurso.

---

## 8. Formato estándar de respuestas

### 8.1. Respuesta exitosa

Formato sugerido:

```json
{
  "status": "success",
  "data": { ... recurso o colección ... },
  "meta": { ... opcional: paginación, contadores, etc. ... }
}
```

Reglas:

- `data` contiene el recurso o lista de recursos.
- `meta` se usa para metadatos (paginación, filtros aplicados, totales, etc.).

### 8.2. Respuesta de error

Formato sugerido:

```json
{
    "status": "error",
    "message": "Descripción legible del error",
    "code": "CODIGO_INTERNO_OPCIONAL",
    "errors": {
        "campo_opcional": ["Detalle de validación o error específico"]
    }
}
```

Reglas:

- `message` debe ser comprensible y no técnico extremo.
- `code` se usa para identificar el tipo de error dentro del sistema.
- `errors` se usa principalmente para errores de validación campo a campo.

### 8.3. Códigos HTTP

Uso esperado:

- `200 OK` → Lecturas exitosas.
- `201 Created` → Creación de recursos.
- `204 No Content` → Borrados o acciones sin cuerpo de respuesta.
- `400 Bad Request` → Petición mal formada o parámetros inválidos.
- `401 Unauthorized` / `403 Forbidden` → Auth/permiso.
- `404 Not Found` → Recurso no encontrado.
- `409 Conflict` → Conflictos de negocio (ej.: violación de restricción única).
- `422 Unprocessable Entity` → Validación de datos fallida.
- `500 Internal Server Error` → Errores inesperados.

---

## 9. Seguridad y clasificación de APIs

### 9.1. API interna de integración

- Prefijo: `/api/v1/...`.
- Protegida por middleware (ej.: `api-secure`) mediante tokens/keys internos.
- Consumida por:
    - Workflows en n8n.
    - Otros microservicios.
    - Herramientas internas.

### 9.2. API pública (futuro)

Si se expone una API a terceros:

- Puede usar el mismo esquema de `/api/v1/...` pero:
    - Con autenticación diferente (tokens por cliente, rate limits, etc.).
    - Con un subconjunto bien controlado de recursos y campos.

- Debe documentarse por separado y permanecer estable por versión.

---

## 10. Checklist para nuevos endpoints

Antes de crear un endpoint nuevo, validar:

1. **Recurso y verbo**
    - ¿Qué recurso toca? (`tenants`, `locations`, `services`, `staff`, …).
    - ¿Es CRUD o acción?
        - CRUD → usar `/api/v1/{recurso}...`
        - Acción → usar `/api/v1/{recurso}/{id}/actions/{accion}`.

2. **Identificador**
    - ¿Tengo el ID principal del recurso? → va en path (`/{tenantJid}`).
    - ¿Es lookup por otro ID fuerte? → usar `by-{campo}` en la ruta.
    - ¿Es búsqueda/filtro? → usar query params.

3. **Estructura**
    - ¿Debe ser sub-recurso?
      → colgar bajo `/tenants/{tenantJid}/...` si depende de un tenant.

4. **Versión**
    - ¿Está bajo `/api/v1`?

5. **Respuesta**
    - ¿Sigue el formato `{ status, data, meta }`?
    - ¿Errores siguen `{ status, message, code, errors }`?

6. **Seguridad**
    - ¿Tiene aplicado el middleware correcto (`api-secure` u otro)?
