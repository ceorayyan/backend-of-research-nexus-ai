# Laravel Backend Development Server Starter
# This script starts the Laravel development server

Write-Host "Starting Laravel Backend..." -ForegroundColor Green

# Check if vendor directory exists
if (-not (Test-Path "vendor")) {
    Write-Host "Installing dependencies..." -ForegroundColor Yellow
    composer install
}

# Check if .env file exists
if (-not (Test-Path ".env")) {
    Write-Host "Creating .env file..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
    php artisan key:generate
}

# Check if database exists and run migrations
if (-not (Test-Path "database/database.sqlite")) {
    Write-Host "Creating database and running migrations..." -ForegroundColor Yellow
    New-Item -ItemType File -Path "database/database.sqlite" -Force | Out-Null
    php artisan migrate
}

Write-Host "Starting development server..." -ForegroundColor Green
Write-Host "Backend will be available at http://localhost:8000" -ForegroundColor Cyan
Write-Host "API endpoints available at http://localhost:8000/api" -ForegroundColor Cyan
Write-Host ""

php artisan serve
