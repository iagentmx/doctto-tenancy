#!/usr/bin/env bash
set -e

DB_HOST="${DB_HOST:-crm-db}"
DB_PORT="${DB_PORT:-5432}"
DB_USERNAME="${DB_USERNAME:-sivegm}"

echo "🔄 Esperando a PostgreSQL en ${DB_HOST}:${DB_PORT}..."

# Esperar a que la base de datos esté lista
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" >/dev/null 2>&1; do
  echo "   ↪ Esperando... (reintento en 2s)"
  sleep 2
done

echo "✅ Base de datos disponible"

# Solo ejecutar migraciones si es el contenedor principal (crm-app)
if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
  echo "🔄 Ejecutando migraciones..."
  php artisan migrate --force --no-interaction
  echo "✅ Migraciones completadas"
else
  echo "ℹ️  Rol: ${CONTAINER_ROLE} - Omitiendo migraciones"
fi

echo "🚀 Iniciando: $@"
exec "$@"