# Interview Notes: Role and Permission System

## One-Line Summary

I built a database-driven role and permission system where roles decide which admin modules and actions a user can access.

## Core Parts

- `roles` defines who the user is, such as `super_admin` or `admin`
- `modules` defines the app areas, such as `products`, `orders`, and `roles`
- `role_permissions` stores which actions a role can do in each module
- `users.role` stores the role slug for each admin user

## Main Flow

1. Admin logs in with email and password.
2. Backend finds the user role.
3. Backend loads the role permissions from the database.
4. Backend returns the permission matrix with the login response.
5. Frontend saves the matrix in `adminAuthStore`.
6. Sidebar, buttons, and route guards use that matrix to show or hide access.
7. API middleware still blocks forbidden requests on the backend.

## What I Implemented

- Role CRUD for list, create, update, and delete
- Permission matrix UI for module actions
- Admin CRUD with role assignment
- Login permission loading
- Backend permission middleware
- Frontend permission-based sidebar and page access
- Dynamic role fetching for the admin popup
- Permission cache for faster login and route checks
- Seeded default roles, modules, and permission rows
- Safe protection for system roles like `super_admin`
- Route-level and UI-level permission filtering

## Advanced Features

### 1. Permission Cache

- role permissions are cached after the first load
- this reduces repeated database queries on login and route checks
- cache is cleared when role permissions change

### 2. Middleware Protection

- frontend hiding is only for user experience
- backend middleware still checks every protected route
- if a user tries to call an unauthorized API directly, the request is blocked

### 3. Dynamic Role Loading

- admin forms fetch roles from the backend
- role options are not hardcoded in the UI
- new roles created in the database appear automatically in the dropdown

### 4. System Role Protection

- `super_admin` is treated as a full-access system role
- normal admins cannot assign `super_admin`
- system roles are blocked from unsafe edit and delete actions unless allowed

### 5. Seeded Access Control

- modules are seeded first
- roles are seeded next
- role permissions are seeded after modules exist
- this gives the app a working access-control setup from the first fresh install

### 6. Action-Based Control

- permissions are not only per module
- each module can allow specific actions like `view`, `create`, `edit`, and `delete`
- the sidebar and buttons use those actions to decide what to show

## Business Rules

- `super_admin` has full access to every module and action
- `admin` uses only the permissions assigned in the database
- `super_admin` is protected from normal restriction checks
- Regular admins cannot assign `super_admin` to other users

## Permission Storage

- `view`, `create`, `edit`, and `delete` are stored per module
- permissions are kept in the database, not hardcoded in the frontend
- the frontend only reads the matrix and applies it to the UI

## Short HR Answer

> I implemented role-based access control for the admin panel. Roles and permissions are stored in the database, the backend loads permissions at login, and the frontend uses them to control the sidebar, pages, and buttons. `super_admin` has full access, while `admin` follows the permissions assigned in the database.

## Short Technical Answer

> I use a role-to-module permission matrix. After login, the backend returns the role permissions, the frontend stores them in `adminAuthStore`, and middleware checks them again on protected routes and API actions.

## Why This Design Is Good

- easy to manage from the admin UI
- easy to seed and update
- secure because backend middleware still validates access
- flexible if new modules or roles are added later
- faster because common permission data can be cached
- safer because system roles are protected separately

## Important Lesson

- frontend hiding is not security by itself
- backend permission checks are still required
- system roles should be rare and carefully protected
- permission changes should update cache or the UI can show stale access
- dynamic roles should still be validated against the database
