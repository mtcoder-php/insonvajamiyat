#!/usr/bin/env bash
# Serverda yangilash: kodni tortib olish, bog'liqliklar, build, migratsiya, keshlar.
# Ishga tushirish (loyiha papkasida, www-data nomidan):
#   cd /var/www/insonvajamiyat && sudo -u www-data bash deploy/deploy.sh
#
# Xato bo'lsa skript to'xtaydi va sayt texnik rejimdan avtomatik chiqariladi.

set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/insonvajamiyat}"
BRANCH="${BRANCH:-main}"
PHP="${PHP:-/usr/bin/php}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"

cd "$APP_DIR"

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }

step "Texnik rejim (maintenance) yoqilmoqda"
"$PHP" artisan down --retry=30 --refresh=15 || true
trap '"$PHP" artisan up; echo "Xato! Sayt texnik rejimdan chiqarildi."' ERR

step "Kod yangilanmoqda ($BRANCH)"
git fetch --prune origin
git reset --hard "origin/$BRANCH"

step "Composer (production)"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

step "Frontend build"
"$PHP" artisan wayfinder:generate --with-form
npm ci --no-audit --no-fund
npm run build

step "Ma'lumotlar bazasi migratsiyasi"
"$PHP" artisan migrate --force

step "Keshlar"
"$PHP" artisan storage:link 2>/dev/null || true
"$PHP" artisan optimize:clear
"$PHP" artisan optimize

step "Navbat ishchilari qayta ishga tushirilmoqda"
"$PHP" artisan queue:restart

trap - ERR
step "Sayt yoqilmoqda"
"$PHP" artisan up

# OPcache (validate_timestamps=0) yangilangan fayllarni ko'rishi uchun
if command -v sudo >/dev/null && sudo -n true 2>/dev/null; then
    sudo systemctl reload "$PHP_FPM_SERVICE" || true
else
    echo "Eslatma: 'sudo systemctl reload $PHP_FPM_SERVICE' ni qo'lda bajaring (OPcache yangilanishi uchun)."
fi

printf '\n\033[1;32mTayyor: %s\033[0m\n' "$(git log -1 --format='%h %s')"
