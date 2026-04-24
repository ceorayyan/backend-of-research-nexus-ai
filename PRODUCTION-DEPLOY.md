# Production Deployment Guide

## 🚀 Quick Deploy to Laravel Cloud

### After Pushing to GitHub:

Laravel Cloud will automatically deploy. After deployment, run these commands in the Laravel Cloud admin panel:

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache && echo "✓ Production Deployed"
```

---

## 🔧 Initial Setup (First Time Only)

### 1. Set Environment Variables in Laravel Cloud Dashboard:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://backend-of-research-nexus-ai.free.laravel.cloud
FRONTEND_URL=http://localhost:3000,https://ai-research-nexus.vercel.app
```

### 2. Run Initial Setup Commands:

```bash
php artisan key:generate && php artisan migrate --force --seed && php artisan config:cache && php artisan route:cache && php artisan view:cache && echo "✓ Initial Setup Complete"
```

---

## 📋 After Every Code Push:

Run this in Laravel Cloud admin panel:

```bash
php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && echo "✓ Updated"
```

---

## ✅ Verify Deployment:

### Test Health Check:
```bash
curl https://backend-of-research-nexus-ai.free.laravel.cloud/api/health
```

**Expected response:**
```json
{
  "status": "ok",
  "timestamp": "2026-04-24T...",
  "app": "Research Nexus",
  "version": "1.0.0"
}
```

### Test CORS:
```bash
php artisan tinker --execute="print_r(config('cors.allowed_origins'));"
```

**Expected output:**
```
Array
(
    [0] => http://localhost:3000
    [1] => https://ai-research-nexus.vercel.app
)
```

---

## 🔍 Troubleshooting:

### 500 Error After Deploy:
```bash
php artisan optimize:clear && php artisan config:cache && php artisan route:cache
```

### CORS Not Working:
```bash
php artisan config:clear && php artisan config:cache
```

### Check Logs:
```bash
tail -50 storage/logs/laravel.log
```

---

## 🎯 Important Endpoints:

- **Root:** `https://backend-of-research-nexus-ai.free.laravel.cloud/`
- **Health:** `https://backend-of-research-nexus-ai.free.laravel.cloud/api/health`
- **Login:** `https://backend-of-research-nexus-ai.free.laravel.cloud/api/login`
- **Register:** `https://backend-of-research-nexus-ai.free.laravel.cloud/api/register`

---

## ⚠️ Never Run in Production:

- ❌ `php artisan key:generate` (unless first time)
- ❌ `php artisan migrate:fresh` (deletes all data)
- ❌ `cp .env.example .env` (overwrites config)

---

## 📝 Deployment Checklist:

- [ ] Code pushed to GitHub
- [ ] Laravel Cloud auto-deployed
- [ ] Run cache commands
- [ ] Test `/api/health` endpoint
- [ ] Verify CORS configuration
- [ ] Test login from Vercel frontend
- [ ] Check logs for errors

---

## 🔐 Security Notes:

- `APP_DEBUG=false` in production
- `APP_ENV=production`
- Strong `APP_KEY` generated
- HTTPS only
- CORS properly configured
- Database credentials secure
