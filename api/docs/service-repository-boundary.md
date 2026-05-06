# Service / Repository Boundary

This project currently uses both `Services` and `Repositories`, so the main goal is to keep their responsibilities clear.

## Rule Of Thumb

- `Repository` = database access, query building, filtering, pagination, caching, and cache invalidation.
- `Service` = business flow, orchestration, transactions, uploads, external APIs, and multi-step use cases.

If a service only forwards a call to a repository without adding logic, the method probably belongs in the repository instead.

## What Goes Where

### Repository

Use a repository when the code:

- reads from or writes to Eloquent models directly
- builds `where`, `join`, `with`, `orderBy`, `paginate`, or `get` queries
- handles cache keys, `Cache::remember`, or version-based invalidation
- returns raw models, collections, or paginated query results

Examples in this codebase:

- product listing cache and invalidation
- product variant list query caching
- category/brand/customer pagination if the repository owns the query

### Service

Use a service when the code:

- combines multiple repository calls
- uploads files to Cloudinary or other external services
- wraps multiple steps in a business flow
- applies conditional logic before saving
- coordinates transactions and side effects

Examples in this codebase:

- category create/update with image upload
- brand create/update with image upload
- customer update with avatar handling
- dashboard summary composition

## When Not To Cache

Do not add cache by default just because a method is a `list` or `index`.

Skip caching when:

- the table is small
- the data changes often
- the query is already cheap
- the result is security sensitive
- you cannot invalidate it reliably

For roles and permissions, this means:

- role list: usually no cache at first
- role permission list: usually no cache at first
- permission checks for auth middleware: cache the derived permission map if needed

## Practical Decision Table

- If the method is mostly SQL/query logic, put it in `Repository`.
- If the method is mostly business logic, put it in `Service`.
- If the method only calls one repository method, remove the service wrapper unless you expect more logic soon.
- If both query logic and business logic are mixed, split them.

## Suggested Pattern For New Modules

For a module like `Role` or `RolePermission`:

- `RoleRepository`: list, find, create, update, delete, cache if needed
- `RolePermissionRepository`: list permissions, update permission rows
- `RoleService`: enforce business rules like system-role protection and permission syncing

This keeps the code easier to scan and prevents services from becoming thin wrappers.
