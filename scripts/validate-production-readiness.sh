#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

COMPOSE="compose.production.yaml"
ENV_FILE="backend/.env.production"

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

declare -A ENV_VALUES

read_dotenv() {
  local file="$1"
  local line key value

  while IFS= read -r line || [[ -n "$line" ]]; do
    line="${line#"${line%%[![:space:]]*}"}"
    line="${line%"${line##*[![:space:]]}"}"

    [[ -z "$line" ]] && continue
    [[ "$line" == \#* ]] && continue
    [[ "$line" != *"="* ]] && continue

    key="${line%%=*}"
    value="${line#*=}"

    key="${key#"${key%%[![:space:]]*}"}"
    key="${key%"${key##*[![:space:]]}"}"

    value="${value#"${value%%[![:space:]]*}"}"
    value="${value%"${value##*[![:space:]]}"}"

    if [[ ${#value} -ge 2 ]]; then
      if [[ "$value" == \"*\" && "$value" == *\" ]]; then
        value="${value:1:${#value}-2}"
      elif [[ "$value" == \'*\' && "$value" == *\' ]]; then
        value="${value:1:${#value}-2}"
      fi
    fi

    ENV_VALUES["$key"]="$value"
  done < "$file"
}

env_value() {
  local key="$1"
  printf '%s' "${ENV_VALUES[$key]:-}"
}

assert_nonempty() {
  local key="$1"
  local message="$2"
  local value

  value="$(env_value "$key")"

  [[ -n "$value" ]] || fail "$key inválido: $message"

  echo "$key=ok"
}

assert_equals() {
  local key="$1"
  local expected="$2"
  local message="$3"
  local value

  value="$(env_value "$key")"

  [[ "$value" == "$expected" ]] || fail "$key inválido: $message"

  echo "$key=ok"
}

echo
echo "=== PRODUCTION READINESS ==="
echo "root=$ROOT"

[[ -f "$COMPOSE" ]] || fail "$COMPOSE não encontrado."
[[ -f "$ENV_FILE" ]] || fail "backend/.env.production não encontrado."

if git ls-files --error-unmatch backend/.env.production >/dev/null 2>&1; then
  fail "backend/.env.production está versionado."
fi

echo
echo "=== COMPOSE ==="

docker compose -f "$COMPOSE" config --quiet \
  || fail "compose.production.yaml inválido."

mapfile -t SERVICES < <(
  docker compose -f "$COMPOSE" config --services
)

for required in backend worker scheduler nginx postgres; do
  found=0

  for service in "${SERVICES[@]}"; do
    if [[ "$service" == "$required" ]]; then
      found=1
      break
    fi
  done

  [[ "$found" == "1" ]] \
    || fail "Serviço obrigatório ausente: $required"

  echo "service-$required=ok"
done

echo
echo "=== PRODUCTION ENV CONTRACT ==="

read_dotenv "$ENV_FILE"

assert_equals \
  "APP_ENV" \
  "production" \
  "deve ser production"

app_debug="$(env_value APP_DEBUG)"
[[ "${app_debug,,}" == "false" ]] \
  || fail "APP_DEBUG inválido: deve ser false"
echo "APP_DEBUG=ok"

app_key="$(env_value APP_KEY)"
[[ -n "$app_key" && "$app_key" != "base64:" ]] \
  || fail "APP_KEY inválido: deve conter uma chave real"
echo "APP_KEY=ok"

app_url="$(env_value APP_URL)"
[[ "$app_url" == https://* ]] \
  || fail "APP_URL inválido: deve começar com https://"
echo "APP_URL=ok"

assert_nonempty \
  "POSTGRES_DB" \
  "obrigatório"

assert_nonempty \
  "POSTGRES_USER" \
  "obrigatório"

postgres_password="$(env_value POSTGRES_PASSWORD)"
[[ -n "$postgres_password" ]] \
  || fail "POSTGRES_PASSWORD inválido: use segredo forte e não-placeholder"

if [[ "$postgres_password" =~ change_me|example|password ]]; then
  fail "POSTGRES_PASSWORD inválido: use segredo forte e não-placeholder"
fi

echo "POSTGRES_PASSWORD=ok"

assert_nonempty \
  "APP_DB_USER" \
  "obrigatório"

app_db_password="$(env_value APP_DB_PASSWORD)"
[[ -n "$app_db_password" ]] \
  || fail "APP_DB_PASSWORD inválido: use segredo forte e não-placeholder"

if [[ "$app_db_password" =~ change_me|example|password ]]; then
  fail "APP_DB_PASSWORD inválido: use segredo forte e não-placeholder"
fi

echo "APP_DB_PASSWORD=ok"

assert_equals \
  "DB_CONNECTION" \
  "pgsql" \
  "deve ser pgsql"

assert_equals \
  "DB_HOST" \
  "postgres" \
  "deve apontar para o serviço postgres"

db_database="$(env_value DB_DATABASE)"
postgres_db="$(env_value POSTGRES_DB)"

[[ -n "$db_database" && "$db_database" == "$postgres_db" ]] \
  || fail "DB_DATABASE inválido: deve coincidir com POSTGRES_DB"

echo "DB_DATABASE=ok"

db_username="$(env_value DB_USERNAME)"
app_db_user="$(env_value APP_DB_USER)"

[[ -n "$db_username" && "$db_username" == "$app_db_user" ]] \
  || fail "DB_USERNAME inválido: deve coincidir com APP_DB_USER"

echo "DB_USERNAME=ok"

db_password="$(env_value DB_PASSWORD)"

[[ -n "$db_password" && "$db_password" == "$app_db_password" ]] \
  || fail "DB_PASSWORD inválido: deve coincidir com APP_DB_PASSWORD"

echo "DB_PASSWORD=ok"

assert_equals \
  "SESSION_DRIVER" \
  "database" \
  "deve ser database"

session_secure="$(env_value SESSION_SECURE_COOKIE)"
[[ "${session_secure,,}" == "true" ]] \
  || fail "SESSION_SECURE_COOKIE inválido: deve ser true em HTTPS"

echo "SESSION_SECURE_COOKIE=ok"

session_http_only="$(env_value SESSION_HTTP_ONLY)"
[[ "${session_http_only,,}" == "true" ]] \
  || fail "SESSION_HTTP_ONLY inválido: deve ser true"

echo "SESSION_HTTP_ONLY=ok"

mail_mailer="$(env_value MAIL_MAILER)"

if [[ "$mail_mailer" != "smtp" && "$mail_mailer" != "resend" ]]; then
  fail "MAIL_MAILER inválido: deve ser smtp ou resend"
fi

echo "MAIL_MAILER=ok"

assert_nonempty \
  "MAIL_FROM_ADDRESS" \
  "obrigatório antes do go-live"

assert_nonempty \
  "MARKETING_CONTACT_EMAIL" \
  "obrigatório antes do go-live"

if [[ "$mail_mailer" == "smtp" ]]; then
  assert_nonempty \
    "MAIL_HOST" \
    "obrigatório para SMTP"

  assert_nonempty \
    "MAIL_PORT" \
    "obrigatório para SMTP"
fi

if [[ "$mail_mailer" == "resend" ]]; then
  assert_nonempty \
    "RESEND_API_KEY" \
    "obrigatório para Resend"
fi

echo
echo "=== STATIC DEPLOY CONTRACT ==="

DEPLOY_FILE="scripts/deploy-production.sh"

[[ -f "$DEPLOY_FILE" ]] \
  || fail "$DEPLOY_FILE não encontrado."

grep -Fq \
  'build backend worker scheduler nginx' \
  "$DEPLOY_FILE" \
  || fail "Deploy não contém contrato obrigatório: build backend worker scheduler nginx"

grep -Fq \
  'up -d --force-recreate backend worker scheduler nginx' \
  "$DEPLOY_FILE" \
  || fail "Deploy não contém contrato obrigatório: up -d --force-recreate backend worker scheduler nginx"

grep -Fq \
  'service_container scheduler' \
  "$DEPLOY_FILE" \
  || fail "Deploy não contém contrato obrigatório do scheduler"

echo "scheduler-deploy=ok"

BACKUP_FILE="scripts/backup-production.sh"

[[ -f "$BACKUP_FILE" ]] \
  || fail "$BACKUP_FILE não encontrado."

if grep -Fq \
  'psql -U postgres -d nossa_plataforma' \
  "$BACKUP_FILE"; then
  fail "Backup ainda contém credenciais/database hardcoded."
fi

if grep -Fq \
  'database = "nossa_plataforma"' \
  "$BACKUP_FILE"; then
  fail "Backup ainda contém credenciais/database hardcoded."
fi

echo "backup-env-resolution=ok"

echo
echo "=== AUTOMATED READINESS GREEN ==="
echo "PRODUCTION_READINESS_OK"
echo
echo "Ainda são obrigatórios fora deste script:"
echo "- DNS principal e wildcard;"
echo "- TLS/HTTPS válido no proxy/load balancer;"
echo "- teste real de envio transacional;"
echo "- backup + restore em ambiente de homologação;"
echo "- monitoramento externo;"
echo "- provider de pagamento real antes de cobrança automática;"
echo "- credenciais reais das integrações efetivamente habilitadas;"
echo "- revisão jurídica dos textos públicos."