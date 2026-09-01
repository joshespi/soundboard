#!/bin/sh
# Dumps the `db` container's database to backups/, gzipped and timestamped.
# Usage: scripts/db-backup.sh
set -eu

. "$(dirname "$0")/lib.sh"

mkdir -p backups
out="backups/${DB_DATABASE}-$(date +%Y%m%d_%H%M%S).sql.gz"

echo "Backing up '$DB_DATABASE' to $out ..."
docker compose exec -T db sh -c 'exec mariadb-dump --single-transaction --routines -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
    | gzip > "$out"

echo "Done: $out ($(du -h "$out" | cut -f1))"
