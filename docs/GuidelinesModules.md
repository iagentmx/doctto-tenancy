# Lineamientos para el desarrollo de módulos

_(Pensado para proyectos Laravel / PHP modulares)_

## 1. Propósito y alcance

Este documento define **cómo debe diseñarse un módulo** para que:

- Sea reutilizable en distintos proyectos.
- Tenga una estructura predecible (`Contracts`, `UseCases`, `DTO`, etc.).
- Mantenga separadas las responsabilidades de:
    - Dominio interno.
    - Integraciones externas.
    - Capa HTTP / UI.
    - Infraestructura global (migraciones, modelos, repositorios compartidos).

Está pensado para proyectos Laravel, pero el patrón es aplicable a cualquier stack con concepto similar a:

- Service Container / IoC.
- Service Providers.
- Capas de dominio / aplicación / infraestructura.

---

## 2. ¿Qué es un “módulo”?

Un **módulo** es un paquete de funcionalidad con límites claros (bounded context) que vive, por ejemplo, bajo:

```text
app/Modules/{ModuleName}/
```

y que:

1. Expone **contratos públicos** (`Contracts`) que el resto del sistema puede usar.
2. Provee **una fachada** (`Services`) que implementa esos contratos.
3. Contiene su **lógica de negocio o integración** en subcapas internas (UseCases, DTO, Infrastructure, etc.).
4. Depende de recursos globales (modelos, migraciones, repositorios, config), pero **no los define**.

Un módulo **NO** es:

- Un controlador.
- Un modelo.
- Un repositorio.
- Una capa HTTP.

Es un “librería interna” de negocio / integración, centrada en un tema: clientes, agendas, facturación, sincronización con CRM, notificaciones, etc.

---

## 3. Tipos de módulos

### 3.1. Módulos de dominio interno

Responsables de reglas de negocio propias del sistema, sin hablar directamente con servicios externos.

Ejemplos de responsabilidad:

- Gestionar la estructura de un tenant (ubicaciones, servicios, personal, horarios).
- Lógica de facturación interna.
- Gestión de reservas, estados, reglas de negocio.

Características:

- Trabajan contra modelos / repositorios internos.
- No conocen detalles HTTP ni APIs externas.
- Suelen tener muchos casos de uso (Create/Update/Delete/List/...).

### 3.2. Módulos de integración externa

Responsables de hablar con **sistemas externos** (CRMs, motores de workflow, pasarelas de pago, etc.).

Ejemplos de responsabilidad:

- Ingesta de datos desde un CRM externo.
- Envío de eventos a un orquestador de workflows.
- Consumo de APIs de terceros (pagos, SMS, etc.).

Características:

- Normalizan payloads externos (webhooks, REST, colas) en DTO internos.
- Manejan clientes HTTP especializados (`HttpXxxClient`).
- Mapean errores externos a excepciones propias del módulo.
- Pueden coordinarse con módulos de dominio interno a través de **Contracts**, nunca de implementaciones concretas.

---

## 4. Estructura de carpetas de un módulo

### 4.1. Estructura mínima

Todo módulo debe tener al menos:

```text
app/Modules/{ModuleName}/
  Contracts/
  Services/
  Providers/
```

- `Contracts/` → Interfaces públicas del módulo.
- `Services/` → Implementaciones de esos contratos (fachadas).
- `Providers/` → ServiceProvider del módulo (bindings en el contenedor).

### 4.2. Capas opcionales (recomendadas)

Según la complejidad del módulo se agregan:

```text
app/Modules/{ModuleName}/
  Contracts/
  DTO/
  UseCases/
  Services/
  Providers/
  Infrastructure/
    Http/
    Config/
    Mapping/
  Exceptions/
  Handlers/
  Support/
```

**Rol de cada carpeta:**

- `DTO/`
  Objetos de datos (entrada/salida) usados por UseCases y Services.
    - Dominios internos → `{Entity}Data` (p.ej. `ServiceData`, `StaffData`).
    - Payloads externos → `{Entity}Payload` (p.ej. `CrmAccountPayload`).

