# Changelog

All notable changes to `laravel-fibery` will be documented in this file.

## v1.2.0 — Error Handling Overhaul & Params Fix - 2026-03-03

### What's Changed

#### Bug Fixes

- **Fix `params: []` serialization** — `QueryBuilder::get()` now sends `params: {}` (JSON object) instead of `params: []` (JSON array) when no where clauses are used. Fibery's API rejects empty arrays for the params field.

#### New Features

- **Centralized HTTP in FiberyClient** — New `rawRequest()`, `rawDownload()`, and `getToken()` methods. All managers now route through FiberyClient instead of creating their own Guzzle clients.
- **New exception types** — `ConnectionException` (network/DNS failures) and `TimeoutException` (request timeouts) for granular error handling.
- **`RateLimitException` improvements** — Now accepts and stores response data; parses `Retry-After` header from 429 responses.
- **Better error messages** — Invalid JSON errors now include `json_last_error_msg()` detail.

#### Breaking Changes

- `FileManager::downloadTo()` return type changed from `bool` to `void` — now throws `FiberyException` on write failure instead of returning `false`.
- `DocumentManager::updateContent()` now throws `FiberyException` on invalid JSON response instead of silently returning `['success' => true]`.

#### Internal

- WebhookManager, DocumentManager, FileManager refactored to use FiberyClient for all HTTP — removes duplicate Guzzle clients and `Closure::bind` token access hacks.
- Consistent retry logic and error classification across all API endpoints.
- 90 tests (128 assertions), PHPStan level 5, Laravel Pint formatted.

## 1.1.1 - 2026-02-25

Fix empty args serialized as JSON array instead of object

When no arguments were passed to a command, PHP's json_encode would
serialize the empty array as `[]` (JSON array) instead of `{}` (JSON
object), causing Fibery API errors. Convert empty args to stdClass
to ensure correct `{}` serialization.

## [Unreleased]

## [1.1.1] - 2026-02-24

### Fixed

- Fixed empty `args` being serialized as JSON array `[]` instead of JSON object `{}` in API commands, which caused errors on commands with no arguments (e.g. `fibery.schema/query`)

## [1.1.0] - 2025-02-04

### Added

- Webhook API support via `WebhookManager` for receiving notifications when entities change
  
  - `create(string $url, string $type)` - Create a webhook for a type
  - `all()` - List all webhooks with their last 50 runs
  - `get(int $id)` - Get a webhook by ID
  - `delete(int $id)` - Delete a webhook
  - `getByType(string $type)` - Get webhooks filtered by type
  - `exists(int $id)` - Check if a webhook exists
  
- New `Fibery::webhooks()` accessor method for webhook operations
  

## [1.0.0] - 2024-XX-XX

### Added

- Initial release
- Fluent query builder with support for select, where, orderBy, limit, offset
- Where clause operators: =, !=, >, >=, <, <=, IN, NULL, NOT NULL
- Relation loading with `with()` and subqueries
- Aggregate functions (count, min, max, etc.)
- Entity CRUD operations (create, update, delete)
- Batch operations (createMany, updateMany, deleteMany)
- Collection operations (add, remove, set, clear)
- Schema API for workspace introspection
- Type API for database management
- Field API for field management
- File API for uploads/downloads
- Document API for rich text fields
- Automatic retry on rate limit (429) responses
- Laravel service provider with config publishing
- Facade for convenient access
- Artisan command `fibery:test` for connection testing
- Comprehensive exception hierarchy
