# Soundboard

A Laravel + Livewire app where each signed-up user builds their own soundboards ("screens"): named boards of uploaded audio clips shown as a big-button touch grid. Multi-user, open self-registration, deployed via Docker Compose. Installable as a PWA with offline playback for screens already opened once.

## Stack

- Laravel 13, Livewire 3 (class-based components, not Volt for the product surface — Breeze's own scaffolding, e.g. `layout.navigation`/auth pages, does use Volt and that's fine to keep/edit) + Alpine.js, Tailwind CSS, Breeze auth (email/password + verification).
- MariaDB 11.8 (driver name `mariadb` in `config/database.php`, not `mysql`).
- Docker Compose: `nginx` (static files + fastcgi proxy) → `app` (php-fpm only) → `db` (MariaDB). No TLS termination in this stack — nginx only ever speaks plain HTTP; a real HTTPS deployment needs a reverse proxy/tunnel in front (see `TRUSTED_PROXIES` in `.env.example`).
- Repo root holds only deployment concerns (`Dockerfile`, `docker-compose.yml`, `docker/`, `scripts/`); the actual Laravel app lives in `src/` (its own `app/`, `resources/`, `composer.json`, etc. — a normal Laravel root, just nested).
- All commands are run through Docker — there is no local PHP/Node install expected. Do **not** follow generic "install PHP/Node locally" bootstrap advice for this repo. `composer` runs inside the `app` container; `npm` runs via the one-off `node` compose service (`docker compose run --rm node npm run build`).
- One `docker-compose.yml`, no override files, used identically for local dev and production — `src/` is *always* bind-mounted into `app` (whole tree) and `nginx` (`public/` only), and the `Dockerfile` builds a bare runtime image with no app code, `vendor/`, or `public/build` baked in (all of that comes from the bind mount + `docker/php/entrypoint.sh`'s runtime `composer install` / the `node` service's `npm run build`). What differs between local and a real deployment lives entirely in `.env` (`APP_ENV`, `SESSION_SECURE_COOKIE`, `APP_URL`, `APP_PORT`) — see its comments in `.env.example`. Don't reintroduce a second compose file for this; that was tried and reverted twice as unnecessary complexity.

## Domain model

- `User` hasMany `Screen` (a named soundboard, owned by one user).
- `Screen` hasMany `Sound` (an uploaded audio file: name, emoji, optional `image_path` tile image, color, file_path — both on the `public` disk). The tile shows the image when present, else the emoji, else a default speaker emoji — see the `x-sound-icon` Blade component.
- Ownership is enforced via `ScreenPolicy` / `SoundPolicy` — a user can only see/edit/play their own screens. There is no cross-user sharing or admin role.

## Key paths

- `src/app/Livewire/Screens/{Index,Manage,Player}.php` + matching views in `src/resources/views/livewire/screens/` — the whole product surface.
- `src/routes/web.php` — `dashboard` (screens list), `screens.manage`, `screens.play`.
- `Dockerfile`, `docker-compose.yml`, `docker/` — deployment.
- `scripts/db-backup.sh`, `scripts/db-restore.sh` — database backup/restore against the running `db` container.
- `src/public/sw.js`, `src/public/manifest.webmanifest`, `src/resources/views/partials/pwa-head.blade.php` — PWA/offline support.
- `src/database/seeders/DatabaseSeeder.php` — the seed user (`test@example.com`) and a "Demo Board" screen with sample sounds (synthesized WAV tones, generated in-place so there's no binary sample audio checked into the repo). Runs on every `app` boot; written idempotently (checks before creating) since it's not a one-time migration.

## Running it

Same command for local dev and production — only `.env` differs (see above):

```bash
docker compose up -d --build
docker compose run --rm node npm ci && docker compose run --rm node npm run build
```

`.env.example` defaults to local trial use (`APP_ENV=local`, port 8090, no HTTPS); switching it to a real deployment is documented in README.md's "Deploying it for real" section.

The `app` container's entrypoint (`docker/php/entrypoint.sh`) installs composer deps if `vendor/` is missing (fresh clone), waits for MariaDB, runs migrations + seeding, and — only when `APP_ENV=production` — runs `php artisan optimize`; otherwise it runs `optimize:clear` so cached config never masks a `.env`/testing override. There's no separate deploy-time migration step to remember.

## Testing

```bash
docker compose exec app composer test
```

`composer test`, `php artisan test`, and `vendor/bin/phpunit` are all equally safe and equally clean (0 warnings) — verified by diffing live user/screen/sound counts across a full run. Two pieces, both needed:

1. `src/tests/bootstrap.php` (phpunit's `bootstrap` file, set in `phpunit.xml` — not the default `vendor/autoload.php`) clears the container's real `APP_ENV`/`DB_*`/etc. environment variables before Laravel loads at all. Without it, those real container env vars win over `phpunit.xml`'s testing overrides (PHPUnit's `<env force="true">` only reaches `$_ENV`/`putenv`, not the `$_SERVER` values a fresh CLI process starts with — including the phpunit subprocess `php artisan test` shells out to), and tests would run against the live MariaDB database instead of the isolated sqlite `:memory:` one. Don't move this fix into a wrapper around one specific command — that was tried first and missed `php artisan test` run directly, since it needs to hold regardless of how the suite gets invoked.
2. `.env` is bind-mounted read-only into `app` (`docker-compose.yml`) so `artisan test`'s own `clearEnv()` (in `nunomaduro/collision`'s `TestCommand`) can read it by path — it reads `.env` purely to get the list of variable *names* to strip, not their values, but throws a `file_get_contents` PHP warning on every test when the file doesn't exist. This isn't a DB-safety concern (bootstrap.php above already covers that on its own) — it was leftover noise on an otherwise-passing run.

## Conventions to keep

- Livewire full-page components (`Screens\*`) use `#[Layout('layouts.app')]`, not Volt — stay consistent with that style rather than converting them.
- File uploads always go through the `public` disk (`Storage::disk('public')`), never the default disk directly, since `FILESYSTEM_DISK` is intentionally `public` for this app.
- `QUEUE_CONNECTION=sync` on purpose — there's no queue worker container. Don't add `ShouldQueue` jobs without also adding a worker service.
- Shared Blade components (`x-primary-button`, `x-sound-icon`, etc.) carry the app's visual language — prefer extending those over one-off styling in a view.
