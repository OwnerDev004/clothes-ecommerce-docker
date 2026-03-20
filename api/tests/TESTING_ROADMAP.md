# Laravel API Testing Roadmap

This roadmap is designed for your current project structure (`api/tests/Feature`, admin/customer API routes, and JWT guards).

## Goal

Build confidence writing tests that verify:

- authentication/authorization
- validation rules
- business behavior
- database side effects

## How To Practice (Every Session)

1. Pick one endpoint.
2. Write behavior in one sentence.
3. Add one failing test.
4. Make it pass with minimal code.
5. Refactor test names/setup.

Run focused tests while learning:

```bash
cd api
php artisan test tests/Feature/AdminCollectionApiTest.php
```

Run all tests:

```bash
cd api
php artisan test
```

## Week 1: Core Feature Testing

Target file: `tests/Feature/AdminCollectionApiTest.php`

Practice:

- Auth required (401)
- Happy path list/show/create/update/delete
- DB assertions with pivot table (`collection_product`)

Add these new tests:

- `test_create_collection_requires_name_and_category_id`
- `test_create_collection_rejects_duplicate_name`
- `test_update_collection_rejects_invalid_product_ids`
- `test_show_collection_returns_404_for_missing_id`

What to master:

- `actingAs($user, 'admin')`
- `getJson/postJson/putJson/deleteJson`
- `assertStatus`, `assertJsonPath`, `assertDatabaseHas`

## Week 2: Validation + Authorization Matrix

Target module: admin brands or vouchers endpoints.

For each endpoint, test 3 dimensions:

- unauthenticated request -> `401`
- invalid payload -> `422`
- valid payload -> success (`200`/`201`)

Create a checklist per endpoint:

- status code correct
- message and data structure correct
- DB changed (or unchanged) correctly

## Week 3: Business Rules / Flow Tests

Target file: `tests/Feature/CommerceFlowTest.php`

Add scenarios:

- invalid voucher (expired/inactive/min amount not met)
- payment webhook signature invalid
- cancel order forbidden on wrong state

What to master:

- multi-step tests (cart -> checkout -> payment -> order state)
- strong assertions on state transitions

## Week 4: Test Quality Upgrade

Refactor for readability and speed:

- move repeated setup into helper methods
- use data providers for repeated validation cases
- keep test names behavior-focused (`test_xxx_when_yyy`)

Add confidence checks:

- random order independence (tests must pass in any order)
- no hidden dependency on existing DB records

## Naming Pattern

Use this naming template:

- `test_{actor}_can_{action}_{context}`
- `test_{action}_fails_when_{condition}`

Examples:

- `test_admin_can_create_collection_with_products`
- `test_create_collection_fails_when_name_is_missing`

## Assertion Cheat Sheet

- HTTP:
  - `assertOk()` -> 200
  - `assertStatus(201)` -> created
  - `assertUnauthorized()` -> 401
  - `assertUnprocessable()` -> 422
- JSON:
  - `assertJsonPath('data.name', 'Back To School')`
  - `assertJsonValidationErrors(['name', 'category_id'])`
- Database:
  - `assertDatabaseHas('collections', [...])`
  - `assertDatabaseMissing('collection_product', [...])`

## Your Next 5 Exercises (Concrete)

1. Add a test for `POST /api/v1/admin/collections` missing `name`.
2. Add a test for duplicate collection name returning `422`.
3. Add a test for invalid `category_id` not existing.
4. Add a test for `PUT /api/v1/admin/collections/{id}` with `product_ids` containing non-existing product.
5. Add a test for `DELETE /api/v1/admin/collections/{id}` unauthenticated returns `401`.

## Review Rule

Every test should verify at least one of:

- response contract
- domain behavior
- persistence side effects

If a test only checks `200`, improve it with JSON + DB assertions.
