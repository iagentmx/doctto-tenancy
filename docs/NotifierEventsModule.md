# Módulo de Eventos de Integración

## 1. Propósito

El módulo `NotifierEvents` existe para registrar y enviar hacia servicios externos los cambios importantes que ocurren en entidades operativas del sistema, de forma confiable y desacoplada.

Su objetivo es modelar eventos del dominio y encargarse de que esos eventos puedan llegar a uno o varios destinos externos con trazabilidad, reintentos e idempotencia.

---

## 2. Cómo funciona en términos generales

Cuando una entidad cubierta por este flujo se actualiza o se elimina, el sistema genera un evento de integración.

Ese evento no se manda directamente al servicio externo. Primero se registra internamente y luego se crean las entregas pendientes para cada destino habilitado.

A partir de ahí, el sistema intenta enviarlo. Si la entrega funciona, queda marcada como entregada. Si falla, queda registrada con su error y programada para reintento.

---

## 3. Flujo operativo

### 3.1. Se detecta un cambio

El módulo entra en acción cuando una entidad cubierta:

- se **actualiza**, o
- se **elimina**.

No genera eventos cuando una entidad se crea.

---

### 3.2. Se construye el evento

El sistema arma un evento canónico con información básica del cambio:

- tipo de evento, por ejemplo `service.updated` o `service.deleted`;
- `tenant_id`;
- `entity_id`;
- fecha/hora del cambio;
- lista de campos modificados cuando aplica.

Si el evento es de eliminación, la lista de campos modificados va vacía.

---

### 3.3. Se registra en la outbox

El evento se guarda primero en una tabla interna de outbox.

Esa tabla representa la evidencia de que el cambio ocurrió y de que debe notificarse hacia afuera.

Esto permite que el evento exista aunque el servicio externo no esté disponible en ese momento.

---

### 3.4. Se crean entregas por destino

Por cada destino habilitado, el sistema crea una entrega independiente.

Esto significa que un mismo evento puede estar:

- entregado en un destino,
- pendiente en otro,
- fallido en otro más.

Cada destino se sigue por separado.

---

### 3.5. Se intenta la entrega

Al crear las entregas, el sistema encola su procesamiento de forma inmediata.

Además, existe un mecanismo de rescate por scheduler que revisa entregas pendientes o reintentos vencidos para evitar que alguna se quede atorada.

En otras palabras:

- hay intento inmediato como flujo principal;
- y hay barrido programado como respaldo operativo.

---

### 3.6. Resultado de la entrega

Si la entrega sale bien:

- se marca como **delivered**;
- se registra cuándo se entregó;
- puede guardarse información básica de la respuesta.

Si falla:

- se incrementa el número de intentos;
- se registra el error;
- se programa el siguiente reintento;
- si se alcanza el máximo configurado, la entrega queda en estado terminal para diagnóstico y reproceso manual.

---

## 4. Eventos que cubre

Este módulo solo publica eventos de dos tipos:

- `{entity}.updated`
- `{entity}.deleted`

No publica eventos `{entity}.created`.

---

## 5. Entidades incluidas

En esta primera versión, el flujo cubre:

- `tenant`
- `tenant_location`
- `service`
- `staff`
- `resource`
- `schedule`
- `tenant_admin`
- `staff_service`

Están excluidas las entidades de catálogo o clasificación que no forman parte de este flujo, en particular:

- `resource_types`
- `service_categories`

---

## 6. Reglas importantes del comportamiento

### 6.1. El evento representa un cambio persistido del dominio

El evento representa un cambio que quedó persistido en el sistema.

La lógica central parte del cambio de la entidad y no del canal, endpoint o servicio desde el que se originó la operación.

---

### 6.2. La entrega se procesa de forma desacoplada

Los observers producen el evento y lo dejan registrado para que el subsistema de integración se encargue de la entrega.

La generación del evento y su transporte hacia servicios externos se manejan como responsabilidades separadas.

---

### 6.3. La persistencia del cambio y del evento debe quedar alineada

Cuando una operación de negocio se guarda, el evento y sus entregas deben quedar registrados dentro de la misma lógica transaccional.

La prioridad es que, si el cambio de negocio se confirma, el evento también exista. Y si el cambio no se confirma, el evento tampoco debe quedar suelto.

---

### 6.4. Cada evento se trata de forma independiente

Cada evento tiene su propio `event_uuid`, y cada entrega es única por evento y destino.

Esto permite procesar cambios consecutivos sobre una misma entidad sin colapsarlos entre sí y, al mismo tiempo, mantener control e idempotencia por cada publicación.

---

### 6.5. Idempotencia hacia afuera

Cada evento cuenta con un identificador único que permite que los consumidores externos detecten duplicados si ocurre un reintento o una entrega repetida.

---

## 7. Reintentos y resiliencia

El módulo está diseñado para reintentar por destino cuando una entrega falla.

La política de reintentos vive en la configuración del sistema y permite controlar:

- si un destino está habilitado o no;
- cuántos intentos tendrá;
- qué timeouts usará;
- cuánto tiempo esperar entre reintentos.

Esto evita perder eventos por fallas temporales de conectividad o indisponibilidad del servicio externo.

---

## 8. Reproceso manual

Además del flujo automático, el sistema permite reprocesar entregas fallidas o pendientes vencidas mediante comando Artisan.

Esto sirve para operación y soporte, sin depender de endpoints HTTP adicionales.

---

## 9. Semántica del evento

El evento mantiene una estructura canónica compuesta por:

- tipo de evento;
- `tenant_id`;
- `entity_id`;
- `occurred_at`;
- `metadata.changed_fields`.

Reglas relevantes:

- en `updated`, `changed_fields` refleja cambios reales;
- en `deleted`, `changed_fields` siempre es `[]`;
- en `staff_service`, el `entity_id` se resuelve con la lógica especial definida para esa relación;
- en `tenant`, `tenant_id` y `entity_id` apuntan al mismo registro.

---

## 10. Resultado funcional del módulo

Cuando una entidad operativa relevante cambia o se elimina, el sistema genera un evento de integración, lo registra internamente, crea una entrega por cada destino habilitado y procesa esas entregas de forma confiable, con reintentos, trazabilidad y capacidad de reproceso.

El diseño del módulo permite operar con uno o varios destinos externos sin necesidad de redefinir el modelo de eventos.

---

## 11. Resumen ejecutivo

`NotifierEvents` es el subsistema que convierte cambios del dominio en eventos confiables de integración.

Primero registra el evento, luego administra su entrega por destino, reintenta cuando hace falta y deja trazabilidad completa del estado de cada publicación.

Eso permite integrar el sistema con servicios externos de forma robusta, observable y escalable.
