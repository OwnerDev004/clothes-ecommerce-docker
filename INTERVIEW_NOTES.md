# Interview Notes

## Project Summary

This project is a Dockerized clothes shop platform with a Laravel API and a Nuxt frontend.
It includes catalog management, product variants, categories, brands, customers, roles, permissions, and admin management.

## Tech Stack

- Backend: Laravel
- Frontend: Nuxt 3
- Database: PostgreSQL
- Cache: Redis
- Containerization: Docker
- Auth: JWT-based admin login

## High-Level Architecture

- Laravel handles API, business logic, validation, and access control
- Nuxt handles the admin UI and permission-based navigation
- PostgreSQL stores the business data
- Redis is used for cache
- Docker keeps the whole stack reproducible

## Core Backend Structure

### Controller

- Receives HTTP requests
- Validates input through Form Requests
- Calls repositories or services
- Returns API responses

### Repository

- Handles database queries
- Manages filtering, pagination, searching, and caching
- Keeps raw query logic out of controllers

### Service

- Handles business flow
- Combines multiple repository calls
- Applies business rules
- Handles uploads or multi-step operations

### Rule Of Thumb

- Repository = query logic
- Service = business logic

If a service only forwards one repository call, it is probably too thin.

## Product Filtering And Redis Cache

I added Redis caching for repeated product filter queries.

Why:

- product filtering can hit the database often
- repeated queries with the same filters can be served from cache
- it improves speed and reduces DB load

How:

- build a stable cache key from normalized filters
- use `Cache::remember`
- keep TTL short so stale data is limited

Important note:

- cache should be invalidated or refreshed when the underlying data changes
- caching is useful for expensive and repeated queries, not every list endpoint

## Docker Setup

The app runs in Docker with separate services:

- `db` for PostgreSQL
- `redis` for cache
- `api` for Laravel
- `web` for Nuxt

Benefits:

- easy setup for local development
- same environment for the whole team
- fewer "works on my machine" issues

## Database Seeding

I added seeders so the project can boot with useful starter data.

Seeded data includes:

- catalog data
- modules
- roles
- role permissions
- a default super admin account

Why this matters:

- the app works right after `migrate:fresh`
- the admin panel has data to test immediately
- role and permission behavior is visible from the start

## Admin Authentication

Admin login is JWT-based.

Flow:

1. Admin submits email and password
2. Backend validates the credentials
3. Backend returns the access token
4. Backend also returns the permission matrix for the role
5. Frontend stores the token, profile, and permission matrix

Why this is useful:

- the frontend can immediately hide inaccessible modules
- middleware still protects the backend
- permissions are loaded once at login

## Role And Permission System

### Core Idea

- `roles` defines who the user is
- `modules` defines what part of the app
- `role_permissions` defines what actions a role can do in each module

### Stored Data

- `users.role` stores the role slug, such as `admin` or `super_admin`
- `roles.slug` is the readable role identifier
- `role_permissions.permissions` stores actions like `view`, `create`, `edit`, `delete`

### Main Flow

1. Admin logs in
2. Backend loads the role permissions from the database
3. Backend returns the permission matrix
4. Frontend stores it in `adminAuthStore`
5. Sidebar and buttons use it to show only allowed actions
6. API middleware blocks forbidden requests

### Business Rules

- `super_admin` has full access
- normal `admin` roles follow database permissions
- `super_admin` is protected from normal restrictions
- regular admins cannot assign `super_admin`

### Why This Design Is Good

- flexible
- database-driven
- easy to update from UI
- secure because backend still checks permissions
- simple to explain in interviews

## Admin Management

I built admin CRUD so super admins or permitted admins can manage admin accounts.

Features:

- list admins
- create admins
- edit admins
- delete admins
- assign role from backend-loaded role options

Important detail:

- the admin role dropdown is dynamic
- it fetches roles from the backend
- it does not rely on a hardcoded frontend list

## Notifications, Queue, And Third-Party Integrations

I also implemented async notifications and live updates.

### What I Built

- queued newsletter welcome email
- real-time admin order alerts
- real-time customer order status alerts
- Telegram notifications for order events
- email notifications through Laravel mail

