# Soundboard

Laravel + Livewire app. Each user builds soundboards ("screens") of uploaded audio clips as a big-button touch grid. Runs in Docker. Installable as a PWA with offline playback.

## Layout

- `src/` — the Laravel app.
- `docker-compose.yml`, `docker/`, `Dockerfile` — deployment.
- `scripts/` — `db-backup.sh` / `db-restore.sh`.

No local PHP/Node needed — everything runs through Docker.

## Run it locally

```bash
cp .env.example .env   # fill in DB_PASSWORD / DB_ROOT_PASSWORD / APP_KEY
scripts/deploy.sh
```

Open http://localhost:8090. Seeded account: `test@example.com` / `password` (has a "Demo Board" screen with sample sounds). Re-run `npm run build` after CSS/JS changes.

Stop: `docker compose down` (add `-v` to wipe DB/uploads too).

## Tests

```bash
docker compose exec app composer test
```

Runs against an isolated in-memory sqlite DB, not the real one.

## Deploying for real

Edit `.env`, then `docker compose up -d --build` as above.

1. **HTTPS** — nginx here only speaks plain HTTP. Put a reverse proxy/tunnel in front and set `TRUSTED_PROXIES`.
2. **`.env` values** — `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`, `APP_DOMAIN`/`APP_URL`, `APP_PORT` (80 if free), `APP_KEY` (generate: `docker compose run --rm app php artisan key:generate --show`), `DB_PASSWORD`/`DB_ROOT_PASSWORD`, `TRUSTED_PROXIES`.
3. **Real email** — registration requires email verification, but `MAIL_MAILER=log` doesn't actually send anything. Set up a transactional provider (Postmark, Mailgun, SES, Resend, ...) and fill in the `MAIL_*` vars before real people sign up.
4. **Start** — `scripts/deploy.sh` (same script as local run above). Migrations/seeding/caching run automatically on container start.
5. **Data persists** — DB and uploads live in named volumes (`db_data`, `storage_uploads`), safe across `down`/rebuild (without `-v`).

## Admin panel

`/admin` — dashboard/usage stats, all users' sounds (search + delete), user list (delete, cascades their screens/sounds), and a shared sound library any user can add from into their own screens. Gated by an `is_admin` flag on `users`, `false` by default; no UI to grant it yet, so promote an account via tinker:

```bash
docker compose exec app php artisan tinker --execute="App\Models\User::where('email', 'you@example.com')->update(['is_admin' => true]);"
```

## Backup / restore

```bash
scripts/db-backup.sh                      # writes backups/soundboard-<timestamp>.sql.gz
scripts/db-restore.sh backups/soundboard-<timestamp>.sql.gz
```

Uploaded files live in the `storage_uploads` volume — back that up separately if needed.

## Updating after code changes

```bash
git pull
scripts/deploy.sh
```

Only rebuilds the image if the `Dockerfile` changed; otherwise it's a no-op there and just refreshes assets, migrations, and caches.

## Installing as an app / offline use

Installable PWA — "Add to Home Screen" or a desktop browser's install icon. Once a screen's play page has been opened online, it works offline after that. Icons live in `src/public/icons/`, regenerate from `favicon.svg` with `rsvg-convert` if the mark changes.

## Known gaps

- No UI to grant/revoke the admin flag — tinker only (see Admin panel above).
- No sharing between users' screens (the admin-curated library is one-way: admin → user).
- Upload limits: 20MB/sound, 4MB/tile image (`Manage.php`, `docker/php/php.ini`, `docker/nginx/default.conf`).
- No CAPTCHA/bot protection on registration.
