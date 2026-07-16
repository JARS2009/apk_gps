#!/bin/bash
# ============================================================
# deploy-pwa.sh
# Script para desplegar la implementación PWA en el VPS
# Ejecutar DENTRO del VPS como root:
#   bash /opt/agrorastreo/deploy-pwa.sh
# ============================================================

set -e

APP_DIR="/opt/agrorastreo"
COMPOSE_FILE="docker-compose.production.yml"

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

log()  { echo -e "${GREEN}[✔]${NC} $1"; }
info() { echo -e "${BLUE}[→]${NC} $1"; }

cd "$APP_DIR"

info "=== Deploy PWA - Agro-Rastreo ==="

# 1. Pull de los últimos cambios
info "Obteniendo cambios de Git..."
git pull origin main

# 2. Reconstruir imagen
info "Reconstruyendo imagen Docker (incluye compilación PWA)..."
docker compose -f "$COMPOSE_FILE" build app

# 3. Reiniciar app
info "Reiniciando contenedor..."
docker compose -f "$COMPOSE_FILE" up -d --no-deps app

# 4. Esperar a que la app arranque
sleep 5

# 5. Ejecutar migraciones (push_subscriptions)
info "Ejecutando migraciones..."
docker compose -f "$COMPOSE_FILE" exec app php artisan migrate --force

# 6. Limpiar caché
info "Limpiando caché..."
docker compose -f "$COMPOSE_FILE" exec app php artisan config:clear
docker compose -f "$COMPOSE_FILE" exec app php artisan route:clear
docker compose -f "$COMPOSE_FILE" exec app php artisan view:clear

# 7. Agregar VAPID keys al .env del VPS (si no están)
ENV_FILE="$APP_DIR/.env"
if ! grep -q "VAPID_PUBLIC_KEY" "$ENV_FILE"; then
    info "Añadiendo VAPID keys al .env del VPS..."
    echo "" >> "$ENV_FILE"
    echo "# PWA Web Push (VAPID)" >> "$ENV_FILE"
    echo "VAPID_PUBLIC_KEY=BGFk-Q0IPOgdPvh-SKYINXcrpNSIJK7RzoImNdBydd_-AlQwCSO5kUJqrxEJ7zU8B_boWh9ryCSNu2ji8GnCyEI" >> "$ENV_FILE"
    echo "VAPID_PRIVATE_KEY=9UNNsqrxqxk5DO6JR1gGIoe-5zWF5Q9Alw1qy5XrlB8" >> "$ENV_FILE"
    echo "VITE_VAPID_PUBLIC_KEY=BGFk-Q0IPOgdPvh-SKYINXcrpNSIJK7RzoImNdBydd_-AlQwCSO5kUJqrxEJ7zU8B_boWh9ryCSNu2ji8GnCyEI" >> "$ENV_FILE"
    # Reiniciar para aplicar el .env
    docker compose -f "$COMPOSE_FILE" up -d --no-deps app
fi

log "=== PWA desplegada correctamente ==="
log "Abre https://5.189.153.106 en Chrome Android para instalar"
log "Prueba la notificación: POST /push/test (requiere estar logueado)"
