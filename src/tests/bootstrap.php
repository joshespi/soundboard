<?php

// The app container sets APP_ENV, DB_CONNECTION, etc. as real environment
// variables (see docker-compose.yml), so php-fpm and `artisan` work
// normally outside tests. A fresh PHP CLI process — including the phpunit
// subprocess `php artisan test` shells out to — starts with those same
// values already in $_SERVER, and PHPUnit's <env force="true"> in
// phpunit.xml only overrides $_ENV/putenv(), not $_SERVER. Left alone,
// that stale $_SERVER value wins over phpunit.xml's testing config, and
// tests silently run against the real database instead of the isolated
// sqlite one. Clearing it here — before Laravel is even loaded — makes
// every way of running the suite (`composer test`, `php artisan test`,
// `vendor/bin/phpunit`) safe, not just the one wrapped to unset these.
foreach (['APP_ENV', 'APP_MAINTENANCE_DRIVER', 'BCRYPT_ROUNDS', 'BROADCAST_CONNECTION', 'CACHE_STORE', 'DB_CONNECTION', 'DB_DATABASE', 'DB_URL', 'DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD', 'MAIL_MAILER', 'QUEUE_CONNECTION', 'SESSION_DRIVER', 'PULSE_ENABLED', 'TELESCOPE_ENABLED', 'NIGHTWATCH_ENABLED'] as $key) {
    unset($_SERVER[$key], $_ENV[$key]);
}

require __DIR__.'/../vendor/autoload.php';
