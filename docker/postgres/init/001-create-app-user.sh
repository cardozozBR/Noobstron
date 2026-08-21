#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 \
  --username "$POSTGRES_USER" \
  --dbname "$POSTGRES_DB" \
  --set=app_user="$APP_DB_USER" \
  --set=app_password="$APP_DB_PASSWORD" \
  --set=db_name="$POSTGRES_DB" \
  <<'EOSQL'

SELECT format(
    'CREATE USER %I WITH PASSWORD %L',
    :'app_user',
    :'app_password'
)
WHERE NOT EXISTS (
    SELECT FROM pg_catalog.pg_roles
    WHERE rolname = :'app_user'
)\gexec

SELECT format(
    'GRANT CONNECT ON DATABASE %I TO %I',
    :'db_name',
    :'app_user'
)\gexec

SELECT format(
    'GRANT USAGE, CREATE ON SCHEMA public TO %I',
    :'app_user'
)\gexec

EOSQL
