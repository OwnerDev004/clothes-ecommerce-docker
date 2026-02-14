# Interview Notes (Project Summary)

Use this as a short, clear script to explain what you built and how.

**Project Overview**
- Laravel API + Nuxt web app, Dockerized with Postgres and Redis.
- I implemented Redis caching for product listing filters and added catalog seeding for fast local testing.

**Redis Caching (Laravel)**
- Goal: speed up repeated product filter queries.
- Where: `api/app/Repositories/ProductRepository.php`
- How:
  - Build a stable cache key from normalized filters.
  - Use `Cache::remember($key, $ttl, fn() => $query->get())`.
  - TTL set to 300 seconds.
- Redis config:
  - `api/.env`: set `CACHE_STORE=redis` and `REDIS_CLIENT=phpredis`.
  - `api/config/cache.php`: default store uses `CACHE_STORE`.
  - `api/config/database.php`: Redis connections `default` and `cache` (DB 0 and DB 1).
- Verification:
  - `docker compose exec redis redis-cli -n 1 dbsize`
  - `docker compose exec redis redis-cli -n 1 keys '*'`

**Docker Setup**
- Services: `db` (Postgres), `redis`, `api` (Laravel), `web` (Nuxt).
- Redis container is exposed on `6379`.
- Redis hostname inside Docker network is `redis`.
- API uses `REDIS_HOST=redis`.
- File: `docker-compose.yml`

**PHP Redis Extension (phpredis)**
- The Redis client is configured to use the native extension.
- Dockerfile installs it via PECL:
  - `pecl install redis`
  - `docker-php-ext-enable redis`
- File: `api/Dockerfile`

**Database Seeding (Catalog Data)**
- Seeded categories, dress types, colors, sizes, products, and variants.
- File: `api/database/seeders/CatalogSeeder.php`
- Wired into main seeder:
  - `api/database/seeders/DatabaseSeeder.php`
- Run seed:
  - `docker compose exec api php artisan db:seed`

**How I Explain It (Interview Script)**
- I introduced Redis caching for product filters to reduce DB load and improve response time for repeated searches.
- I used stable cache keys derived from validated filters and kept TTL short to avoid stale data.
- I set up Redis via Docker and configured Laravel to use `phpredis` for performance.
- I added a catalog seeder so anyone can spin up the stack and test the API quickly.

**Common Questions and Short Answers**
- Why Redis? It’s fast in-memory caching; reduces DB queries for repeated filter requests.
- How do you avoid stale cache? I used TTL. For full correctness, I would also invalidate keys on product updates.
- Why phpredis vs predis? phpredis is native and faster; predis is easier but slower.

**Things to Mention If Asked**
- Cache key uses normalized filters and deterministic hashes.
- Redis cache DB uses `REDIS_CACHE_DB=1` by default, so check DB 1 when debugging.
- You can confirm the cache by inspecting Redis keys in the container.

