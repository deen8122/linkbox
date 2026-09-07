#!/usr/bin/env bash
#
# Полная установка и запуск проекта одной командой: make install
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_ROOT"

SAIL="./vendor/bin/sail"

C_GREEN="\033[0;32m"
C_YELLOW="\033[0;33m"
C_RED="\033[0;31m"
C_CYAN="\033[0;36m"
C_BOLD="\033[1m"
C_RESET="\033[0m"

step() { echo -e "\n${C_CYAN}==>${C_RESET} ${C_BOLD}$1${C_RESET}"; }
info() { echo -e "    $1"; }
warn() { echo -e "${C_YELLOW}!${C_RESET} $1"; }
die()  { echo -e "${C_RED}Ошибка:${C_RESET} $1" >&2; exit 1; }

trap 'die "Установка прервана (строка $LINENO). Смотри вывод выше."' ERR

# ---------------------------------------------------------------------------
step "Проверяю Docker"
command -v docker >/dev/null 2>&1 || die "Docker не найден. Установи Docker Desktop / Docker Engine и повтори."
docker info >/dev/null 2>&1 || die "Docker демон не запущен. Запусти Docker и повтори."
info "Docker найден и запущен."

# ---------------------------------------------------------------------------
step "Проверяю .env"
if [ ! -f .env ]; then
    cp .env.example .env
    info "Создан .env из .env.example."
fi

# ---------------------------------------------------------------------------
step "Проверяю зависимости composer (vendor/)"
if [ ! -d vendor ]; then
    info "vendor/ отсутствует, ставлю composer-зависимости во временном контейнере..."
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$PROJECT_ROOT":/var/www/html \
        -w /var/www/html \
        composer:latest \
        composer install --ignore-platform-reqs --no-interaction --prefer-dist
else
    info "vendor/ уже на месте, пропускаю composer install."
fi

[ -x "$SAIL" ] || die "$SAIL не найден после установки зависимостей."

# ---------------------------------------------------------------------------
step "Проверяю APP_KEY"
if ! grep -qE '^APP_KEY=base64:.+' .env; then
    info "APP_KEY пуст, будет сгенерирован после старта контейнеров."
    NEED_KEY=1
else
    info "APP_KEY уже задан."
    NEED_KEY=0
fi

# ---------------------------------------------------------------------------
step "Собираю и поднимаю контейнеры (docker compose up -d)"
$SAIL up -d

# ---------------------------------------------------------------------------
step "Жду готовности MySQL"
DB_SERVICE="mysql"
ATTEMPTS=0
MAX_ATTEMPTS=60
until $SAIL exec -T "$DB_SERVICE" mysqladmin ping -h 127.0.0.1 --silent >/dev/null 2>&1; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "$ATTEMPTS" -ge "$MAX_ATTEMPTS" ]; then
        die "MySQL не поднялся за отведённое время."
    fi
    printf "."
    sleep 2
done
echo ""
info "MySQL готов."

# ---------------------------------------------------------------------------
if [ "$NEED_KEY" -eq 1 ]; then
    step "Генерирую APP_KEY"
    $SAIL artisan key:generate --ansi
fi

# ---------------------------------------------------------------------------
step "Накатываю миграции и сидеры (migrate:fresh --seed)"
$SAIL artisan migrate:fresh --seed

# ---------------------------------------------------------------------------
step "Настраиваю storage:link"
$SAIL artisan storage:link || true

# ---------------------------------------------------------------------------
step "Ставлю npm-зависимости"
$SAIL npm install

# ---------------------------------------------------------------------------
step "Собираю фронтенд (npm run build)"
# Удаляем след от предыдущего "npm run dev" — иначе Laravel будет считать,
# что Vite dev-сервер ещё жив, и отдавать битые ссылки на localhost:5173.
rm -f public/hot
$SAIL npm run build

# ---------------------------------------------------------------------------
APP_URL_VALUE="$(grep -E '^APP_URL=' .env | tail -n1 | cut -d '=' -f2-)"
APP_PORT_VALUE="$(grep -E '^APP_PORT=' .env | tail -n1 | cut -d '=' -f2-)"
APP_PORT_VALUE="${APP_PORT_VALUE:-80}"
APP_URL_VALUE="${APP_URL_VALUE:-http://localhost:${APP_PORT_VALUE}}"

echo -e "\n${C_GREEN}${C_BOLD}Проект собран и запущен!${C_RESET}"
echo -e "\nОткрой в браузере:"
echo -e "  ${C_BOLD}${C_CYAN}${APP_URL_VALUE}${C_RESET}"

echo -e "\n${C_BOLD}Тестовые данные для входа:${C_RESET}"
echo -e "  Email: ${C_BOLD}test@example.com${C_RESET}"
echo -e "  Пароль не используется — вход только по одноразовому коду."
echo -e "  Код запроси на странице входа, затем возьми его из лога:"
echo -e "    storage/logs/laravel.log  (строка \"Login code generated\")"
echo -e "  Посмотреть код одной командой:"
echo -e "    ${SAIL} exec laravel.test tail -n 50 storage/logs/laravel.log | grep -i 'Login code' | tail -n1"

echo -e "\nПолезные команды:"
echo -e "  ${SAIL} up -d          # поднять контейнеры"
echo -e "  ${SAIL} down           # остановить контейнеры"
echo -e "  ${SAIL} npm run dev    # dev-сервер фронтенда с hot reload"
echo ""
