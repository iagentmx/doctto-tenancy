# 🧩 CRM-AGENTS

### Versión: 1.0

**Autor:** Edgar Gómez Moctezuma
**Fecha:** 2025-10-20

---

## 📘 Descripción general

**CRM-AGENTS** es una plataforma **multi-tenant** enfocada en la **gestión de citas, clientes, servicios y personal** para negocios de distintos giros: consultorios médicos, clínicas de belleza, talleres mecánicos, despachos legales, entre otros.

Su propósito es ofrecer una solución integral para **administrar agendas, disponibilidad del personal, métricas y desempeño operativo** de manera independiente por cada negocio registrado (_tenant_).

---

## 🎯 Objetivos del sistema

-   Gestionar de forma completa **citas, clientes y servicios**.
-   Permitir **configuración personalizada por tenant** (horarios, recordatorios, servicios, políticas).
-   Ofrecer **reportes analíticos** de conversión, ocupación y cancelaciones.
-   Mantener una arquitectura **segura, modular y escalable** bajo el modelo SaaS.
-   Garantizar **independencia total de datos entre tenants** mediante segmentación lógica.

---

## ⚙️ Requisitos del sistema

| Componente        | Versión mínima |
| ----------------- | -------------- |
| PHP               | 8.3            |
| Laravel           | 12.x           |
| PostgreSQL        | 16             |
| Node.js           | 20             |
| Composer          | 2.6            |
| Docker (opcional) | 24+            |

---

## 🚀 Instalación y configuración

### 🧩 Opción 1 — Entorno local (sin Docker)

```bash
# Clonar el repositorio
git clone https://github.com/usuario/crm-agents.git
cd crm-agents

# Instalar dependencias
composer install
npm install

# Copiar variables de entorno y generar key
cp .env.example .env
php artisan key:generate

# Configurar base de datos en el archivo .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=crm_agents
DB_USERNAME=postgres
DB_PASSWORD=secret

# Migrar la base de datos y cargar datos iniciales
php artisan migrate --seed

# Levantar el servidor
php artisan serve
```

---

### 🐳 Opción 2 — Con Docker Compose (Desarrollo)

El proyecto incluye una configuración completa de Docker con:

-   `crm-app` → API Laravel con Xdebug
-   `crm-db` → PostgreSQL 16
-   `crm-queue` → Worker para Jobs
-   `crm-scheduler` → Cron para tareas periódicas

#### Archivos necesarios

Asegúrate de tener estos archivos en la raíz del proyecto:

```
crm-agents/
├── .env                      # Variables de desarrollo
├── docker-compose.yml        # Configuración de servicios
├── Dockerfile                # Multi-stage (development/production)
├── docker-entrypoint.sh      # Script de inicialización
└── nginx.conf                # Configuración de nginx (solo producción)
```

#### Levantar el entorno de desarrollo

```bash
# Detener contenedores previos (si existen)
docker-compose down

# Levantar servicios
docker-compose up -d --build

# Ver logs
docker-compose logs -f crm-app

# Acceder a la aplicación
# http://localhost:8001
```

#### Comandos útiles

```bash
# Ver estado de contenedores
docker-compose ps

# Ejecutar comandos dentro del contenedor
docker-compose exec crm-app php artisan migrate
docker-compose exec crm-app php artisan tinker

# Acceder a la base de datos
docker-compose exec crm-db psql -U sivegm -d crm

# Ver logs de un servicio específico
docker-compose logs -f crm-queue

# Detener todos los servicios
docker-compose down

# Eliminar volúmenes (⚠️ borra la base de datos)
docker-compose down -v
```

---

### 🚀 Opción 3 — Producción con Docker (VPS/Hostinger)

Para producción se incluye:

-   **Nginx** como proxy reverso
-   **PHP-FPM** para mejor rendimiento
-   Sin volúmenes montados (código dentro del contenedor)
-   Optimizaciones de caché de Laravel

#### Preparar entorno de producción

```bash
# 1. Clonar el repositorio en el servidor
git clone https://github.com/usuario/crm-agents.git
cd crm-agents

# 2. Copiar archivo de producción
cp .env.production .env

# 3. Editar variables críticas
nano .env
# Cambiar:
# - APP_KEY (generar nueva con: php artisan key:generate)
# - DB_PASSWORD (contraseña segura)
# - APP_URL (tu dominio)
# - MAIL_* (configuración de correo)

# 4. Levantar servicios en modo producción
docker-compose --profile production up -d --build

# 5. Verificar que todo funciona
docker-compose logs -f

# Acceder: http://tu-ip-servidor (puerto 80)
```

#### Variables importantes para producción

En tu `.env` de producción asegúrate de configurar:

```bash
# Aplicación
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

# Docker
BUILD_TARGET=production
VOLUME_MOUNT=
COMPOSE_PROFILES=production

# Base de datos (cambiar contraseña)
DB_PASSWORD=TU_PASSWORD_SEGURO_AQUI

# Email (configurar SMTP real)
MAIL_MAILER=smtp
MAIL_HOST=smtp.tuproveedor.com
MAIL_USERNAME=tu_email@dominio.com
MAIL_PASSWORD=tu_password_email
```