### Newsletter Queue Flow

1. User subscribes to the newsletter.
2. Backend creates or finds the subscriber record.
3. A queue job is dispatched after the database commit.
4. The job sends a welcome email through Laravel notification mail.
5. The subscriber record is updated with the sent date.

Why queue it:

- the HTTP request stays fast
- mail delivery happens in the background
- retries are easier if sending fails

### Real-Time Order Alert Flow

1. Order status changes or a new order is created.
2. Backend builds a payload with order details.
3. A broadcast event is fired on a private channel.
4. Frontend listens with Echo and Pusher.
5. Admin or customer sees the alert without refreshing the page.

Why this helps:

- the dashboard feels live
- admins can react immediately
- customers get instant status updates

### Telegram Integration

- order updates can also go to Telegram
- the service calls the Telegram Bot API through HTTP
- it supports both admin and customer messages
- it has fallback handling if delivery fails

### Third-Party Services

- SMTP for email delivery
- Pusher for broadcasting
- Telegram Bot API for chat notifications
- Redis for queue and cache

### Why This Design Is Good

- background work does not block the request
- live updates improve the admin experience
- external integrations stay inside services, not controllers
- queue jobs make notifications more reliable

### Notification Interview Answer

> I implemented queued notifications and third-party delivery for better performance. Newsletter signup dispatches a background job that sends a welcome email, and order updates are broadcast in real time with Pusher. I also integrated Telegram for order notifications, so the system can notify users through multiple channels without blocking the main request.

## Frontend Permission Handling

The frontend stores admin permission data in `adminAuthStore`.

It is used for:

- sidebar visibility
- button visibility
- page access decisions
- route-based permission checks

Important principle:

- hiding UI is not security by itself
- backend middleware is still required

## Middleware Protection

I added backend permission middleware so API routes are checked on the server.

This matters because:

- a user can still call APIs directly
- frontend hidden buttons do not stop direct requests
- the backend must be the final authority

## Advanced Features

### Permission Cache

- role permissions are cached after first load
- reduces repeated queries on login and route checks
- cache is cleared when permissions are updated

### Dynamic Role Loading

- admin forms fetch role options from backend
- new roles appear automatically
- no frontend code change is needed for new roles

### System Role Protection

- `super_admin` is treated as a full-access system role
- system roles are protected from unsafe changes
- normal admins cannot manage `super_admin`

### Action-Based Access

- permissions are split by module and action
- actions include `view`, `create`, `edit`, and `delete`
- the UI can show or hide features very precisely

## Service / Repository Boundary

### Repository

- database access
- query building
- pagination
- search filters
- caching

### Service

- business flow
- transactions
- uploads
- side effects
- rule enforcement

### Good Pattern

- keep SQL in repositories
- keep workflows in services
- do not create empty service wrappers

## Real-World Lessons

- use database-driven permissions when roles are dynamic
- keep `super_admin` as the only real system override
- do not trust frontend hiding as security
- use cache carefully and invalidate it when data changes
- keep the permission model simple enough to maintain
- queue notifications for non-blocking delivery
- wrap third-party APIs in services so they are easy to replace

## How To Explain It In An Interview

### 20-Second Answer

> I built a role-based access control system for the admin panel. Roles, modules, and permissions are stored in the database. After login, the backend returns the permission matrix, the frontend stores it, and middleware protects API routes.

### 1-Minute Answer

> I implemented a database-driven role and permission system. Roles are assigned to admin users, permissions are stored per module with actions like view, create, edit, and delete, and the permission matrix is loaded at login. The frontend uses that matrix to control the sidebar and buttons, while backend middleware still blocks unauthorized requests. `super_admin` has full access, and normal admin roles follow the permissions saved in the database.

### Technical Answer

> The system uses `roles`, `modules`, and `role_permissions`. The backend loads the role permission matrix on login, caches it for performance, and the frontend saves it in `adminAuthStore`. UI visibility is permission-based, and API middleware enforces the same rules server-side.

### Notification / Queue Answer

