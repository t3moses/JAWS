#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Log Write Diagnostic
 *
 * Manual/ops tool — NOT part of the scheduled cron pipeline by default.
 *
 * Writes one entry through the application's LoggerInterface (the same
 * Monolog RotatingFileHandler used everywhere else in the app) and emails
 * the admin notification address with the result. Exists to verify that
 * logs/app-*.log is writable by whichever user this script runs as,
 * independent of waiting for a real notify.php send to exercise it.
 *
 * Background: on 2026-09-04, cron's `bitnami` user hit "Permission denied"
 * appending to logs/app-2026-09-04.log because Apache (running as `daemon`)
 * had created that day's file first with group-read-only permissions. Fixed
 * on the server via a setgid bit + default ACL on logs/ (see ops notes / the
 * DEPLOYMENT.md cron section). This script is a lightweight way to confirm
 * that fix continues to hold across each day's file rotation, without
 * waiting for a real event's notification window.
 *
 * Usage (run manually):
 *   php bin/log-write-test.php
 *
 * To re-enable temporarily as an hourly cron check (e.g. while validating a
 * permissions fix), add to crontab and comment it back out once confirmed:
 *   0 * * * * /usr/bin/php /opt/bitnami/jaws/bin/log-write-test.php >> /opt/bitnami/jaws/logs/cron.log 2>&1
 */

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/vendor/autoload.php';

$envFile = $projectRoot . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

// Always pin DB_PATH to an absolute path, same as bin/notify.php.
putenv('DB_PATH=' . $projectRoot . '/database/jaws.db');

use App\Application\Port\Service\EmailServiceInterface;
use Psr\Log\LoggerInterface;

$container = require $projectRoot . '/config/container.php';
$config    = require $projectRoot . '/config/config.php';

$now = (new \DateTimeImmutable('now', new \DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/Toronto')))
    ->format('Y-m-d H:i:s');
$logFile = $projectRoot . '/logs/app-' . date('Y-m-d') . '.log';

// -----------------------------------------------------------------------
// Exercise the exact code path that failed: Monolog writing to app-*.log
// -----------------------------------------------------------------------
$logError = null;
try {
    $container->get(LoggerInterface::class)->info('cron.log_write_test', ['at' => $now]);
} catch (\Throwable $e) {
    $logError = $e->getMessage();
}

$status = $logError === null ? 'OK' : 'FAILED';
$body   = "Log write test at {$now}\n"
    . "Target file: {$logFile}\n"
    . "Result: {$status}\n"
    . ($logError !== null ? "Error: {$logError}\n" : '');

echo $body;

// -----------------------------------------------------------------------
// Report the result by email regardless of outcome
// -----------------------------------------------------------------------
try {
    $adminEmail = $config['email']['admin_notification_email'];
    $container->get(EmailServiceInterface::class)->send(
        $adminEmail,
        "[JAWS] Log write test: {$status} ({$now})",
        nl2br($body)
    );
} catch (\Throwable $e) {
    echo "Email send also failed: {$e->getMessage()}\n";
}

exit(0);
