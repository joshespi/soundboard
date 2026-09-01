# Shared setup for db-backup.sh / db-restore.sh. Source, don't execute.
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    echo "No .env found in $(pwd) — copy .env.example to .env first." >&2
    exit 1
fi

# Only need DB_DATABASE out of .env; avoid sourcing the whole file since it
# may contain values with characters the shell would otherwise interpret.
DB_DATABASE=$(grep -m1 '^DB_DATABASE=' .env | cut -d= -f2-)

if [ -z "$DB_DATABASE" ]; then
    echo "DB_DATABASE is not set in .env" >&2
    exit 1
fi
