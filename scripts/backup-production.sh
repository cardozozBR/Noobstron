#!/usr/bin/env bash
set -euo pipefail

cd /opt/noobstron

BACKUP_ROOT="/opt/noobstron-backups"
STAMP="$(date -u +%Y%m%d-%H%M%S)"
BACKUP_DIR="$BACKUP_ROOT/$STAMP"

mkdir -p "$BACKUP_DIR"

docker compose -f compose.production.yaml exec -T postgres sh -lc '
pg_dump \
  -U "$POSTGRES_USER" \
  -d "$POSTGRES_DB" \
  -Fc
' > "$BACKUP_DIR/database.dump"

docker compose -f compose.production.yaml exec -T backend \
  tar -czf - -C /var/www/html/storage/app . \
  > "$BACKUP_DIR/storage.tar.gz"

git rev-parse HEAD > "$BACKUP_DIR/git-commit.txt"

sha256sum \
  "$BACKUP_DIR/database.dump" \
  "$BACKUP_DIR/storage.tar.gz" \
  > "$BACKUP_DIR/SHA256SUMS"

docker run --rm \
  -v "$BACKUP_DIR:/backup:ro" \
  postgres:18 \
  pg_restore --list /backup/database.dump >/dev/null

tar -tzf "$BACKUP_DIR/storage.tar.gz" >/dev/null

# Retenção: mantém 14 dias.
find "$BACKUP_ROOT" \
  -mindepth 1 \
  -maxdepth 1 \
  -type d \
  -mtime +14 \
  -exec rm -rf {} +

echo "Backup concluído: $BACKUP_DIR"