#### SSL/HTTPS con Let's Encrypt (opcional)

Para habilitar HTTPS edita `nginx.conf` y descomenta la sección de SSL, luego:

```bash
# Instalar certbot en el servidor
apt-get install certbot

# Generar certificado
certbot certonly --standalone -d tudominio.com

# Copiar certificados al proyecto
cp /etc/letsencrypt/live/tudominio.com/fullchain.pem ./ssl/cert.pem
cp /etc/letsencrypt/live/tudominio.com/privkey.pem ./ssl/key.pem

# Reiniciar nginx
docker-compose restart nginx
```

---

## 🧱 Estructura del proyecto

```
app/
 ├── Enums/
 ├── Http/
 │    ├── Controllers/
 │    └── Requests/
 ├── Models/
 ├── Observers/
 ├── Repositories/
 │    └── Contracts/
 ├── Services/
 └── Providers/
database/
 ├── migrations/
 ├── seeders/
routes/
 ├── api.php
 └── web.php
docker/
 ├── .env                    # Desarrollo
 ├── .env.production         # Producción
 ├── docker-compose.yml      # Configuración de servicios
 ├── Dockerfile              # Imagen de la aplicación
 ├── docker-entrypoint.sh    # Script de inicialización
 └── nginx.conf              # Configuración de nginx
```

---

## 🧩 Arquitectura de desarrollo

El proyecto sigue una **arquitectura modular, limpia y desacoplada**, basada en buenas prácticas de Laravel para mantener mantenibilidad, testabilidad y separación de responsabilidades.

### 🔹 Principios principales

1. **Enums (`app/Enums`)**
   Todos los valores fijos (estados, roles, tipos, etc.) se implementan como _enum classes_ en PHP.
   Ejemplo: `BookingStatus`, `LeadStatus`, `ReminderType`.

2. **Requests (`app/Http/Requests`)**
   La validación **no** se realiza en los controladores.
   Cada endpoint utiliza su propio _Form Request_ con reglas, mensajes y autorización.

3. **Modelos y migraciones**
   Cada tabla tiene su modelo Eloquent en `app/Models`.
   Las migraciones únicamente definen estructura, sin lógica adicional.

4. **Observers (`app/Observers`)**
   Escuchan eventos de modelos (`created`, `updated`, `deleted`) para detonar procesos automáticos, como notificaciones o sincronización con otros sistemas.

5. **Repositorios (`app/Repositories` + `app/Repositories/Contracts`)**
   Todo acceso a datos pasa por un _Repository Pattern_:

    - `Contracts/` define las interfaces.
    - `Repositories/` contiene las implementaciones concretas.
      Esto facilita pruebas unitarias y reduce el acoplamiento.

6. **Servicios (`app/Services`)**
   Encapsulan la lógica de negocio. Los controladores nunca contienen reglas de negocio.
   Cada servicio orquesta uno o varios repositorios.

7. **Controladores (`app/Http/Controllers`)**
   Se limitan a recibir peticiones, invocar servicios y devolver respuestas JSON limpias.

8. **Eventos y Jobs**
   Las tareas costosas o asíncronas (recordatorios, métricas, reportes) se ejecutan mediante Jobs despachados desde servicios u observers.

---

## ⚙️ Arquitectura funcional

| Módulo       | Función principal                                                      |
| ------------ | ---------------------------------------------------------------------- |
| **Tenants**  | Administración de los negocios registrados.                            |
| **Leads**    | Registro y seguimiento de prospectos antes de convertirse en clientes. |
| **Accounts** | Clientes confirmados o recurrentes.                                    |
| **Bookings** | Agenda central de citas, estados y recordatorios.                      |
| **Staff**    | Administración del personal, sus horarios y servicios asignados.       |
| **Services** | Catálogo de servicios ofrecidos por cada tenant.                       |
| **Reports**  | Generación de métricas e indicadores de rendimiento.                   |

---

## 🧱 Modelo de datos

| Tabla                     | Propósito                                                        |
| ------------------------- | ---------------------------------------------------------------- |
| **tenants**               | Registra los negocios o consultorios que utilizan la plataforma. |
| **leads**                 | Almacena los contactos o prospectos iniciales.                   |
| **accounts**              | Representa a los clientes confirmados.                           |
| **bookings**              | Gestiona las citas o reservas de servicios.                      |
| **appointment_reminders** | Controla los recordatorios automáticos.                          |
| **services**              | Define los servicios ofrecidos por cada negocio.                 |
| **service_categories**    | Agrupa los servicios en categorías temáticas.                    |
| **staff**                 | Registra al personal o especialistas.                            |
| **staff_schedules**       | Define los horarios laborales o disponibilidad.                  |
| **staff_services**        | Relaciona qué servicios puede realizar cada miembro del staff.   |

