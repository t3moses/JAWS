# LocalStack Setup for JAWS

This guide explains how to set up and use LocalStack to test AWS SES email functionality locally.

## Overview

JAWS uses AWS SDK for PHP to send emails via AWS Simple Email Service (SES). For local development and testing, we use LocalStack to simulate AWS SES without actually sending emails or incurring AWS costs.

## Prerequisites

1. **Docker Desktop** installed and running
2. **PHP 8.1+** with composer installed
3. **JAWS dependencies** installed (`composer install`)

## Quick Start

### 1. Start LocalStack

```bash
# Start LocalStack container
docker-compose up -d

# Verify it's running
docker-compose ps

# Check SES service status
curl http://localhost:4566/_localstack/health | grep ses
```

Expected output should show `"ses": "available"`.

### 2. Verify Email Address

LocalStack requires email addresses to be verified before sending (just like real AWS SES).

Create a verification script (`verify_email.php`):

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Aws\Ses\SesClient;

$sesClient = new SesClient([
    'region' => 'ca-central-1',
    'version' => 'latest',
    'credentials' => ['key' => 'test', 'secret' => 'test'],
    'endpoint' => 'http://localhost:4566',
    'http' => ['verify' => false],
]);

$sesClient->verifyEmailIdentity([
    'EmailAddress' => 'noreply@nepean-sailing.ca',
]);

echo "Email verified!\n";
```

Run it:
```bash
php verify_email.php
```

### 3. Configure Environment Variables

The `.env` file should already be configured for LocalStack:

```env
# LocalStack SES Configuration
SES_REGION=ca-central-1
SES_SMTP_USERNAME=test
SES_SMTP_PASSWORD=test
SES_ENDPOINT=http://localhost:4566
EMAIL_FROM=noreply@nepean-sailing.ca
EMAIL_FROM_NAME="JAWS - Nepean Sailing Club"
```

**Important**: Use quotes around values with spaces or special characters.

### 4. Start the Development Server

```bash
php -S localhost:8000 -t public
```

### 5. Test Email Sending

```bash
curl -X POST "http://localhost:8000/api/admin/notifications/Fri%20May%2029" \
  -H "Content-Type: application/json" \
  -H "X-User-FirstName: Admin" \
  -H "X-User-LastName: User" \
  -d '{"include_calendar": false}'
```

Expected response:
```json
{
  "success": true,
  "data": {
    "success": true,
    "emails_sent": 4,
    "message": "Sent 4 notification emails for event Fri May 29"
  }
}
```

### 6. View Sent Emails in LocalStack Logs

```bash
# Watch logs in real-time
docker-compose logs -f localstack

# View recent logs
docker-compose logs --tail=50 localstack | grep "SendEmail"
```

You should see lines like:
```
localstack  | 2026-01-27T19:30:09.703 DEBUG --- Email saved at: /tmp/localstack/state/ses/...
localstack  | 2026-01-27T19:30:09.704  INFO --- AWS ses.SendEmail => 200
```

## Architecture

### AWS SDK Integration

JAWS uses AWS SDK for PHP instead of PHPMailer for email sending:

```php
// src/Infrastructure/Service/AwsSesEmailService.php
use Aws\Ses\SesClient;

$sesClient = new SesClient([
    'region' => $region,
    'version' => 'latest',
    'credentials' => [
        'key' => $accessKeyId,
        'secret' => $secretAccessKey,
    ],
    'endpoint' => $endpoint, // Points to LocalStack for local development
    'http' => ['verify' => false], // Disable SSL verification for LocalStack
]);
```

### Environment Variable Loading

The application uses `vlucas/phpdotenv` to load `.env` variables:

```php
// public/index.php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Populate getenv() for backward compatibility
foreach ($_ENV as $key => $value) {
    if (!getenv($key)) {
        putenv("{$key}={$value}");
    }
}
```

## Switching Between LocalStack and Production AWS

The email service automatically detects the environment:

- **Local Development**: If `SES_ENDPOINT` is set in `.env`, it uses LocalStack
- **Production**: If `SES_ENDPOINT` is empty or unset, it uses real AWS SES

### Production Configuration

For production deployment, remove or comment out the `SES_ENDPOINT`:

```env
# AWS SES (Production)
SES_REGION=ca-central-1
SES_SMTP_USERNAME=<your-actual-aws-access-key>
SES_SMTP_PASSWORD=<your-actual-aws-secret-key>
# SES_ENDPOINT=  # Leave empty for production AWS
EMAIL_FROM=noreply@nepean-sailing.ca
EMAIL_FROM_NAME="JAWS - Nepean Sailing Club"
```

## Troubleshooting

### "Email address not verified" Error

LocalStack requires email addresses to be verified before use. Run the verification script:

```bash
php verify_email.php
```

### Emails Not Sending (0 emails sent)

1. **Check LocalStack is running**:
   ```bash
   curl http://localhost:4566/_localstack/health | grep ses
   ```

2. **Verify environment variables are loaded**:
   ```php
   <?php
   require 'vendor/autoload.php';
   $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
   $dotenv->safeLoad();
   foreach ($_ENV as $key => $value) putenv("{$key}={$value}");
   echo "SES_ENDPOINT: " . getenv('SES_ENDPOINT') . "\n";
   ```

3. **Check email address is verified**:
   ```bash
   php verify_email.php
   ```

4. **Restart the PHP development server**:
   ```bash
   # Stop old server (Ctrl+C) and restart
   php -S localhost:8000 -t public
   ```

### SSL Certificate Errors

If you see SSL errors when connecting to LocalStack, ensure the `http.verify` option is set to `false` in the SES client configuration:

```php
$config['http'] = ['verify' => false];
```

### Viewing Saved Emails

LocalStack saves emails as JSON files. To view them:

```bash
# Access LocalStack container
docker exec -it jaws-localstack-1 /bin/bash

# Navigate to email storage
cd /tmp/localstack/state/ses/

# List saved emails
ls -lt

# View email content
cat <email-id>.json
```

## Running Tests

The API test suite includes email notification tests:

```bash
# Ensure LocalStack is running
docker-compose up -d

# Ensure sender email is verified
php verify_email.php

# Run all tests
php Tests/api_test.php
```

Expected output:
```
Test: POST /api/admin/notifications/{eventId}
✓ PASSED

=================================
Test Results
=================================
Passed: 12
Failed: 0
Total:  12

✓ All tests passed!
```

## Dependencies

The LocalStack integration requires these Composer packages:

- `aws/aws-sdk-php` - AWS SDK for PHP
- `vlucas/phpdotenv` - Environment variable loader

These are automatically installed with `composer install`.

## Stopping LocalStack

```bash
# Stop LocalStack container
docker-compose down

# Stop and remove volumes (clears saved emails)
docker-compose down -v
```

## Next Steps

- For production deployment, configure real AWS SES credentials
- Set up AWS SES email identity verification in AWS Console
- Configure DKIM and SPF records for your domain
- Monitor email sending via AWS CloudWatch
