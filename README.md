# Clothes Shop - Docker Setup

A modern e-commerce application with a **Laravel REST API** backend and **Nuxt 3** frontend, fully containerized with Docker.

---

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Project Structure](#project-structure)
- [Services & Ports](#services--ports)
- [Development Guide](#development-guide)
- [Useful Commands](#useful-commands)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Project Overview

This is a complete e-commerce platform with:

- **Backend API**: RESTful API built with Laravel (PHP 8.4)
- **Frontend**: Modern SPA built with Nuxt 3 (Vue 3)
- **Database**: PostgreSQL for data storage
- **Cache**: Redis for sessions and caching
- **Containerization**: Docker & Docker Compose

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                                                               │
│  BROWSER (http://localhost:3000)                            │
│                                                               │
└────────────────────┬────────────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
        ▼                         ▼
┌──────────────────┐      ┌──────────────────┐
│  WEB (Nuxt 3)    │      │  API (Laravel)   │
│  Port 3000       │      │  Port 8000       │
│  Frontend        │      │  REST Endpoints  │
└──────────┬───────┘      └────────┬─────────┘
           │                       │
           │       ┌───────────────┼───────────────┐
           │       │               │               │
           ▼       ▼               ▼               ▼
        ┌─────────────┐    ┌──────────────┐  ┌──────────┐
        │ PostgreSQL  │    │ Redis Cache  │  │ Sessions │
        │ Database    │    │ Port 6379    │  │          │
        │ Port 5432   │    └──────────────┘  └──────────┘
        └─────────────┘
```

---

## 💻 Tech Stack

| Layer                | Technology                  | Version      |
| -------------------- | --------------------------- | ------------ |
| **Frontend**         | Nuxt 3, Vue 3, Tailwind CSS | Latest       |
| **Backend**          | Laravel 12, PHP             | 8.4          |
| **Database**         | PostgreSQL                  | 18-alpine    |
| **Cache**            | Redis                       | 8.4.0-alpine |
| **Web Server**       | Nginx                       | Latest       |
| **Containerization** | Docker & Docker Compose     | Latest       |

---

## 📋 Prerequisites

- **Docker**: [Install Docker](https://docs.docker.com/get-docker/)
- **Docker Compose**: Included with Docker Desktop
- **Git**: [Install Git](https://git-scm.com/)
- **Port Availability**: Ports 3000, 8000, 5432, 6379 must be free

---

## 🚀 Quick Start

### 1. Clone Repository

```bash
git clone https://github.com/yourusername/clothes_shop_docker.git
cd clothes_shop_docker
```

### 2. Start Services

```bash
# Start all containers
docker-compose up -d

# Check status
docker-compose ps
```

### 3. Access Application

- **Frontend**: http://localhost:3000
- **API**: http://localhost:8000
- **Database**: localhost:5432
- **Redis**: localhost:6379

---

## 📂 Project Structure

```
clothes_shop_docker/
├── api/                          # Laravel Backend
│   ├── Dockerfile               # PHP 8.4 + Nginx + PostgreSQL support
│   ├── docker-entrypoint.sh     # Startup script (migrations, permissions)
│   ├── nginx.conf               # Nginx config (port 8000)
│   ├── app/                     # Application code
│   │   ├── Http/Controllers/    # API Controllers
│   │   ├── Models/              # Database Models
│   │   └── Providers/           # Service Providers
│   ├── database/
│   │   ├── migrations/          # Database migrations
│   │   └── seeders/             # Database seeds
│   ├── routes/
│   │   └── api.php              # API routes
│   ├── .env                     # Environment variables
│   └── composer.json            # PHP dependencies
│
├── web/                          # Nuxt Frontend
│   ├── Dockerfile               # Node 20 + npm
│   ├── nuxt.config.ts           # Nuxt configuration
│   ├── app/                     # Application code
│   │   ├── app.vue              # Root component
│   │   ├── components/          # Vue components
│   │   ├── pages/               # Route pages
│   │   └── layouts/             # Layout templates
│   ├── package.json             # Node dependencies
│   └── tailwind.config.js        # Tailwind CSS config
│
├── docker-compose.yml           # Service orchestration
├── docker-compose.product.yml   # Production config
├── SETUP_NOTES.md              # Detailed setup documentation
└── README.md                    # This file
```

---

## 🔌 Services & Ports

### API (Backend)

```
🔗 URL: http://localhost:8000
🌐 Port: 8000 (external) → 8000 (internal Nginx)
          9000 (internal PHP-FPM, not exposed)
📦 Framework: Laravel 12
🐘 Language: PHP 8.4
🔗 Database: PostgreSQL 18
💾 Cache: Redis
```

**Endpoint Examples:**

```
GET  /api/products              # Get all products
POST /api/products              # Create product
GET  /api/products/{id}         # Get single product
PUT  /api/products/{id}         # Update product
DELETE /api/products/{id}       # Delete product
```

### Frontend (Client)

```
🔗 URL: http://localhost:3000
🌐 Port: 3000
📦 Framework: Nuxt 3
📘 Language: Vue 3 (JavaScript/TypeScript)
🎨 Styling: Tailwind CSS
```

### Database

```
🔗 Host: localhost
🔌 Port: 5432
🐘 Type: PostgreSQL 18
👤 User: admin
🔐 Password: securepassword123
💾 Database: clothes_shop
```

### Cache

```
🔗 Host: localhost
🔌 Port: 6379
🔴 Type: Redis 8.4.0
```

---

## 👨‍💻 Development Guide

### Backend Development (API)

#### View API Logs

```bash
docker-compose logs -f api
```

#### Run Artisan Commands

```bash
# Run migrations
docker-compose exec api php artisan migrate

# Seed database
docker-compose exec api php artisan db:seed

# Create new migration
docker-compose exec api php artisan make:migration create_products_table

# Create new model
docker-compose exec api php artisan make:model Product

# Create new controller
docker-compose exec api php artisan make:controller ProductController --resource
```

#### Access API Shell

```bash
# SSH into container
docker-compose exec api bash

# Run tinker (REPL)
php artisan tinker
```

#### API Structure

- **Routes**: `api/routes/api.php`
- **Controllers**: `api/app/Http/Controllers/`
- **Models**: `api/app/Models/`
- **Database**: PostgreSQL on port 5432

---

### Frontend Development (Web)

#### View Frontend Logs

```bash
docker-compose logs -f web
```

#### Hot Reload

Changes in `web/` folder automatically reload in browser (Vite dev server)

#### Build for Production

```bash
docker-compose exec web npm run build
```

#### Access Frontend Shell

```bash
docker-compose exec web sh
```

#### Frontend Structure

- **Pages**: `web/app/pages/` (auto-routing)
- **Components**: `web/app/components/`
- **Layouts**: `web/app/layouts/`
- **Stores**: `web/app/stores/` (Pinia state management)
- **API Calls**: `web/app/lib/` (composables/utilities)

---

## 🛠️ Useful Commands

### Container Management

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Stop and remove volumes (clean slate)
docker-compose down -v

# View running containers
docker-compose ps

# View all services
docker-compose config
```

### Logs

```bash
# View all logs
docker-compose logs

# Follow logs (real-time)
docker-compose logs -f

# View specific service logs
docker-compose logs api
docker-compose logs web
docker-compose logs db

# View last 100 lines
docker-compose logs --tail=100
```

### Building

```bash
# Build all images
docker-compose build

# Build specific service
docker-compose build api
docker-compose build web

# Build without cache (fresh)
docker-compose build --no-cache api
```

### Database

```bash
# Connect to PostgreSQL
docker-compose exec db psql -U admin -d clothes_shop

# Run migrations
docker-compose exec api php artisan migrate

# Rollback migrations
docker-compose exec api php artisan migrate:rollback

# Seed database
docker-compose exec api php artisan db:seed
```

### Redis

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# Check connection
docker-compose exec redis redis-cli ping

# View all keys
docker-compose exec redis redis-cli keys "*"

# Clear cache
docker-compose exec redis redis-cli FLUSHDB
```

---

## 📊 API Response Examples

### Get Products

```bash
curl http://localhost:8000/api/products
```

Response:

```json
{
  "data": [
    {
      "id": 1,
      "name": "T-Shirt",
      "price": 29.99,
      "description": "Comfortable cotton t-shirt"
    }
  ]
}
```

### Create Product

```bash
curl -X POST http://localhost:8000/api/products \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jeans",
    "price": 59.99,
    "description": "Blue denim jeans"
  }'
```

---

## 🐛 Troubleshooting

### Services Not Starting?

```bash
# Check logs
docker-compose logs

# Rebuild everything
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Database Connection Error?

```bash
# Check PostgreSQL
docker-compose logs db

# Test connection
docker-compose exec api nc -zv db 5432

# Run migrations manually
docker-compose exec api php artisan migrate
```

### Frontend Not Loading?

```bash
# Check Nuxt logs
docker-compose logs -f web

# Rebuild web
docker-compose build web --no-cache
docker-compose up -d web
```

### API Port Already in Use?

```bash
# Find what's using port 8000
lsof -i :8000

# Kill the process or change port in:
# 1. api/nginx.conf (listen X;)
# 2. docker-compose.yml (ports: - X:X)
```

### Redis Connection Issues?

```bash
# Test Redis
docker-compose exec redis redis-cli ping

# Should return: PONG
```

### Clear All Data (Start Fresh)

```bash
# Remove containers and volumes
docker-compose down -v

# Remove images
docker rmi clothes_shop_docker-api clothes_shop_docker-web

# Rebuild and start
docker-compose build --no-cache
docker-compose up -d
```

---

## 📝 Environment Variables

### API Configuration

Key variables in `api/.env` (use `.env.example` as template):

```env
APP_NAME="Clothes Shop"
APP_ENV=local                    # Change to 'production' for production
APP_DEBUG=true                   # Change to 'false' for production
APP_URL=https://127.0.0.1:8000

DB_CONNECTION=pgsql             # Database type
DB_HOST=db                       # Docker service name (or your host)
DB_PORT=5432                     # PostgreSQL port
DB_DATABASE=clothes_shop
DB_USERNAME=admin                # Set your username
DB_PASSWORD=***                  # Set your password (DO NOT commit)

CACHE_DRIVER=redis
REDIS_HOST=redis                 # Docker service name
REDIS_PORT=6379

SESSION_DRIVER=database
```

**⚠️ IMPORTANT:**

- Never commit `.env` with real passwords
- Use `.env.example` as template
- Each developer has their own `.env`
- `.env` is in `.gitignore`

---

## 🔄 Development Workflow

### 1. Make Changes

**Backend:**

```bash
# Edit api/app/Models/Product.php
# Or create new migration
docker-compose exec api php artisan make:migration add_fields_to_products
```

**Frontend:**

```bash
# Edit web/app/pages/products.vue
# Changes auto-reload in browser
```

### 2. Test Changes

```bash
# Check API logs
docker-compose logs -f api

# Check frontend logs
docker-compose logs -f web
```

### 3. Commit & Push

```bash
git add .
git commit -m "Add new feature"
git push origin main
```

---

## 🚀 Production Deployment

For production, use `docker-compose.product.yml`:

```bash
docker-compose -f docker-compose.product.yml up -d
```

Key differences:

- Environment set to `production`
- Debug disabled
- Optimizations enabled
- Security hardened

---

## 📚 Learning Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Nuxt 3 Guide](https://nuxt.com/)
- [Docker Documentation](https://docs.docker.com/)
- [PostgreSQL Docs](https://www.postgresql.org/docs/)
- [Redis Documentation](https://redis.io/documentation)

---

## 🤝 Contributing

1. Create feature branch: `git checkout -b feature/amazing-feature`
2. Make changes and test
3. Commit: `git commit -m 'Add amazing feature'`
4. Push: `git push origin feature/amazing-feature`
5. Open Pull Request

---

## 📜 License

This project is licensed under the MIT License.

---

## 📞 Support

For issues and questions:

- Check [SETUP_NOTES.md](SETUP_NOTES.md) for detailed troubleshooting
- Open GitHub Issues
- Review Docker logs: `docker-compose logs`

---

## ✅ Quick Checklist

- [ ] Docker installed
- [ ] Ports 3000, 8000, 5432, 6379 available
- [ ] Repository cloned
- [ ] `docker-compose up -d` executed
- [ ] All containers running: `docker-compose ps`
- [ ] Frontend accessible: http://localhost:3000
- [ ] API accessible: http://localhost:8000
- [ ] Database initialized
- [ ] Ready to develop! 🎉

---

**Happy coding! 🚀**

Last Updated: January 30, 2026
