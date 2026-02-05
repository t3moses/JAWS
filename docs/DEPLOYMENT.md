# JAWS Deployment Guide

Complete guide for deploying JAWS to production on AWS Lightsail.

## Table of Contents

- [Pre-Deployment Checklist](#pre-deployment-checklist)
- [AWS Lightsail Deployment](#aws-lightsail-deployment)
- [Environment Configuration](#environment-configuration)
- [Database Management](#database-management)
- [Monitoring](#monitoring)
- [Rollback Procedures](#rollback-procedures)
- [Troubleshooting](#troubleshooting)

---

## Pre-Deployment Checklist

Before deploying to production, verify the following:

- [ ] All tests pass locally: `./vendor/bin/phpunit`
- [ ] Code reviewed and approved via Pull Request
- [ ] Database migrations tested locally
- [ ] Production `.env` file prepared with secure credentials
- [ ] AWS SES configured and email sending tested
- [ ] Backup of current production database created
- [ ] Deployment window scheduled (avoid event hours 10:00-18:00)
- [ ] Rollback plan prepared

---

## AWS Lightsail Deployment

JAWS is deployed on AWS Lightsail with a Bitnami LAMP stack.

### Prerequisites

- **AWS Lightsail instance running** (current: `16.52.222.15`)
- **SSH key file**: `LightsailDefaultKey-ca-central-1.pem`
- **SSH access** to the server
- **SFTP client** for file uploads
- **Apache/Bitnami stack** installed on Lightsail

### Initial Setup (One-Time Configuration)

These steps are only needed when setting up a new server.

#### 1. Configure Apache

Ensure Apache is configured to route requests to `public/index.php`.

**File:** `/opt/bitnami/apache/conf/vhosts/myapp-vhost.conf`

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /opt/bitnami/apache/logs/error_log
    CustomLog /opt/bitnami/apache/logs/access_log combined
</VirtualHost>
```

**Create .htaccess file:**

**File:** `/var/www/html/public/.htaccess`

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

Restart Apache:
```bash
sudo /opt/bitnami/ctlscript.sh restart apache
```

#### 2. Install Composer

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

#### 3. Create Database Directory

```bash
sudo mkdir -p /var/www/html/database
sudo chown bitnami:www-data /var/www/html/database
sudo chmod 775 /var/www/html/database
```

### Deployment Steps

Follow these steps for each deployment.

#### 1. Add SSH Key to Agent

```bash
ssh-add LightsailDefaultKey-ca-central-1.pem
```

On Windows, use PuTTY Pageant or Windows SSH Agent.

#### 2. Backup Production Database

**Always backup before deploying:**

```bash
ssh bitnami@16.52.222.15
cd /var/www/html/database
sudo cp jaws.db jaws.backup.$(date +%Y%m%d_%H%M%S).db
ls -lh jaws.backup.*
exit
```

Verify the backup was created successfully.

#### 3. Upload Files via SFTP

```bash
sftp bitnami@16.52.222.15
cd /./var/www/html

# Upload main entry point
put public/index.php

# Upload source code
put -r src

# Upload configuration
put -r config

# Upload dependencies (if changed)
put composer.json
put composer.lock

# Upload database migrations (if new migrations)
put -r database/migrations

bye
```

**Tip:** To upload specific files only:
```bash
put -r src/Domain/Service/SelectionService.php src/Domain/Service/
```

#### 4. Install/Update Dependencies

SSH into the server and install Composer dependencies:

```bash
ssh bitnami@16.52.222.15
cd /var/www/html

# Install dependencies (production mode)
composer install --no-dev --optimize-autoloader

# If composer.lock changed, this will update dependencies
# If not, it will verify existing dependencies
```

**Flags explained:**
- `--no-dev`: Excludes development dependencies (PHPUnit, Phinx, etc.)
- `--optimize-autoloader`: Generates optimized autoloader for production

#### 5. Run Database Migrations (if applicable)

If you have new migrations:

```bash
# Check migration status
vendor/bin/phinx status --environment=production

# Run pending migrations
vendor/bin/phinx migrate --environment=production

# Verify migrations applied
vendor/bin/phinx status --environment=production
```

**Important:** Test migrations locally first!

#### 6. Set File Permissions

```bash
# Set ownership for PHP files
sudo chgrp -R www-data src config public
sudo chmod -R 750 src config
sudo chmod 644 public/index.php
sudo chmod 644 public/.htaccess

# Set database permissions (if database uploaded)
sudo chgrp www-data database/jaws.db
sudo chmod 664 database/jaws.db
sudo chmod 775 database
```

**Why these permissions:**
- `750` for directories: Owner can read/write/execute, group can read/execute
- `644` for PHP files: Owner can read/write, group/others can read
- `664` for database: Owner and group can read/write (Apache needs write access)
- `775` for database directory: Apache needs to create journal files

#### 7. Verify Environment Configuration

Ensure `.env` file exists with production configuration:

```bash
cat /var/www/html/.env
```

If `.env` doesn't exist or needs updates, see [Environment Configuration](#environment-configuration) section.

#### 8. Restart Apache

```bash
sudo /opt/bitnami/ctlscript.sh restart apache
```

#### 9. Verify Deployment

Test the API endpoint:

```bash
curl https://your-domain.com/api/events
```

Expected response:
```json
{
  "success": true,
  "data": {
    "events": [...]
  }
}
```

If you get an error, check logs:
```bash
sudo tail -f /opt/bitnami/apache/logs/error_log
```

#### 10. Test Critical Functionality

- [ ] Login works
- [ ] Availability updates work
- [ ] Assignments retrieved correctly
- [ ] Email notifications send (test with admin account)
- [ ] Frontend loads correctly

---

## Environment Configuration

Production environment variables must be configured in `.env` file.

### Creating Production .env File

SSH into server:

```bash
ssh bitnami@16.52.222.15
cd /var/www/html
nano .env
```

Add the following configuration:

```bash
# Database
DB_PATH=/var/www/html/database/jaws.db

# JWT Authentication (REQUIRED - CHANGE THIS!)
JWT_SECRET=your-production-secret-key-at-least-32-characters-long-must-be-different-from-dev
JWT_EXPIRATION_MINUTES=60

# AWS SES (Email Service)
SES_REGION=ca-central-1
SES_SMTP_USERNAME=your_production_smtp_username
SES_SMTP_PASSWORD=your_production_smtp_password
EMAIL_FROM=noreply@nsc-sdc.ca
EMAIL_FROM_NAME="Nepean Sailing Club - Social Day Cruising"

# Application
APP_DEBUG=false
APP_ENV=production
APP_TIMEZONE=America/Toronto
APP_URL=https://your-domain.com

# CORS (adjust for production frontend domain)
CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com
CORS_ALLOWED_HEADERS=Content-Type,Authorization
```

Save and exit (Ctrl+X, Y, Enter).

### Security Considerations

**Critical Security Settings:**

1. **JWT_SECRET**:
   - Must be at least 32 characters
   - Must be different from development secret
   - Use a cryptographically secure random string
   - Generate with: `openssl rand -base64 32`

2. **APP_DEBUG**:
   - Must be `false` in production
   - When `true`, exposes sensitive error details

3. **Database Permissions**:
   - Database file must not be world-readable
   - Use `chmod 664` (owner + group only)

4. **File Permissions**:
   - PHP files should be `644` (not executable)
   - Directories should be `750` (not world-readable)
   - Never use `777` permissions

5. **Environment File**:
   - `.env` should not be in version control
   - Should be readable only by `bitnami` user and `www-data` group
   - Use `chmod 640 .env`

---

## Database Management

### Running Migrations in Production

Always backup before running migrations:

```bash
ssh bitnami@16.52.222.15
cd /var/www/html

# 1. Backup database
sudo cp database/jaws.db database/jaws.backup.$(date +%Y%m%d_%H%M%S).db

# 2. Check migration status
vendor/bin/phinx status --environment=production

# 3. Run migrations
vendor/bin/phinx migrate --environment=production

# 4. Verify
vendor/bin/phinx status --environment=production
```

If a migration fails, see [Rollback Procedures](#rollback-procedures).

### Backup Procedures

#### Automated Backups

Set up a daily backup cron job:

```bash
crontab -e
```

Add the following line:

```bash
# Daily backup at 2 AM
0 2 * * * /usr/bin/cp /var/www/html/database/jaws.db /var/www/html/database/backups/jaws.backup.$(date +\%Y\%m\%d).db
```

Create backups directory:

```bash
mkdir -p /var/www/html/database/backups
```

#### Manual Backups

**Before deployment:**
```bash
ssh bitnami@16.52.222.15
cd /var/www/html/database
sudo cp jaws.db jaws.backup.$(date +%Y%m%d_%H%M%S).db
```

**Download backup to local machine:**
```bash
sftp bitnami@16.52.222.15
cd var/www/html/database
get jaws.db
get jaws.backup.*
bye
```

#### Database Backup Strategy

**Retention Policy:**
- Daily backups: Keep for 7 days
- Weekly backups: Keep for 4 weeks
- Monthly backups: Keep for 6 months
- Pre-deployment backups: Keep indefinitely

**Cleanup old backups:**
```bash
# Delete backups older than 7 days
find /var/www/html/database/backups -name "jaws.backup.*.db" -mtime +7 -delete
```

### Restore Procedures

If you need to restore from a backup:

```bash
ssh bitnami@16.52.222.15
cd /var/www/html/database

# List available backups
ls -lh jaws.backup.*

# Restore from backup (replace YYYYMMDD_HHMMSS with actual backup timestamp)
sudo cp jaws.backup.YYYYMMDD_HHMMSS.db jaws.db

# Set permissions
sudo chgrp www-data jaws.db
sudo chmod 664 jaws.db

# Restart Apache
sudo /opt/bitnami/ctlscript.sh restart apache
```

### Querying the Production Database

**Read-only queries are safe:**

```bash
ssh bitnami@16.52.222.15
cd /var/www/html/database

# Query database
sqlite3 jaws.db "SELECT * FROM boats LIMIT 5;"
sqlite3 jaws.db "SELECT COUNT(*) FROM crews;"
sqlite3 jaws.db "SELECT event_id, event_date FROM events ORDER BY event_date;"
```

**Important:** Never run UPDATE/DELETE queries directly on production database. Use migrations instead.

---

## Monitoring

### Check Application Logs

**Apache Error Log:**
```bash
sudo tail -f /opt/bitnami/apache/logs/error_log
```

**Apache Access Log:**
```bash
sudo tail -f /opt/bitnami/apache/logs/access_log
```

**Filter for errors only:**
```bash
sudo grep -i error /opt/bitnami/apache/logs/error_log | tail -20
```

### Check Database Size

```bash
ls -lh /var/www/html/database/jaws.db
```

Monitor database growth over time to identify potential issues.

### Health Checks

**API Health Check:**
```bash
curl -s https://your-domain.com/api/events | jq '.success'
```

Expected output: `true`

**Database Connection Check:**
```bash
curl -s https://your-domain.com/api/events | grep -o '"success":[^,]*'
```

### Performance Monitoring

**Check Apache Process Count:**
```bash
ps aux | grep httpd | wc -l
```

**Check Memory Usage:**
```bash
free -h
```

**Check Disk Space:**
```bash
df -h /var/www/html
```

### Setting Up Alerts

Create a monitoring script that sends alerts when issues are detected:

**File:** `/home/bitnami/monitor.sh`

```bash
#!/bin/bash

# Check if API is responding
API_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/api/events)

if [ "$API_STATUS" != "200" ]; then
    echo "API is down! Status: $API_STATUS" | mail -s "JAWS API Alert" admin@example.com
fi

# Check disk space
DISK_USAGE=$(df -h /var/www/html | awk 'NR==2 {print $5}' | sed 's/%//')

if [ "$DISK_USAGE" -gt 80 ]; then
    echo "Disk usage is at ${DISK_USAGE}%!" | mail -s "JAWS Disk Alert" admin@example.com
fi
```

Add to cron (run every 15 minutes):
```bash
*/15 * * * * /home/bitnami/monitor.sh
```

---

## Rollback Procedures

If deployment fails or introduces critical bugs, follow these steps to rollback.

### Rollback Checklist

- [ ] Identify the issue (check logs, test endpoints)
- [ ] Determine rollback scope (code only, or code + database)
- [ ] Notify team/users if downtime is required
- [ ] Execute rollback procedures
- [ ] Verify system is working
- [ ] Document what went wrong

### Code Rollback

If the issue is in the code (not database):

```bash
ssh bitnami@16.52.222.15
cd /var/www/html

# Option 1: Checkout previous git commit (if using git on server)
git checkout <previous-commit-hash>

# Option 2: Re-upload previous version via SFTP
# (Upload previous src/, config/, public/index.php from local backup)

# Reinstall dependencies
composer install --no-dev --optimize-autoloader

# Restart Apache
sudo /opt/bitnami/ctlscript.sh restart apache
```

### Database Rollback

If a migration caused issues:

```bash
ssh bitnami@16.52.222.15
cd /var/www/html

# Option 1: Rollback last migration via Phinx
vendor/bin/phinx rollback --environment=production

# Option 2: Restore from backup
cd database
sudo cp jaws.backup.YYYYMMDD_HHMMSS.db jaws.db
sudo chgrp www-data jaws.db
sudo chmod 664 jaws.db

# Restart Apache
sudo /opt/bitnami/ctlscript.sh restart apache
```

### Full System Rollback

If both code and database need to be rolled back:

```bash
ssh bitnami@16.52.222.15
cd /var/www/html

# 1. Restore database
sudo cp database/jaws.backup.YYYYMMDD_HHMMSS.db database/jaws.db
sudo chgrp www-data database/jaws.db
sudo chmod 664 database/jaws.db

# 2. Restore code (via git or SFTP)
git checkout <previous-commit-hash>
# OR re-upload via SFTP

# 3. Reinstall dependencies
composer install --no-dev --optimize-autoloader

# 4. Restart Apache
sudo /opt/bitnami/ctlscript.sh restart apache

# 5. Verify
curl https://your-domain.com/api/events
```

### Post-Rollback

After rolling back:

1. **Verify** system is working correctly
2. **Document** what went wrong in deployment notes
3. **Fix** the issue in development environment
4. **Test** thoroughly before next deployment
5. **Update** deployment procedures if needed

---

## Troubleshooting

### Common Production Issues

#### Issue: "500 Internal Server Error"

**Possible Causes:**
- PHP syntax error
- Missing dependencies
- Database connection error
- File permission issues

**Solution:**

1. Check Apache error log:
   ```bash
   sudo tail -50 /opt/bitnami/apache/logs/error_log
   ```

2. Look for specific error message (syntax errors, file not found, etc.)

3. Verify file permissions:
   ```bash
   ls -la /var/www/html/src
   ls -la /var/www/html/database
   ```

4. Verify dependencies installed:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

#### Issue: "Database locked" error

**Cause:** Multiple processes trying to write to SQLite simultaneously

**Solution:**

1. Check for open connections:
   ```bash
   fuser /var/www/html/database/jaws.db
   ```

2. Restart Apache to clear connections:
   ```bash
   sudo /opt/bitnami/ctlscript.sh restart apache
   ```

3. If problem persists, verify WAL mode is enabled:
   ```bash
   sqlite3 /var/www/html/database/jaws.db "PRAGMA journal_mode;"
   ```
   Should return: `wal`

#### Issue: "Permission denied" when accessing database

**Cause:** Incorrect file permissions

**Solution:**

```bash
sudo chgrp www-data /var/www/html/database/jaws.db
sudo chmod 664 /var/www/html/database/jaws.db
sudo chgrp www-data /var/www/html/database
sudo chmod 775 /var/www/html/database
```

#### Issue: "JWT token invalid" after deployment

**Cause:** JWT_SECRET changed or token format changed

**Solution:**

1. Verify `JWT_SECRET` in `.env` hasn't changed
2. If it changed, all users need to login again to get new tokens
3. Clear any cached tokens on frontend

#### Issue: Email notifications not sending

**Possible Causes:**
- AWS SES credentials incorrect
- Email not verified in AWS SES
- SES in sandbox mode

**Solution:**

1. Check SES credentials in `.env`:
   ```bash
   cat /var/www/html/.env | grep SES
   ```

2. Test sending email via AWS CLI:
   ```bash
   aws ses send-email --from noreply@nsc-sdc.ca --to test@example.com --subject "Test" --text "Test"
   ```

3. Verify email address is verified in AWS SES console

4. Check Apache error log for AWS SES errors

#### Issue: Frontend not loading

**Possible Causes:**
- Apache configuration incorrect
- .htaccess not working
- File permissions

**Solution:**

1. Verify Apache virtual host configuration:
   ```bash
   sudo cat /opt/bitnami/apache/conf/vhosts/myapp-vhost.conf
   ```

2. Verify .htaccess exists:
   ```bash
   cat /var/www/html/public/.htaccess
   ```

3. Test Apache rewrite module:
   ```bash
   sudo apachectl -M | grep rewrite
   ```
   Should see: `rewrite_module (shared)`

4. Restart Apache:
   ```bash
   sudo /opt/bitnami/ctlscript.sh restart apache
   ```

---

## Next Steps

Now that you understand JAWS deployment:

✅ Deployment complete!
➡️ **Next:** Set up [monitoring alerts](#setting-up-alerts) for proactive issue detection

✅ Production running!
➡️ **Next:** Review [Database Management](../database/README.md) for ongoing maintenance

✅ Rollback plan ready!
➡️ **Next:** Document your deployment process in team wiki

---

📖 **Additional Resources:**

- [Setup Guide](SETUP.md) - Local development setup
- [Database README](../database/README.md) - Database management
- [API Reference](API.md) - API endpoint documentation
- [Developer Guide](DEVELOPER_GUIDE.md) - Architecture and patterns
