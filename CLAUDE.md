# Soundboard

A Laravel + Livewire app where each signed-up user builds their own soundboards ("screens"): named boards of uploaded audio clips shown as a big-button touch grid. Multi-user, open self-registration, deployed via Docker Compose. Installable as a PWA with offline playback for screens already opened once.

## Stack

- Laravel 13, Livewire 3 (class-based components, not Volt for the product surface — Breeze's own scaffolding, e.g. `layout.navigation`/auth pages, does use Volt and that's fine to keep/edit) + Alpine.js, Tailwind CSS, Breeze auth (email/password + verification).
- MariaDB 11.8 (driver name `mariadb` in `config/database.php`, not `mysql`).
- Docker Compose: `nginx` (static files + fastcgi proxy) → `app` (php-fpm only) → `db` (MariaDB). No TLS termination in this stack — nginx only ever speaks plain HTTP; a real HTTPS deployment needs a reverse proxy/tunnel in front (see `TRUSTED_PROXIES` in `.env.example`).
- Repo root holds only deployment concerns (`Dockerfile`, `docker-compose.yml`, `docker/`, `scripts/`); the actual Laravel app lives in `src/` (its own `app/`, `resources/`, `composer.json`, etc. — a normal Laravel root, just nested).
- All commands are run through Docker — there is no local PHP/Node install expected. Do **not** follow generic "install PHP/Node locally" bootstrap advice for this repo. Both `composer` and `npm` run inside the `app` container (it has Node/npm installed alongside PHP) — no separate `node` compose service. Rebuild styles/JS with `docker compose exec app npm run build`.
- One `docker-compose.yml`, no override files, used identically for local dev and production — `src/` is *always* bind-mounted into `app` (whole tree) and `nginx` (`public/` only), and the `Dockerfile` builds a bare runtime image with no app code, `vendor/`, `node_modules/`, or `public/build` baked in (all of that comes from the bind mount + `docker/php/entrypoint.sh`'s runtime `composer install` / `npm ci` / `npm run build`). What differs between local and a real deployment lives entirely in `.env` (`APP_ENV`, `SESSION_SECURE_COOKIE`, `APP_URL`, `APP_PORT`) — see its comments in `.env.example`. Don't reintroduce a second compose file for this; that was tried and reverted twice as unnecessary complexity.

## Domain model

- `User` hasMany `Screen` (a named soundboard, owned by one user).
- `Screen` hasMany `Sound` (an uploaded audio file: name, emoji, optional `image_path` tile image, color, file_path — both on the `public` disk). The tile shows the image when present, else the emoji, else a default speaker emoji — see the `x-sound-icon` Blade component.
- A `Sound` can also be a video (mp4/webm/m4v by extension — `is_video` accessor, `Sound::MEDIA_RULES`, up to 1 GB; Livewire's 12 MB temp-upload default is raised in `AppServiceProvider`). Video tiles open a fullscreen `<video>` overlay in the player and are kept out of the audio engine's pre-decode list; `sw.js` never caches video (streamed only, no offline). The tile's cover is the usual `image_path`/emoji — there's no ffmpeg to extract posters.
- Ownership is enforced via `ScreenPolicy` / `SoundPolicy` — a user can only see/edit/play their own screens. Screens are private unless the owner ticks `screens.is_public` ("Public link" in Manage): then `ScreenPolicy::view` lets guests through (`?User`), and `screens.play` / `sounds.play` (the single-song autoplay page, `Screens\SoundPlayer`) are served without login (private screens still redirect guests to login from inside the component). Public screens/sounds are installable as their own Android home-screen apps: `PwaController` serves per-screen and per-sound manifests + square PNG icons (cropped from `screens.image_path` / `sounds.image_path`, default icon fallback), linked via the `$manifest`/`$touchIcon` layout data in `partials/pwa-head`; private screens keep the generic manifest because the WebAPK build server fetches those URLs without a login. Two other exceptions to per-user privacy: `users.is_admin` marks admins (the `admin` middleware + `EnsuresAdmin` guard the `admin.*` routes: dashboard, content moderation, user list, library management), and `LibrarySound` is an admin-curated shared library that users copy into their own screens (the copies are independent files, not references).

## Key paths

- `src/app/Livewire/Screens/{Index,Manage,Player}.php` + matching views in `src/resources/views/livewire/screens/` — the whole product surface.
- `src/routes/web.php` — `dashboard` (screens list), `screens.manage`, `screens.play`.
- `src/app/Livewire/Admin/*` + `src/resources/views/livewire/admin/` — admin area (`admin.*` routes).
- `src/app/Livewire/Broadcast/Setup.php` (`voice.setup`) + `src/resources/js/broadcast/` — optional "go live" voice broadcast: one Web Audio engine mixes mic and clips to a virtual cable so Discord etc. hears the board. Alpine glue is in `alpine.js` (`soundboard`, `broadcastBar`, ...); `engine.js` owns all audio state. The player's play/stop-all/keyboard shortcuts (1-9, Esc) go through it.
- `src/app/Livewire/Concerns/HasEditableSoundForm.php` — upload/edit form state shared by `ScreensManage` and `Adminibrary`. tile colours come from the `sound::colors` presets (`x-color-picker`); sound reordering in manage is drag-and-drop via alpine's bundled `x-sort` plus `reorder()`.
- `Dockerfile`, `docker-compose.yml`, `docker/` — deployment.
- `scripts/db-backup.sh`, `scripts/db-restore.sh` — database backup/restore against the running `db` container.
- `src/public/sw.js`, `src/public/manifest.webmanifest`, `src/resources/views/partials/pwa-head.blade.php` — PWA/offline support.
- `src/database/seeders/DatabaseSeeder.php` — the seed user (`test@example.com`) and a "Demo Board" screen with sample sounds (synthesized WAV tones, generated in-place so there's no binary sample audio checked into the repo). Runs on every `app` boot; written idempotently (checks before creating) since it's not a one-time migration.

## Running it

Same command for local dev and production — only `.env` differs (see above):

```bash
docker compose up -d --build
```

`.env.example` defaults to local trial use (`APP_ENV=local`, port 8090, no HTTPS); switching it to a real deployment is documented in README.md's "Deploying it for real" section.

The `app` container's entrypoint (`docker/php/entrypoint.sh`) installs composer deps if `vendor/` is missing and npm deps if `node_modules/` is missing (fresh clone), runs `npm run build` (every start, so pulled asset changes always land), waits for MariaDB, runs migrations + seeding, and — only when `APP_ENV=production` — runs `php artisan optimize`; otherwise it runs `optimize:clear` so cached config never masks a `.env`/testing override. There's no separate deploy-time migration or asset-build step to remember — `scripts/deploy.sh` just does `docker compose up -d --build` + `docker compose restart app` to re-trigger the entrypoint on an already-running container.

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
