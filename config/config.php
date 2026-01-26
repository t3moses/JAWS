<?php

declare(strict_types=1);

/**
 * Application Configuration
 *
 * Central configuration file for the application.
 * Environment variables can be loaded from .env file if needed.
 */

return [
    // Database
    'database' => [
        'path' => getenv('DB_PATH') ?: __DIR__ . '/../database/jaws.db',
    ],

    // AWS SES (Email Service)
    'email' => [
        'region' => getenv('SES_REGION') ?: 'ca-central-1',
        'smtp_username' => getenv('SES_SMTP_USERNAME') ?: '',
        'smtp_password' => getenv('SES_SMTP_PASSWORD') ?: '',
        'from_address' => getenv('EMAIL_FROM') ?: 'noreply@nepean-sailing.ca',
        'from_name' => getenv('EMAIL_FROM_NAME') ?: 'JAWS - Nepean Sailing Club',
    ],

    // Application
    'app' => [
        'debug' => getenv('APP_DEBUG') === 'true',
        'timezone' => getenv('APP_TIMEZONE') ?: 'America/Toronto',
        'url' => getenv('APP_URL') ?: 'http://localhost',
    ],

    // CORS (for future frontend)
    'cors' => [
        'allowed_origins' => explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: '*'),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-User-FirstName', 'X-User-LastName'],
    ],
];
