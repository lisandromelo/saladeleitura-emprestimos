#!/usr/bin/env bash
# Verifica e sobe os serviços Docker da Sala de Leitura.
# Uso: ./scripts/levantar.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

info() { printf '→ %s\n' "$*"; }
ok() { printf '✓ %s\n' "$*"; }
fail() { printf '✗ %s\n' "$*" >&2; exit 1; }

env_val() {
    local key="$1" default="${2:-}"
    local value=""
    if [[ -f .env ]]; then
        value="$(grep -E "^${key}=" .env | tail -n1 | cut -d= -f2- | tr -d '\r' | sed 's/^["'\'']//;s/["'\'']$//')"
    fi
    printf '%s' "${value:-$default}"
}

APP_PORT="$(env_val APP_PORT 8089)"
VITE_PORT="$(env_val VITE_PORT 5174)"
MAILPIT_UI_PORT="$(env_val MAILPIT_UI_PORT 8026)"
APP_URL="http://localhost:${APP_PORT}"

if ! command -v docker >/dev/null 2>&1; then
    fail "Docker não está instalado."
fi

if ! docker info >/dev/null 2>&1; then
    fail "O daemon do Docker não está no ar. No Linux: sudo systemctl start docker"
fi

if [[ ! -f .env ]]; then
    if [[ -f .env.example ]]; then
        cp .env.example .env
        info "Criei .env a partir de .env.example"
    else
        fail "Não há .env nem .env.example na raiz do projeto."
    fi
fi

info "Estado atual:"
docker compose ps -a

info "Subindo serviços (web, app, queue, scheduler, node, db, mailpit)…"
docker compose up -d

info "Aguardando o MySQL ficar saudável…"
for _ in $(seq 1 40); do
    health="$(docker compose ps db --format '{{.Health}}' 2>/dev/null || true)"
    if [[ "$health" == "healthy" ]]; then
        ok "MySQL saudável"
        break
    fi
    sleep 2
done
if [[ "${health:-}" != "healthy" ]]; then
    docker compose logs db --tail 40
    fail "MySQL não ficou saudável a tempo."
fi

info "Aguardando o PHP-FPM…"
for _ in $(seq 1 30); do
    if docker compose exec -T app php -r 'exit(0);' >/dev/null 2>&1; then
        ok "PHP-FPM no ar"
        break
    fi
    sleep 1
done

info "Checando o banco pela aplicação…"
if ! docker compose exec -T app php artisan db:show >/dev/null; then
    fail "A aplicação não conseguiu falar com o MySQL."
fi
ok "Aplicação conectada em db:3306"

http_code() {
    curl -sS -o /dev/null -w '%{http_code}' --max-time 5 "${APP_URL}/login" || printf '000'
}

code="$(http_code)"
if [[ "$code" == "502" || "$code" == "000" ]]; then
    info "Nginx sem upstream (HTTP ${code}). Reiniciando o web…"
    docker compose restart web >/dev/null
    sleep 2
    code="$(http_code)"
fi

if [[ "$code" != "200" && "$code" != "302" ]]; then
    docker compose logs web app --tail 30
    fail "App em ${APP_URL}/login respondeu HTTP ${code}."
fi
ok "App responde em ${APP_URL} (HTTP ${code})"

echo
docker compose ps
echo
ok "Pronto."
printf '  App:     %s\n' "${APP_URL}"
printf '  Vite:    http://localhost:%s\n' "${VITE_PORT}"
printf '  Mailpit: http://localhost:%s\n' "${MAILPIT_UI_PORT}"
printf '  MySQL:   localhost:%s (no container: db:3306)\n' "$(env_val MYSQL_PUBLISH_PORT 3308)"
