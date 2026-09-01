# Soundboard

A soundboard app where each person creates an account and builds their own screens (boards) of sound-button grids from their own uploaded audio clips. Built with Laravel + Livewire, runs entirely in Docker. Installable as a PWA, and a screen you've opened once keeps working offline.

## What it does

- Anyone can register their own account (name, email, password + email verification).
- Each user creates their own **screens** — named boards like "Minecraft" or "Memes".
- Each screen holds **sounds** — an uploaded audio clip with a label, an emoji or a custom tile image, and a color.
- The **play** view is a big-button, mobile-friendly grid — tap a button, it plays instantly (and you can mash buttons, sounds overlap).
- Everyone's screens are private to them — there's no sharing between accounts yet.
- Installable to a phone/desktop home screen (PWA), and a screen you've already opened once keeps working with no network.

## Repo layout

- `src/` — the Laravel app itself (everything you'd normally see at a Laravel project root: `app/`, `resources/`, `composer.json`, etc.).
- `docker-compose.yml`, `docker/`, `Dockerfile` — deployment, at the repo root, kept separate from the app so the two aren't tangled together. One compose file for both local dev and production — what differs between them lives in `.env`, not in extra `-f` flags.
- `scripts/` — `db-backup.sh` / `db-restore.sh`.

There is no local PHP/Node install expected — every command below runs through Docker.

## Running it locally (no domain needed)

`src/` is always bind-mounted into `app` and `nginx`, so code edits and asset rebuilds show up live without rebuilding the image — locally or in production.

```bash
cp .env.example .env   # first time only — then fill in DB_PASSWORD / DB_ROOT_PASSWORD / APP_KEY (see below)
docker compose up -d --build
docker compose run --rm node npm ci
docker compose run --rm node npm run build
```

Then open http://localhost:8090 — the seeded test account is `test@example.com` / `password`, with a "Demo Board" screen already populated with six sample sounds (real, short synthesized beep tones — see `DatabaseSeeder`) so there's something to click on immediately. Register your own account instead if you'd rather start from empty. `composer` and `npm` never need to be installed on your machine — both run inside their containers (`app` for composer, the one-off `node` service for npm). After editing CSS/JS, re-run the `npm run build` line above to refresh `src/public/build`.

To stop it: `docker compose down` (add `-v` to also wipe the database/uploads).

## Running the tests

```bash
docker compose exec app composer test
# or: docker compose exec app php artisan test / vendor/bin/phpunit — all equally safe
```

Tests run against an isolated in-memory sqlite database (`phpunit.xml`), never the real one — verified by checking the live user/screen/sound counts before and after a full run and confirming they don't move. Two things make that hold regardless of how you invoke the suite:

1. `src/tests/bootstrap.php` (phpunit's actual `bootstrap` file, not just `vendor/autoload.php`) clears the container's real `DB_*`/`APP_ENV`/etc. environment variables before Laravel loads at all. Without it, those real container env vars win over `phpunit.xml`'s testing config — PHPUnit's `<env force="true">` only reaches `$_ENV`/`putenv()`, not the `$_SERVER` values a fresh PHP process starts with (including the phpunit subprocess `php artisan test` shells out to) — and tests would silently run against the live MariaDB instead.
2. `.env` is bind-mounted read-only into the container (`docker-compose.yml`) purely so `artisan test`'s own built-in `clearEnv()` step can read it — without a physical `.env` file present it still works, but throws a harmless-but-noisy PHP warning on every single test.

## Deploying it for real (public internet)

You asked for this to be reachable from anywhere, with anyone able to sign up. A few things need to be true before you flip that on — all of it is just editing `.env`, then re-running the same `docker compose up -d --build` from above:

### 1. HTTPS — this stack doesn't terminate TLS itself

Nginx here only ever speaks plain HTTP. If you want a real `https://` domain, put something you already run in front of it — a reverse proxy, Cloudflare Tunnel, etc. — pointed at this host's published port, and set `TRUSTED_PROXIES` in `.env` (its IP/CIDR, or `*`) so Laravel trusts that proxy's forwarded headers and generates correct `https://` URLs / accepts secure cookies. Without anything in front, this is plain-HTTP-only, which is fine for a LAN/internal deployment but not for a public one with real passwords.

### 2. Fill in `.env`

Copy `.env.example` to `.env` if you haven't already (its defaults are for local trial use), and for a real deployment set:

- `APP_ENV=production` and `SESSION_SECURE_COOKIE=true` — local defaults to both off since there's no HTTPS on localhost.
- `APP_DOMAIN` / `APP_URL` — your real domain (or an internal hostname/IP if you're not exposing this publicly).
- `APP_PORT` — `80` if nothing else on the host needs it (defaults to `8090`, chosen for local dev so it doesn't need a free port 80).
- `APP_KEY` — generate one: `docker compose run --rm app php artisan key:generate --show`, paste the output in as `APP_KEY=base64:...`.
- `DB_PASSWORD` / `DB_ROOT_PASSWORD` — set these to long random values (a password manager's generator is fine).
- `TRUSTED_PROXIES` — see step 1.

### 3. Real email (important)

Because registration is open, new accounts must verify their email before they can use the app — otherwise anyone could sign up with a fake address. Right now `MAIL_MAILER=log` means verification emails don't actually get delivered anywhere.

Before real people sign up, sign up for a transactional email provider (Postmark, Mailgun, AWS SES, Resend, etc. — most have a free tier that's more than enough for a family app) and fill in `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS` in `.env` with the credentials they give you.

### 4. Start it

```bash
docker compose up -d --build
docker compose run --rm node npm ci && docker compose run --rm node npm run build
```

Migrations, `storage:link`, seeding, and Laravel's production caches (since `APP_ENV=production`) run automatically every time the `app` container starts — there's no separate deploy step to remember.

### 5. Uploaded sounds and the database persist automatically

Both live in named Docker volumes (`db_data`, `storage_uploads`), so `docker compose down` (without `-v`) and rebuilding the image for updates won't lose anything.

## Backing up / restoring the database

```bash
scripts/db-backup.sh                      # writes backups/soundboard-<timestamp>.sql.gz
scripts/db-restore.sh backups/soundboard-<timestamp>.sql.gz   # asks to confirm before overwriting
```

Both talk to whichever `db` container is currently running (local or prod). Uploaded sound files live separately in the `storage_uploads` volume — back that up too if you care about it, e.g. `docker run --rm -v soundboard_storage_uploads:/data -v $PWD:/backup alpine tar czf /backup/storage-backup.tar.gz /data`.

## Updating the app after code changes

```bash
git pull
docker compose run --rm node npm run build   # only needed if CSS/JS changed
docker compose restart app                   # re-runs migrations/seeding/config caching
```

`src/` is bind-mounted, so a `git pull` alone already updates the running code — `restart` (not `up -d --build`) is what applies new migrations and re-caches config, since that's the entrypoint script running again. Only rebuild the image (`docker compose up -d --build`) if the `Dockerfile` itself changed (new PHP extension, base image bump, etc.) — routine app updates don't need it. Either way the database and its data are untouched.

## Installing it as an app / offline use

The site is an installable PWA (manifest + service worker) — "Add to Home Screen" on a phone, or the install icon in a desktop browser's address bar. Once a screen's play page has been opened at least once while online, its page, styles, and every sound's audio/image are cached, so reopening that same screen with no network at all still works (the play button is client-side JavaScript, not a server round-trip). Icons are simple flat SVG-derived PNGs (`src/public/icons/`) — regenerate them from `src/public/favicon.svg` with `rsvg-convert` if you ever change the mark.

## Notes / things you may want to add later

- **No admin controls.** Anyone who verifies their email gets an account; there's no way to remove a user from the UI yet (would need direct database access, or a small admin screen added later).
- **No sharing between users.** If your son wants to show a screen to a friend, the friend needs their own account and their own copy of the sounds for now.
- **File limits.** Uploads are capped at 20MB per sound / 4MB per tile image, audio must be mp3/wav/ogg/m4a/aac and images jpg/png/webp/gif — adjust in `src/app/Livewire/Screens/Manage.php` and `docker/php/php.ini`/`docker/nginx/default.conf` if you need to raise that.
- **No CAPTCHA/bot protection on registration.** Fine for a low-traffic family app; if spam signups become a problem, that's the first thing to add.