> I use queue jobs for background email delivery and broadcasting for real-time updates. Newsletter signup dispatches a job after the database commit, the job sends the welcome email, and order events are broadcast over private channels so the admin dashboard updates live. For external chat delivery, I also use the Telegram Bot API through a dedicated service.

## Technical Interview Answers

If the interviewer is technical, focus on structure, tradeoffs, and why you chose each part.

### 1. Why use roles and permissions in the database?

> Because the system is dynamic. Admins can create roles and assign module permissions without changing code. That is easier to maintain than hardcoding access rules in the frontend.

### 2. Why keep both frontend checks and backend middleware?

> Frontend checks are for user experience, but backend middleware is the real security layer. If someone sends a direct API request, the server still blocks unauthorized actions.

### 3. Why store permissions as a module-action matrix?

> Because the UI is module-based and actions are fixed, like view, create, edit, and delete. That keeps the schema simple and easy to render in the permission matrix screen.

### 4. Why cache role permissions?

> Permission lookup happens often during login and route checks. Caching reduces database queries and speeds up authorization, while cache invalidation keeps it accurate after updates.

### 5. Why use a service for role permission syncing?

> Syncing permissions is a business workflow, not just a database write. The service can protect system roles, validate the payload, and coordinate multiple updates safely.

### 6. Why use a queue for notifications?

> Email and external delivery can be slow or fail temporarily. Queueing keeps the request fast, moves the work to the background, and gives retry support.

### 7. Why use Pusher or broadcasting?

> Broadcasting lets the admin dashboard receive live updates without polling or manual refresh. That gives a better real-time experience for order alerts.

### 8. Why use Telegram as a third-party channel?

> It gives another fast notification path for important order events. The integration is isolated in a service, so it stays easy to change or replace later.

### 9. What is the main tradeoff in this design?

> The system becomes more complex than hardcoded checks, but it is much more flexible, scalable, and maintainable for a real admin panel.

### 10. What should I say if they ask about security?

> I treat the frontend as presentation only. The backend is the source of truth, and middleware enforces permissions on every protected route.

## Short Talking Points

- Laravel API + Nuxt frontend
- PostgreSQL + Redis
- Dockerized dev environment
- JWT admin auth
- role-based access control
- dynamic admin role assignment
- permission matrix per module
- backend middleware for security
- Redis cache for repeated product list filters
- queued emails and notifications
- real-time order alerts
- Telegram integration

## Technical Summary

This project uses a layered architecture:

- controllers handle requests
- repositories handle database work
- services handle business rules
- middleware protects APIs
- frontend stores and uses permissions for UI state
- queues and broadcasting handle asynchronous and live communication

That is the short technical story of the project.

## Best Final Summary

This project is a full-stack admin system with database-driven roles and permissions, cached product queries, Dockerized services, and a permission-aware frontend. The architecture keeps business rules in services, queries in repositories, and security enforced on both frontend and backend.

## Flow Process Answer

### Simple Flow

> The user sends a request from the frontend, Laravel validates it, the repository handles the database query, and the service applies business rules if needed. For admin access, the backend checks the role permissions, returns the permission matrix on login, and the frontend uses it to show only what the user is allowed to see. The middleware still protects the API so unauthorized requests are blocked on the server.

### More Advanced Flow

> First, the admin logs in and the backend loads the role from the `users.role` field. Then it looks up the permissions for that role from `role_permissions` and returns a permission matrix with the login response. The frontend stores that matrix in `adminAuthStore`, so the sidebar, buttons, and pages can render based on permission. At the same time, backend middleware checks every protected route again, so even if someone bypasses the UI, the API still rejects forbidden actions. For performance, the permission matrix can be cached, and the cache is refreshed when role permissions change.

### If Asked About Cache Flow

> I use cache only where it helps, like repeated product listing queries or permission lookup. The main idea is to reduce repeated database work while keeping the result fresh enough for the business. When permissions change, I clear the cache so the next login or route check gets the updated data.

### If Asked About Security Flow

> Security is handled in two layers. The frontend hides unavailable actions for better user experience, but the backend middleware is the real enforcement layer. That way, users cannot access a module just by typing the URL or sending a manual request.
