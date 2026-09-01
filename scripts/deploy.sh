#!/bin/sh
# Brings the stack up and builds frontend assets, in the right order.
# Safe to re-run after every `git pull` or on a brand new server.
# Usage: scripts/deploy.sh
set -eu

cd "$(dirname "$0")/.."

docker compose up -d --build
docker compose run --rm node npm ci
docker compose run --rm node npm run build
docker compose restart app
