# LocalStack Setup for JAWS

This guide explains how to set up and use LocalStack to test AWS SES email functionality locally.

## Overview

JAWS uses AWS SDK for PHP to send emails via AWS Simple Email Service (SES). For local development and testing, we use LocalStack to simulate AWS SES without actually sending emails or incurring AWS costs.

## Quick Reference

If you've already completed the setup, use these commands to start development:

```bash
# 1. Start LocalStack and nginx
docker-compose -f LocalStack/docker-compose.yml up -d

# 2. Verify email address (first time only, or after clearing LocalStack data)
php LocalStack/verify_email.php

# 3. Start PHP development server
php -S localhost:8000 -t public

# 4. Open frontend in browser
# Navigate to http://localhost:3000
```

To stop services:

```bash
# Stop LocalStack and nginx
docker-compose -f LocalStack/docker-compose.yml down

# Stop PHP server (Ctrl+C in its terminal)
```

## Prerequisites

1. **Docker Desktop** installed and running
2. **PHP 8.1+** with composer installed
3. **JAWS dependencies** installed (`composer install`)

## Frontend Setup

The JAWS backend API is designed to work with the [nsc-sdc](https://github.com/RobJohnston/nsc-sdc) frontend. To view and interact with the full application, you need to clone the frontend repository as a sibling directory to JAWS.

### Directory Structure

The backend (JAWS) and frontend (nsc-sdc) should be sibling directories:

**Windows:**
```
d:\source\repos\
├── JAWS\                # This repository (backend API)
│   ├── LocalStack\
│   ├── src\
│   └── ...
└── nsc-sdc\             # Frontend repository (sibling)
    ├── index.html
    ├── events.html
    ├── css\
    └── js\
```

**Linux/Mac:**
```
~/source/repos/
├── JAWS/                # This repository (backend API)
│   ├── LocalStack/
│   ├── src/
│   └── ...
└── nsc-sdc/             # Frontend repository (sibling)
    ├── index.html
    ├── events.html
    ├── css/
    └── js/
```

### One-Time Setup

**Windows:**
```bash
# Navigate to the parent directory
cd d:\source\repos

# Clone the frontend repository (if it doesn't already exist)
git clone https://github.com/RobJohnston/nsc-sdc.git
```

**Linux/Mac:**
```bash
# Navigate to the parent directory
cd ~/source/repos

# Clone the frontend repository (if it doesn't already exist)
git clone https://github.com/RobJohnston/nsc-sdc.git
```

### Port Allocation

- **Frontend (nginx)**: `http://localhost:3000` - Static file server
- **Backend API**: `http://localhost:8000` - PHP development server
- **LocalStack SES**: `http://localhost:4566` - AWS SES simulation

The nginx container automatically proxies `/api/*` requests from the frontend to the PHP backend.

## Quick Start

### 1. Start LocalStack and nginx

```bash
# Start LocalStack and nginx containers
docker-compose -f LocalStack/docker-compose.yml up -d

# Verify both services are running
docker-compose -f LocalStack/docker-compose.yml ps

# Check SES service status
curl http://localhost:4566/_localstack/health | grep ses

# Verify nginx is serving the frontend
curl http://localhost:3000/
```

Expected output:

- LocalStack health check should show `"ses": "available"`
- nginx curl should return HTML content from the frontend's `index.html`

### 2. Verify Email Address

LocalStack requires email addresses to be verified before sending (just like real AWS SES).

The repository includes a verification script at `LocalStack/verify_email.php` that automatically:
- Loads environment variables from `.env`
- Configures the SES client for LocalStack
- Verifies the email address from `EMAIL_FROM`
- Lists all verified email addresses
- Displays helpful output and error messages

Run it:
```bash
php LocalStack/verify_email.php
```

Expected output:

```text
LocalStack Email Verification
==================================================

Configuration:
  Region: ca-central-1
  Endpoint: http://localhost:4566
  Email: noreply@nsc-sdc.ca

Verifying email address...
✓ Email address verified successfully!

Listing all verified email addresses:
  ✓ noreply@nsc-sdc.ca

==================================================
Email verification complete!
You can now send emails through LocalStack.
```

### 3. Configure Environment Variables

The `.env` file should already be configured for LocalStack:

```env
# LocalStack SES Configuration
SES_REGION=ca-central-1
SES_SMTP_USERNAME=test
SES_SMTP_PASSWORD=test
SES_ENDPOINT=http://localhost:4566
EMAIL_FROM=noreply@nsc-sdc.ca
EMAIL_FROM_NAME="Nepean Sailing Club - Social Day Cruising"
```

**Important**: Use quotes around values with spaces or special characters.

### 4. Start the Development Server

```bash
php -S localhost:8000 -t public
```

### 5. Test Email Sending

Admin endpoints require JWT authentication. First, obtain a JWT token (see [Authentication](#authentication) section below), then test email sending:

```bash
# Replace YOUR_JWT_TOKEN with an actual JWT token
curl -X POST "http://localhost:8000/api/admin/notifications/Fri%20May%2029" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
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

## Authentication

JAWS uses JWT (JSON Web Token) authentication for all protected API endpoints. The application has transitioned to JWT-only authentication; the previous name-based header authentication (`X-User-FirstName`, `X-User-LastName`) has been removed.

### JWT Token Requirements

- **Protected endpoints** (user-specific and admin endpoints) require: `Authorization: Bearer <token>`
- **Public endpoints** (like `GET /api/events`) do not require authentication

### Obtaining a JWT Token

The current implementation requires a valid JWT token for authenticated endpoints. JWT tokens contain:

- User identification (user ID, email, or name)
- Token expiration time
- Signature for verification

**Configuration**: Set `JWT_SECRET` in your `.env` file (minimum 32 characters)

```env
JWT_SECRET=your-very-secure-secret-key-at-least-32-characters-long
JWT_EXPIRATION_MINUTES=60
```

### Testing with JWT

For local development and testing:

1. **For API tests**: The `Tests/Integration/api_test.php` script may need to be updated to generate or use JWT tokens
2. **For manual testing**: You'll need to generate a valid JWT token using the secret from your `.env` file
3. **For Postman**: Update the Postman collection to include the `Authorization: Bearer <token>` header

**Example authenticated request:**

```bash
curl -X GET "http://localhost:8000/api/assignments" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Transitioning from Name-Based Auth

If you have existing scripts or tests using the old `X-User-FirstName` and `X-User-LastName` headers:

1. Update all requests to use `Authorization: Bearer <token>` instead
2. Remove any references to `X-User-FirstName` and `X-User-LastName` headers
3. The nginx CORS configuration has been updated to only allow `Content-Type` and `Authorization` headers

### 6. View the Frontend

Open your browser and navigate to:

```
http://localhost:3000
```

**Expected Behavior:**

- The frontend landing page loads
- You can navigate through the application pages (events, account, dashboard, etc.)
- API requests are automatically proxied through nginx to the PHP backend on port 8000

**Verify API Integration:**

1. Open browser DevTools (F12) → Network tab
2. Interact with the frontend (e.g., view events, sign in)
3. Observe API requests:

   - Requests should show as `localhost:3000/api/*` (not `localhost:8000`)
   - nginx transparently proxies these to the backend
   - No CORS errors should appear

### 7. View Sent Emails in LocalStack Logs

```bash
# Watch logs in real-time
docker-compose -f LocalStack/docker-compose.yml logs localstack

# View recent logs
docker-compose -f LocalStack/docker-compose.yml logs --tail=50 localstack | grep "SendEmail"
```

You should see lines like:

```text
localstack  | 2026-01-27T19:30:09.703 DEBUG --- Email saved at: /tmp/localstack/state/ses/...
localstack  | 2026-01-27T19:30:09.704  INFO --- AWS ses.SendEmail => 200
```

### 8. Making Frontend Changes

The frontend files are mounted as a Docker volume, enabling live development:

**Workflow:**

1. Edit files in your frontend repository sibling directory (e.g., modify `index.html`, update CSS, change JavaScript)
2. Save your changes
3. Refresh your browser (no container restart needed)
4. Changes are immediately visible

**Example (Windows):**

```bash
# Edit the frontend homepage
notepad ..\nsc-sdc\index.html

# Save changes, then refresh browser at http://localhost:3000
```

**Example (Linux/Mac):**

```bash
# Edit the frontend homepage
nano ../nsc-sdc/index.html

# Save changes, then refresh browser at http://localhost:3000
```

The read-only volume mount prevents accidental modifications from within the container while allowing seamless development on your host machine.

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
EMAIL_FROM=noreply@nsc-sdc.ca
EMAIL_FROM_NAME="Nepean Sailing Club - Social Day Cruising"
```

## Troubleshooting

### nginx Not Starting

If the nginx container fails to start:

1. **Check Docker is running**:
   ```bash
   docker --version
   docker ps
   ```

2. **Verify frontend directory exists**:
   ```bash
   # Windows
   dir d:\source\repos\nsc-sdc\index.html

   # Linux/Mac
   ls ~/source/repos/nsc-sdc/index.html
   ```

3. **Check docker-compose logs**:
   ```bash
   docker-compose -f LocalStack/docker-compose.yml logs nginx
   ```

4. **Common issues**:
   - Frontend repository not cloned: Clone it as a sibling to JAWS (`../nsc-sdc`)
   - Volume mount path incorrect: Verify docker-compose.yml has `../../nsc-sdc:/usr/share/nginx/html:ro`
   - Port 3000 already in use: Stop other services using port 3000

### Frontend Shows 404

If browsing to `http://localhost:3000` shows a 404 error:

1. **Verify frontend repository cloned correctly**:
   ```bash
   # Windows
   ls d:\source\repos\nsc-sdc\

   # Linux/Mac
   ls ~/source/repos/nsc-sdc/

   # Should show index.html, events.html, css/, js/, etc.
   ```

2. **Check nginx volume mount** in `LocalStack/docker-compose.yml`:
   ```yaml
   volumes:
     - ../../nsc-sdc:/usr/share/nginx/html:ro
   ```

3. **Verify files exist**:
   ```bash
   # Windows
   type d:\source\repos\nsc-sdc\index.html

   # Linux/Mac
   cat ~/source/repos/nsc-sdc/index.html
   ```

4. **Check nginx container logs**:
   ```bash
   docker-compose -f LocalStack/docker-compose.yml logs nginx
   ```

### API Requests Failing

If the frontend loads but API requests fail:

1. **Ensure PHP dev server is running** on port 8000:
   ```bash
   # In separate terminal
   php -S localhost:8000 -t public
   ```

2. **Test API directly**:
   ```bash
   curl http://localhost:8000/api/events
   # Should return JSON response
   ```

3. **Check nginx proxy configuration** in `LocalStack/nginx.conf`:
   ```nginx
   location /api/ {
       proxy_pass http://host.docker.internal:8000;
       # ...
   }
   ```

4. **Verify `host.docker.internal` resolves** (Windows Docker Desktop):
   ```bash
   # From inside nginx container
   docker-compose -f LocalStack/docker-compose.yml exec nginx ping host.docker.internal
   ```

5. **Check nginx logs for proxy errors**:
   ```bash
   docker-compose -f LocalStack/docker-compose.yml logs nginx | grep "api"
   ```

### CORS Errors (Unexpected)

CORS errors should **not** occur with this setup because nginx proxies API requests as same-origin.

If you see CORS errors:

1. **Verify frontend API base URL** is set to `http://localhost:3000/api`, not `http://localhost:8000/api`
   - Check JavaScript service files in `nsc-sdc/js/`
   - API requests should go through nginx proxy, not directly to backend

2. **Check browser Network tab**:
   - API requests should show as `localhost:3000/api/*`
   - If showing `localhost:8000/api/*`, the frontend is bypassing nginx

3. **Verify PHP CORS middleware** is configured in `config/config.php`:
   ```bash
   # Check CORS configuration
   grep -A 5 "cors" config/config.php
   ```

### "Email address not verified" Error

LocalStack requires email addresses to be verified before use. Run the verification script:

```bash
php LocalStack/verify_email.php
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
   php LocalStack/verify_email.php
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
docker-compose -f LocalStack/docker-compose.yml exec localstack /bin/bash

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
docker-compose -f LocalStack/docker-compose.yml up -d

# Ensure sender email is verified
php LocalStack/verify_email.php

# Run all tests
php Tests/Integration/api_test.php
```

Expected output:

```text
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
# Stop all containers (LocalStack and nginx)
docker-compose -f LocalStack/docker-compose.yml down

# Stop and remove volumes (clears saved emails)
docker-compose -f LocalStack/docker-compose.yml down -v
```

**Note:** This command stops both LocalStack and nginx containers. Remember to also stop the PHP development server (Ctrl+C in its terminal).

## Next Steps

- For production deployment, configure real AWS SES credentials
- Set up AWS SES email identity verification in AWS Console
- Configure DKIM and SPF records for your domain
- Monitor email sending via AWS CloudWatch