- `UseCases/`
  Casos de uso del módulo. Una clase por operación:
    - `CreateX`, `UpdateX`, `DeleteX`, `ListX`, `SyncXFromY`, etc.

- `Infrastructure/Http/`
  Clientes HTTP hacia APIs externas (p.ej. `HttpCrmClient`, `HttpWorkflowEngineClient`).

- `Infrastructure/Config/`
  Adaptadores para leer configuración desde `config()` y abstraerla mediante interfaces (`ConfigProviderInterface`).
  Regla: **nunca leer `.env` directamente en el módulo**.

- `Infrastructure/Mapping/`
  Mapeos de estructuras externas ↔ internas (p.ej. de payload de CRM a DTO de dominio).

- `Exceptions/`
  Excepciones específicas del módulo (errores HTTP externos, payload inválido, estados de negocio).

- `Handlers/`
  Clases que reaccionan a eventos internos o externos (por ejemplo, publicar algo en otro sistema cuando cambia una entidad).

- `Support/`
  Helpers específicos del módulo (guards, detectores, pequeñas utilidades), que no aplican de forma global al proyecto.

---

## 5. Qué SÍ va dentro de un módulo

### 5.1. Contracts (interfaces públicas)

- Definen **qué ofrece** el módulo al resto de la app.
- No exponen detalles de implementación.
- Ejemplos:
    - `CustomerEntitiesServiceInterface`
    - `ExternalCrmClientInterface`
    - `IntegrationEventBusInterface`

Regla: controladores, jobs, listeners y otros módulos **dependen de estos Contracts**, no de clases concretas.

### 5.2. Services (fachada del módulo)

- Clases que implementan los Contracts.
- Coordinan UseCases, DTO, clientes HTTP y repositorios.
- Punto único de entrada al módulo.

Regla: cualquier funcionalidad nueva del módulo se agrega:

1. Primero al **Contract**.
2. Luego a la **implementación en Services**.

### 5.3. UseCases (dominio / aplicación)

- Una clase por operación de negocio o integración.
- No manejan HTTP ni respuestas JSON.
- Consumen:
    - DTO (entrada).
    - Repositorios / modelos.
    - Clientes externos a través de interfaces.

- Devuelven:
    - DTO, modelos, arrays de datos, o void (si solo tienen efectos).

Ejemplos de nombres:

- `CreateCustomer`
- `UpdateService`
- `SyncCustomerFromCrm`
- `SendBookingToWorkflowEngine`

### 5.4. DTO y payloads

- Representan datos con significado de dominio o integración:
    - `CustomerData`, `LocationData`, `ServiceData`.
    - `CrmAccountPayload`, `WebhookEventPayload`.

- Se usan para:
    - Evitar pasar arrays desorganizados.
    - Documentar mejor la forma de los datos.
    - Aislar al resto del módulo de cambios en el payload externo.

### 5.5. Clientes externos y configuración

Dentro de `Infrastructure/`:

- Interfaces como `ExternalCrmClientInterface`, `WorkflowEngineClientInterface`.
- Implementaciones HTTP (`HttpExternalCrmClient`) que:
    - Construyen requests.
    - Manejan autenticación / headers.
    - Interpretan respuestas y errores.

Configuración:

- Se obtiene desde `config('...')`, nunca con `env()`.
- Se aísla mediante un `ConfigProviderInterface`, implementado en `Infrastructure/Config`.

### 5.6. Exceptions específicas

- Excepciones que representan:
    - Errores de integración externa (`ExternalServiceHttpException`, `ExternalServicePayloadException`).
    - Errores de negocio del módulo (`DomainRuleException`, `MappingException`).

- Son traducidas a respuestas HTTP en la capa de controladores, no dentro del módulo.

### 5.7. Support y Handlers