---

## 🔄 Flujo operativo general

1. **Registro del tenant** → creación del negocio con su configuración inicial.
2. **Registro de leads o clientes** → ingreso manual o importación.
3. **Creación de citas (bookings)** → asignación de cliente, servicio y personal.
4. **Recordatorios automáticos** → envío programado por los canales configurados.
5. **Actualización de estado** → "attended", "cancelled", "rescheduled" o "no-show".
6. **Reportes** → visualización de métricas y desempeño del negocio.

---

## 📊 Métricas clave

| Métrica                     | Descripción                                   |
| --------------------------- | --------------------------------------------- |
| **Citas programadas**       | Total de citas registradas en un periodo.     |
| **Citas canceladas**        | Número de citas con estado _cancelled_.       |
| **Citas reagendadas**       | Citas con estado _rescheduled_.               |
| **Inasistencias (no-show)** | Citas no atendidas.                           |
| **Tasa de asistencia**      | % de citas atendidas sobre las programadas.   |
| **Tasa de utilización**     | Horas ocupadas / horas disponibles del staff. |
| **Retención de clientes**   | Clientes recurrentes / clientes totales.      |
| **Conversión de leads**     | Leads convertidos a clientes / leads totales. |

---

## 🧰 Arquitectura técnica

| Componente          | Tecnología implementada                               |
| ------------------- | ----------------------------------------------------- |
| **Backend**         | Laravel 12 (PHP 8.3)                                  |
| **Base de datos**   | PostgreSQL 16 (multi-tenant por columna `tenant_id`)  |
| **Frontend**        | Vue 3 o React                                         |
| **Autenticación**   | Laravel Sanctum / JWT                                 |
| **Colas y Jobs**    | Laravel Queue con Database Driver                     |
| **Infraestructura** | Docker Compose (App + DB + Queue + Scheduler + Nginx) |
| **Web Server**      | Nginx (producción) / Artisan Serve (desarrollo)       |
| **Seguridad**       | Roles por tenant, auditoría, rate limiting            |
| **Notificaciones**  | Email, SMS o WhatsApp (opcional)                      |

---

## ⚡ Comandos de utilidad

```bash
# Comandos de Laravel
php artisan tenants:list        # Mostrar tenants registrados
php artisan bookings:reminders  # Enviar recordatorios pendientes
php artisan reports:generate    # Generar métricas del periodo
php artisan db:seed --class=TenantSeeder  # Cargar datos de ejemplo

# Comandos de Docker
docker-compose up -d            # Levantar servicios
docker-compose down             # Detener servicios
docker-compose logs -f crm-app  # Ver logs en tiempo real
docker-compose exec crm-app php artisan migrate  # Ejecutar migraciones
docker-compose exec crm-db psql -U sivegm -d crm  # Acceder a PostgreSQL
```

> Puedes agregar más comandos personalizados en la carpeta `app/Console/Commands`.

---

## 📈 Reportes disponibles

-   Conversión de **leads → accounts**
-   **Ocupación** del personal (por semana o mes)
-   **Tasa de cancelación y no-show**
-   **Confirmación de citas**
-   **Ingresos por servicio y categoría** _(fase 2)_
-   **Rendimiento del staff**

---

## 🚀 Futuras extensiones (fase 2 y 3)

-   Integración con **pasarelas de pago** (Stripe, Mercado Pago).
-   Generación de **facturas automáticas**.
-   **Portal web o app móvil** para auto-agendamiento.
-   Recordatorios **inteligentes basados en comportamiento histórico**.
-   **API pública** para integraciones con otros CRMs o ERP.

---

## 🐛 Troubleshooting

### Problema: "Connection refused" al conectar con la base de datos

**Solución:**

```bash
# Verificar que el servicio de DB esté corriendo
docker-compose ps

# Ver logs de la base de datos
docker-compose logs crm-db

# Asegurarse de que DB_HOST=crm-db en .env
```

### Problema: Permisos en carpetas storage/bootstrap

**Solución:**

```bash
# Desde el contenedor
docker-compose exec crm-app chmod -R 775 storage bootstrap/cache
docker-compose exec crm-app chown -R www-data:www-data storage bootstrap/cache
```

### Problema: No se ejecutan las migraciones

**Solución:**

```bash
# Ejecutar manualmente
docker-compose exec crm-app php artisan migrate --force

# Ver el estado de las migraciones
docker-compose exec crm-app php artisan migrate:status
```

---

## 👨‍💻 Contribución

1. Crear una rama:

    ```bash
    git checkout -b feature/nueva-funcionalidad
    ```

2. Realizar cambios y ejecutar pruebas.
3. Enviar un Pull Request detallando la modificación.

---

## 🧾 Licencia

Este proyecto es propiedad intelectual de **Edgar Gómez Moctezuma**.
Su uso, redistribución o comercialización está sujeta a autorización previa.
