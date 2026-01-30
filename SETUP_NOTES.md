# Docker Compose Setup - Complete Guide

## Overview

This document outlines the issues encountered and the solutions applied to get the Clothes Shop Docker application running successfully.

---

## Problems Encountered & Solutions

### 1. **Composer Install Failed - PHP Version Mismatch**

**Problem:**

```
failed to solve: process "/bin/sh -c composer install --no-dev --optimize-autoloader" did not complete successfully: exit code: 2
```

**Root Cause:**

- The `composer.lock` file contained dependencies requiring PHP 8.4+
- The Dockerfile was using `php:8.2-fpm`
- Mismatch between PHP version and locked dependencies

**Solution:**

- Upgraded PHP from 8.2 to 8.4 in [api/Dockerfile](api/Dockerfile)
  ```dockerfile
  FROM php:8.4-fpm  # Changed from php:8.2-fpm
  ```

---

### 2. **Database Port Mismatch**

**Problem:**

```
db [172.18.0.2] 3306 (mysql) : Connection refused
```

**Root Cause:**

- Database is PostgreSQL (port 5432), not MySQL (port 3306)
- The `docker-entrypoint.sh` was hardcoded to check MySQL port 3306
- Environment variables in `docker-compose.yml` had syntax errors

**Solution:**

**a) Fixed docker-entrypoint.sh:**

```bash
# Before:
until nc -z -v -w30 $DB_HOST 3306; do

# After:
DB_PORT=${DB_PORT:-5432}
until nc -z -v -w30 $DB_HOST $DB_PORT; do
```

**b) Fixed docker-compose.yml environment variables:**

```yaml
# Before:
environment:
  - DB_HOST:db          # Wrong syntax (colon instead of equals)
  - REDIS_HOST:redis

# After:
environment:
  - DB_HOST=db          # Correct syntax (equals sign)
  - DB_PORT=5432        # Added explicit PostgreSQL port
  - REDIS_HOST=redis
```

---

### 3. **Missing PostgreSQL Driver in PHP**

**Problem:**

```
Illuminate\Database\QueryException : could not find driver (Connection: pgsql, ...)
```

**Root Cause:**

- PHP didn't have the PostgreSQL driver (`pdo_pgsql`) installed
- The Dockerfile only installed `pdo_mysql`

**Solution:**

**a) Added libpq-dev package:**

```dockerfile
RUN apt-get update && apt-get install -y \
    ...
    libpq-dev  # Added this for PostgreSQL support
```

**b) Installed pdo_pgsql extension:**

```dockerfile
# Before:
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# After:
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip
```

---

### 4. **NPM Native Binding Error (Web Service)**

**Problem:**

```
ERROR Cannot find native binding. npm has a bug related to optional dependencies
Cannot find module './parser.linux-arm64-musl.node'
```

**Root Cause:**

- Local `node_modules` and `package-lock.json` had wrong architecture binaries
- Docker volume mount was syncing corrupted node_modules into container

**Solution:**

**a) Removed package-lock.json locally:**

```bash
rm /Users/longdy/projects/clothes_shop_docker/web/package-lock.json
```

**b) Updated web Dockerfile:**

```dockerfile
# Before:
COPY package.json package-lock.json ./
RUN npm install

# After:
COPY package.json ./
RUN rm -rf node_modules
RUN npm install --legacy-peer-deps
```

**c) Fixed docker-compose.yml volume mount:**

```yaml
# Before:
volumes:
  - ./web:/app              # This syncs corrupted node_modules

# After:
volumes:
  - ./web:/app
  - /app/node_modules       # Anonymous volume to keep container's node_modules
```

---

## Key Learnings

### Docker & Compose Best Practices

1. **Volume Mounts**: When syncing source code with volumes, exclude directories with platform-specific binaries:

   ```yaml
   volumes:
     - ./app:/app # Source code
     - /app/node_modules # Exclude node_modules (use container's version)
   ```

2. **Environment Variables**: Use `=` not `:` in docker-compose.yml:

   ```yaml
   # Wrong:
   environment:
     - DB_HOST:localhost

   # Correct:
   environment:
     - DB_HOST=localhost
   ```

3. **Database Drivers**: Always ensure PHP has the correct database extensions installed:

   ```dockerfile
   # For MySQL:
   RUN docker-php-ext-install pdo_mysql

   # For PostgreSQL:
   RUN docker-php-ext-install pdo_pgsql

   # For both:
   RUN docker-php-ext-install pdo_mysql pdo_pgsql
   ```