- **Support**:
    - Utilidades que solo tienen sentido dentro del módulo.
    - Ejemplos: guard para evitar publicar dos veces el mismo evento por request, helper para construir rutas dinámicas externas, etc.

- **Handlers**:
    - Fragmentos que se disparan ante eventos: por ejemplo, cuando cambia una entidad interna y quieres enviar una notificación a un sistema externo.
    - Se conectan al resto del sistema mediante eventos / listeners o un bus de integración.

---

## 6. Qué NO va dentro de un módulo

Estos elementos deben quedar **fuera** de `app/Modules` y mantenerse “globales” al proyecto:

- **Migraciones** (p.ej. `database/migrations/*`).
- **Modelos Eloquent / entidades de dominio** (p.ej. `app/Models/*`).
- **Repositorios globales** (interfaces e implementaciones reutilizadas por varios módulos).
- **Enums globales** (tipos de industria, roles, estados generales).
- **Controladores HTTP** (`app/Http/Controllers/*`).
- **Form Requests** (`app/Http/Requests/*`).
- **Routes** (`routes/*.php`).
- **Middlewares globales**, policies, etc.

Regla dura: el módulo **usa** estos recursos globales, pero **no los define**. Eso permite que:

- Varios módulos puedan compartir modelos / repositorios.
- Cambiar la implementación interna del módulo sin afectar el modelo ni la base de datos.

---

## 7. Convenciones de nombres

### 7.1. Módulos

- Carpeta: `app/Modules/{ModuleName}`.
- Formato: **PascalCase**.
- Nombre debe describir su responsabilidad principal, por ejemplo:
    - `CustomerEntities`, `BookingEntities`, `Billing`, `ExternalCrmIngestion`, `WorkflowNotifierEvents`.

### 7.2. Interfaces (Contracts)

- `{NombreServicio}Interface`
    - `CustomerEntitiesServiceInterface`
    - `ExternalCrmClientInterface`
    - `WorkflowNotifierInterface`

Interfaces de configuración:

- `{NombreSistema}ConfigProviderInterface`
    - `ExternalCrmConfigProviderInterface`.

### 7.3. Servicios (implementaciones)

- Nombre sin sufijo extra:
    - `CustomerEntitiesService`
    - `ExternalCrmService`
    - `WorkflowNotifier`

### 7.4. UseCases

- Verbo + Entidad (+ contexto opcional):
    - `CreateCustomer`
    - `UpdateLocation`
    - `DeleteService`
    - `ListStaff`
    - `SyncCustomerFromCrm`
    - `NotifyWorkflowOnBookingCreated`

Si un UseCase es claramente de integración externa, se puede incluir `From` / `To` en el nombre (`FromCrm`, `ToWorkflow`).

### 7.5. DTO / Payloads

- Dominio interno:
    - `{Entidad}Data` → `CustomerData`, `LocationData`.

- Payload externo:
    - `{NombreSistema}{Entidad}Payload` → `CrmAccountPayload`, `PaymentGatewayChargePayload`.

### 7.6. Providers

- `{ModuleName}ServiceProvider.php`

Responsabilidades:

- Registrar bindings de:
    - Contracts → Services.
    - Clientes HTTP.
    - ConfigProviders.
    - Otros componentes del módulo que necesiten resolverse vía IoC.

---

## 8. Reglas de dependencia entre capas

Orden de fuera hacia adentro:

1. **Capa HTTP**
    - Controladores, Form Requests, rutas.
    - Conoce contratos de módulos, pero no implementaciones.

2. **Contracts de módulos**
    - Interfaces de servicios, clientes, bus de integración.

3. **Services de módulos**
    - Implementan Contracts y coordinan UseCases / DTO / clientes externos.

4. **UseCases**
    - Contienen la lógica de negocio o de aplicación.

5. **Repositorios**
    - Interfaces globales (por ejemplo, `CustomerRepositoryInterface`).
    - Implementaciones Eloquent / SQL / etc.

6. **Modelos / Base de datos / Infraestructura global**

