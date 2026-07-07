#!/bin/bash
# ============================================================
# deploy.sh
# Script de despliegue en el VPS
# Ejecutar en el VPS: bash deploy.sh
#
# Uso:
#   Primera vez:  bash deploy.sh --install
#   Actualizar:   bash deploy.sh
# ============================================================

set -e

VPS_IP="5.189.153.106"
REPO_URL="https://github.com/JARS2009/apk_gps.git"
APP_DIR="/opt/agrorastreo"
COMPOSE_FILE="docker-compose.production.yml"
ENV_FILE=".env"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log()   { echo -e "${GREEN}[✔]${NC} $1"; }
warn()  { echo -e "${YELLOW}[!]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }
info()  { echo -e "${BLUE}[→]${NC} $1"; }

# ─── Verificar docker ─────────────────────────────────────
command -v docker >/dev/null 2>&1 || error "Docker no está instalado"
command -v git    >/dev/null 2>&1 || error "Git no está instalado"

# ─── Primera instalación ──────────────────────────────────
if [[ "$1" == "--install" ]]; then
    info "=== Primera instalación en VPS $VPS_IP ==="

    # Clonar repositorio
    if [ ! -d "$APP_DIR" ]; then
        info "Clonando repositorio..."
        git clone "$REPO_URL" "$APP_DIR"
    else
        warn "Directorio $APP_DIR ya existe, haciendo pull..."
        cd "$APP_DIR" && git pull
    fi

    cd "$APP_DIR"

    # Configurar .env desde .env.production
    if [ ! -f "$ENV_FILE" ]; then
        if [ -f ".env.production" ]; then
            info "Copiando .env.production → .env"
            cp .env.production .env
            warn "⚠️  EDITA el archivo .env antes de continuar:"
            warn "   - Cambia DB_PASSWORD"
            warn "   - Cambia DB_ROOT_PASSWORD"
            warn "   - Genera APP_KEY con: docker run --rm php:8.3-cli php -r \"echo 'base64:'.base64_encode(random_bytes(32));\""
            echo ""
            read -p "¿Has editado el .env? (s/n): " confirm
            [[ "$confirm" != "s" ]] && error "Edita el .env primero y vuelve a ejecutar."
        else
            error "No se encontró .env.production. Sube el repo con ese archivo."
        fi
    fi

    # Construir y levantar
    info "Construyendo imágenes Docker (puede tardar 5-10 min la primera vez)..."
    docker compose -f "$COMPOSE_FILE" build --no-cache

    info "Levantando contenedores..."
    docker compose -f "$COMPOSE_FILE" up -d

    log "=== Instalación completa ==="
    log "App:        http://$VPS_IP"
    log "phpMyAdmin: http://$VPS_IP:5050"
    exit 0
fi

# ─── Actualización (deploy normal) ───────────────────────
info "=== Actualizando Agro-Rastreo en VPS $VPS_IP ==="

cd "$APP_DIR"

# Pull del repositorio
info "Obteniendo últimos cambios de Git..."
git pull origin main

# Rebuild solo de la app (no reconstruye MySQL/Redis)
info "Reconstruyendo imagen de la app..."
docker compose -f "$COMPOSE_FILE" build app

# Reiniciar contenedor de la app con zero-downtime
info "Reiniciando la aplicación..."
docker compose -f "$COMPOSE_FILE" up -d --no-deps app

# Limpiar imágenes antiguas
docker image prune -f

log "=== Deploy completado ==="
log "App:        http://$VPS_IP"
log "phpMyAdmin: http://$VPS_IP:5050"
log "Logs:       docker compose -f $COMPOSE_FILE logs -f app"