4. **Composer Dependency Locking**: Keep `composer.lock` compatible with your PHP version
   - If you change PHP versions, you may need to regenerate the lock file

### Dockerfile Optimization

1. **Clean up after installations**:

   ```dockerfile
   RUN apt-get update && apt-get install -y ... && apt-get clean && rm -rf /var/lib/apt/lists/*
   ```

2. **Use specific package versions** when possible to avoid breaking changes

3. **Remove unnecessary build artifacts**:
   ```dockerfile
   RUN rm -rf vendor/ node_modules/  # Before installing fresh
   ```

---

## Service Endpoints

Once all containers are running:

| Service               | URL                   | Port |
| --------------------- | --------------------- | ---- |
| Frontend (Nuxt)       | http://localhost:3000 | 3000 |
| API (Laravel)         | http://localhost:8000 | 8000 |
| Database (PostgreSQL) | localhost:5432        | 5432 |
| Cache (Redis)         | localhost:6379        | 6379 |

---

## Useful Commands

### View Service Status

```bash
docker-compose ps
```

### View Logs

```bash
# All services
docker-compose logs

# Specific service
docker-compose logs api
docker-compose logs web
docker-compose logs db

# Follow logs
docker-compose logs -f api
```

### Rebuild Services

```bash
# Rebuild all
docker-compose build --no-cache

# Rebuild specific service
docker-compose build api --no-cache
docker-compose build web --no-cache
```

### Stop & Start

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Stop and remove volumes
docker-compose down -v
```

### Execute Commands in Containers

```bash
# Run artisan commands
docker-compose exec api php artisan migrate
docker-compose exec api php artisan tinker

# Run shell in container
docker-compose exec api bash
docker-compose exec web sh
```

---

## Environment Configuration

### .env File (api/.env)

Key settings for the Laravel API:

- `DB_CONNECTION=pgsql` - Using PostgreSQL
- `DB_HOST=db` - Docker service name
- `DB_PORT=5432` - PostgreSQL port
- `CACHE_DRIVER=redis` - Using Redis for cache
- `SESSION_DRIVER=database` - Sessions in database

### docker-compose.yml

Key services:

- **api**: Laravel PHP-FPM application
- **web**: Nuxt 3 frontend
- **db**: PostgreSQL database
- **redis**: Cache/session storage

---

## Troubleshooting

### Services Not Starting?

1. Check if ports are in use:

   ```bash
   lsof -i :8000
   lsof -i :3000
   lsof -i :5432
   ```

2. View full logs:

   ```bash
   docker-compose logs
   ```

3. Rebuild everything:
   ```bash
   docker-compose down -v
   docker-compose build --no-cache
   docker-compose up -d
   ```

### Database Connection Issues?

1. Verify PostgreSQL is running:

   ```bash
   docker-compose logs db
   ```

2. Check environment variables in API container:

   ```bash
   docker-compose exec api env | grep DB_
   ```

3. Test connection manually:
   ```bash
   docker-compose exec api nc -zv db 5432
   ```

### Frontend Not Loading?

1. Check if Nuxt dev server is running:

   ```bash
   docker-compose logs web
   ```

2. Look for compilation errors in logs

3. Rebuild web image:
   ```bash
   docker-compose build web --no-cache
   docker-compose up -d web
   ```

---

## Files Modified

1. **api/Dockerfile**
   - Changed `php:8.2-fpm` → `php:8.4-fpm`
   - Added `libpq-dev` package
   - Added `pdo_pgsql` extension
   - Added clean npm install commands

2. **api/docker-entrypoint.sh**
   - Made database port configurable (default: 5432)
   - Changed from hardcoded MySQL port to dynamic port

3. **web/Dockerfile**
   - Removed `package-lock.json` from COPY
   - Added `npm install --legacy-peer-deps`
   - Cleaned up node_modules before installation

4. **docker-compose.yml**
   - Fixed environment variable syntax (`:` → `=`)
   - Added `DB_PORT` environment variable
   - Fixed API volume mount path
   - Added anonymous volume for web node_modules

---

## Quick Start

```bash
# Navigate to project directory
cd /Users/longdy/projects/clothes_shop_docker

# Start all services
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

Your application should now be fully operational! 🎉