Reglas importantes:

- Controladores **no** hablan directamente con repositorios ni modelos para dominios que ya tienen módulo; usan el Contract del módulo.
- UseCases no devuelven respuestas HTTP; devuelven datos.
- Módulos de integración externa pueden usar módulos de dominio, pero siempre a través de Contracts.

---

## 9. Flujo genérico de interacción

Ejemplo generalizable a muchos proyectos:

1. **Entrada externa**
    - Un webhook HTTP, una request API, un mensaje en cola, etc.
    - Es procesado por:
        - Un controlador HTTP, o
        - Un job / listener fuera del módulo.

2. **Uso de módulo de integración** (si aplica)
    - El punto de entrada usa un Contract de un módulo de integración (`ExternalCrmIngestionInterface`, `WorkflowNotifierInterface`) para:
        - Validar/normalizar entradas.
        - Pedir datos a APIs externas.
        - Generar DTO internos.

3. **Uso de módulo de dominio**
    - El módulo de integración llama a Contracts de módulos de dominio (`CustomerEntitiesServiceInterface`, `BookingEntitiesServiceInterface`) para aplicar reglas de negocio y persistir cambios.

4. **Eventos internos / notificaciones** (si aplica)
    - Cambios de dominio disparan eventos internos.
    - Handlers fuera o dentro de otros módulos reaccionan (por ejemplo, publican a un motor de workflow, envían correo, etc.).

5. **Respuesta HTTP / salida**
    - La capa HTTP traduce:
        - Datos devueltos por los módulos → JSON / HTTP.
        - Excepciones de módulos → códigos HTTP y mensajes apropiados.

---

## 10. Checklist para crear un módulo nuevo

Cuando vayas a crear un módulo en **cualquier** proyecto:

1. **Definir el propósito**
    - ¿Es dominio interno o integración externa?
    - Escribir una descripción corta:
      “Este módulo se encarga de **\_\_**”.

2. **Elegir el nombre del módulo**
    - `CustomerEntities`, `Billing`, `ExternalCrmIngestion`, etc.
    - Crear carpeta `app/Modules/{ModuleName}`.

3. **Estructura mínima**
    - `Contracts/`, `Services/`, `Providers/`.

4. **Definir Contracts**
    - Interfaces públicas con métodos de negocio, no detalles técnicos.
    - Pensar: “¿Cómo quiero que otros módulos/controladores hablen con esto?”.

5. **Crear Services (fachada)**
    - Implementar los Contracts.
    - Delegar la lógica a UseCases / DTO / clientes externos.

6. **Agregar subcapas necesarias**
    - `DTO/` para datos de entrada/salida.
    - `UseCases/` para operaciones específicas.
    - `Infrastructure/Http` y `Infrastructure/Config` para integraciones externas.
    - `Exceptions/` para errores propios del módulo.
    - `Support/` y `Handlers/` según se requiera.

7. **Registrar ServiceProvider**
    - Agregar `{ModuleName}ServiceProvider` al bootstrap del framework (en Laravel: `config/app.php` o un provider central).

8. **Conectar desde la app**
    - Controladores, jobs, listeners, etc. inyectan solo **Contracts** del módulo.

---

## 11. Uso en otros proyectos

Este esquema se puede aplicar en cualquier proyecto Laravel que:

- Use `app/Modules` (o `src/Modules`) como raíz de módulos.
- Tenga:
    - Service Container (bindings).
    - Service Providers.
    - Capa HTTP separada de dominios/servicios.

Para adaptarlo a otro proyecto:

- Cambia solo:
    - El namespace base (`App\Modules` → `MyApp\Modules`, etc.).
    - El directorio raíz (`app/Modules` → `src/Modules`).

- Mantén:
    - Nombres de carpetas internos (`Contracts`, `DTO`, `UseCases`, etc.).
    - Reglas de qué va dentro/fuera del módulo.
    - Patrón de dependencias y convenciones de nombres.
