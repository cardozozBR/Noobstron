#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

COMPOSE="compose.production.yaml"
SKIP_GIT_PULL="${SKIP_GIT_PULL:-0}"
SKIP_BACKUP="${SKIP_BACKUP:-0}"

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

service_container() {
  docker compose -f "$COMPOSE" ps -q "$1"
}

echo
echo "=== PREFLIGHT ==="

[[ -f "$COMPOSE" ]] || fail "$COMPOSE não encontrado."
[[ -f backend/.env.production ]] || fail "backend/.env.production não encontrado."

if git ls-files --error-unmatch backend/.env.production >/dev/null 2>&1; then
  fail "backend/.env.production está versionado."
fi

docker compose -f "$COMPOSE" config --quiet
echo "COMPOSE_CONFIG_OK"

echo
echo "=== GIT ==="

branch="$(git branch --show-current)"
[[ -n "$branch" ]] || fail "Falha ao identificar branch."

echo "branch=$branch"

if [[ "$SKIP_GIT_PULL" != "1" ]]; then
  [[ -z "$(git status --porcelain)" ]] || fail "Working tree não está limpa."

  git fetch origin
  git pull --ff-only origin "$branch"
else
  echo "SKIP_GIT_PULL"
fi

commit="$(git rev-parse --short HEAD)"
echo "commit=$commit"

if [[ "$SKIP_BACKUP" != "1" ]]; then
  echo
  echo "=== BACKUP PRE-DEPLOY ==="

  "$ROOT/scripts/backup-production.sh"
  echo "BACKUP_PRE_DEPLOY_OK"
else
  echo "SKIP_BACKUP"
fi

echo
echo "=== BUILD ==="

docker compose -f "$COMPOSE" build backend worker scheduler nginx
echo "BUILD_OK"

echo
echo "=== POSTGRES ==="

docker compose -f "$COMPOSE" up -d postgres

postgres_container="$(service_container postgres)"
[[ -n "$postgres_container" ]] || fail "Container postgres não encontrado."

postgres_healthy=0

for _ in $(seq 1 60); do
  status="$(
    docker inspect \
      -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' \
      "$postgres_container" 2>/dev/null || true
  )"

  if [[ "$status" == "healthy" ]]; then
    postgres_healthy=1
    break
  fi

  sleep 2
done

[[ "$postgres_healthy" == "1" ]] || fail "PostgreSQL não ficou healthy."

echo "POSTGRES_HEALTHY_OK"

echo
echo "=== MIGRATIONS ==="

docker compose -f "$COMPOSE" run --rm backend php artisan migrate --force
echo "MIGRATIONS_OK"

echo
echo "=== SUBIR SERVICOS ==="

docker compose -f "$COMPOSE" up -d --force-recreate backend worker scheduler nginx

echo
echo "=== HEALTHCHECKS ==="

for service in backend nginx postgres worker; do
  container="$(service_container "$service")"
  [[ -n "$container" ]] || fail "Container de $service não encontrado."

  healthy=0

  for _ in $(seq 1 60); do
    status="$(
      docker inspect \
        -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' \
        "$container" 2>/dev/null || true
    )"

    if [[ "$status" == "healthy" ]]; then
      healthy=1
      break
    fi

    sleep 2
  done

  if [[ "$healthy" != "1" ]]; then
    docker compose -f "$COMPOSE" ps
    fail "$service não ficou healthy."
  fi

  echo "$service=healthy"
done

scheduler_container="$(service_container scheduler)"
[[ -n "$scheduler_container" ]] || fail "Container scheduler não encontrado."

scheduler_running=0

for _ in $(seq 1 30); do
  status="$(
    docker inspect \
      -f '{{.State.Status}}' \
      "$scheduler_container" 2>/dev/null || true
  )"

  if [[ "$status" == "running" ]]; then
    scheduler_running=1
    break
  fi

  sleep 2
done

if [[ "$scheduler_running" != "1" ]]; then
  docker compose -f "$COMPOSE" ps
  docker compose -f "$COMPOSE" logs --tail=100 scheduler
  fail "Scheduler não permaneceu running."
fi

echo "scheduler=running"
echo "ALL_SERVICES_OK"

echo
echo "=== HTTP HEALTH ==="

curl -kfsS --max-time 15 https://127.0.0.1/up >/dev/null
echo "HTTP_UP_OK"

echo
echo "=== DATABASE FINAL ==="

docker compose -f "$COMPOSE" exec -T backend php artisan migrate:status
echo "DATABASE_OK"

echo
echo "=== QUEUE FINAL ==="

docker compose -f "$COMPOSE" exec -T worker \
  php artisan queue:monitor default --max=1000

docker compose -f "$COMPOSE" exec -T backend \
  php artisan queue:failed

echo "QUEUE_OK"

echo
echo "=== SERVICOS ==="

docker compose -f "$COMPOSE" ps

echo
echo "=== DEPLOY CONCLUIDO ==="
echo "commit=$commit"
echo "DEPLOY_OK"