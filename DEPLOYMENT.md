# Laravel Breeze React - Deployment Guide

## Pre-Deployment Checklist

Before deploying, ensure:
- [ ] Database is set up on production server
- [ ] Domain/server is configured
- [ ] SSH access to server
- [ ] Git repository is pushed to GitHub/GitLab

## Deployment Commands (Run on Server)

### 1. Clone Repository
```bash
git clone https://github.com/rayyan/YOUR_REPO_NAME.git
cd YOUR_REPO_NAME
```

### 2. Install PHP Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Install Node Dependencies & Build Assets
```bash
npm install
npm run build
```

### 4. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Configure .env File
Edit `.env` with production settings:
```env
APP_NAME="Your App Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (example for MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Mail (for password reset emails)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### 6. Run Database Migrations
```bash
php artisan migrate --force
```

### 7. Set Permissions
```bash
# Storage and cache directories must be writable
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 8. Optimize for Production
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 9. Link Storage (if using file uploads)
```bash
php artisan storage:link
```

## Quick Deployment Script

Create a file `deploy.sh`:

```bash
#!/bin/bash

echo "🚀 Starting deployment..."

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 775 storage bootstrap/cache

echo "✅ Deployment complete!"
```

Make it executable:
```bash
chmod +x deploy.sh
```

Run it:
```bash
./deploy.sh
```

## For Subsequent Deployments

After initial setup, for updates run:

```bash
# 1. Pull latest code
git pull origin main

# 2. Update dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 3. Run new migrations (if any)
php artisan migrate --force

# 4. Clear and recache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart queue workers (if using queues)
php artisan queue:restart
```

## Common Deployment Platforms

### Deploying to Shared Hosting (cPanel)

1. Upload files via FTP/File Manager
2. Run commands via Terminal in cPanel
3. Point domain to `/public` directory
4. Set PHP version to 8.2+

### Deploying to VPS (DigitalOcean, AWS, etc.)

1. Install LEMP/LAMP stack
2. Configure Nginx/Apache to point to `/public`
3. Install Supervisor for queue workers
4. Set up SSL with Let's Encrypt

**Nginx Configuration Example:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/your-app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Deploying to Laravel Forge

1. Connect your server to Forge
2. Create new site pointing to your repository
3. Set deployment script:
```bash
cd /home/forge/yourdomain.com
git pull origin main
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```
4. Enable Quick Deploy for automatic deployments

### Deploying to Heroku

1. Create `Procfile`:
```
web: vendor/bin/heroku-php-apache2 public/
```

2. Deploy:
```bash
heroku create your-app-name
heroku addons:create heroku-postgresql:mini
git push heroku main
heroku run php artisan migrate --force
heroku run php artisan key:generate
```

## Environment Variables to Set

**Required:**
- `APP_KEY` (generated by `php artisan key:generate`)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` (your domain)
- Database credentials
- Mail credentials (for password reset)

**Optional but Recommended:**
- `SESSION_DRIVER=database` (more reliable than file)
- `CACHE_DRIVER=redis` (if available)
- `QUEUE_CONNECTION=redis` (if using queues)

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secured
- [ ] `.env` file not in git (check `.gitignore`)
- [ ] HTTPS/SSL enabled
- [ ] File permissions set correctly (775 for storage)
- [ ] Disable directory listing
- [ ] Keep Laravel and dependencies updated

## Troubleshooting

### 500 Error
```bash
# Check logs
tail -f storage/logs/laravel.log

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Assets Not Loading
```bash
# Rebuild assets
npm run build

# Check public/build directory exists
ls -la public/build
```

### Database Connection Error
- Verify `.env` database credentials
- Check database server is running
- Ensure database user has proper permissions

### Permission Errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Monitoring & Maintenance

### Regular Tasks
```bash
# Clear old sessions (run weekly)
php artisan session:gc

# Clear expired password reset tokens
php artisan auth:clear-resets

# Optimize (run after updates)
php artisan optimize
```

### Backup Database
```bash
# MySQL example
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

---

## Quick Reference: Essential Commands

**Initial Deployment:**
```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

**Update Deployment:**
```bash
git pull origin main
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Clear Everything (troubleshooting):**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

---

**Need help with a specific platform? Let me know!**
