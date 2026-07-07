#!/bin/bash
# ============================================================
# docker/scripts/start.sh
# Script de arranque del contenedor de producción
# ============================================================

set -e

echo "🚀 Iniciando Agro-Rastreo en modo producción..."

# ─── Esperar a MySQL ──────────────────────────────────────
echo "⏳ Esperando conexión a MySQL..."
until php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null; do
    echo "   MySQL no disponible aún, reintentando en 3s..."
    sleep 3
done
echo "✅ MySQL conectado"

# ─── Ejecutar migraciones ─────────────────────────────────
echo "🔄 Ejecutando migraciones..."
php artisan migrate --force --no-interaction

# ─── Seeders solo en primera ejecución ───────────────────
if [ ! -f /var/www/html/storage/.seeded ]; then
    echo "🌱 Ejecutando seeders..."
    php artisan db:seed --force --no-interaction && touch /var/www/html/storage/.seeded
fi

# ─── Optimizar Laravel para producción ───────────────────
echo "⚡ Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ─── Crear enlace simbólico storage ──────────────────────
php artisan storage:link --force 2>/dev/null || true

# ─── Permisos finales ─────────────────────────────────────
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Aplicación lista en http://5.189.153.106"
echo "🗄️  phpMyAdmin en http://5.189.153.106:5050"

# ─── Iniciar supervisord ──────────────────────────────────
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
