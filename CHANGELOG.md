# Changelog

All notable changes to `laravel-fibery` will be documented in this file.

## [1.2.0] - 2026-03-03

### Fixed

- Fixed `QueryBuilder::get()` sending `params: []` (JSON array) instead of `params: {}` (JSON object) when no where clauses are used, which caused Clojure spec errors on Fibery's API
- Fixed `DocumentManager::updateContent()` silently returning `['success' => true]` when the API returned invalid JSON — now throws `FiberyException`
- Fixed `FileManager::downloadTo()` silently returning `false` on disk write failure — now throws `FiberyException`
- Fixed rate limit retries ignoring the `Retry-After` response header — was hardcoded to 1ms
- Fixed invalid JSON error messages missing detail — now includes `json_last_error_msg()`

### Added

- `ConnectionException` for network/DNS failures
- `TimeoutException` (extends `ConnectionException`) for request timeouts
- `FiberyClient::rawRequest()` for non-command HTTP requests returning decoded JSON
- `FiberyClient::rawDownload()` for non-command HTTP requests returning raw response body
- `FiberyClient::getToken()` public accessor
- `RateLimitException` now accepts and preserves API response data via `getResponse()`
- `RateLimitException::getRetryAfter()` now returns the actual `Retry-After` header value

### Changed

- **Breaking:** `FileManager::downloadTo()` return type changed from `bool` to `void` — throws `FiberyException` on failure instead of returning `false`
- **Breaking:** `DocumentManager::updateContent()` now throws on invalid JSON response instead of silently succeeding
- `WebhookManager`, `DocumentManager`, `FileManager` refactored to use `FiberyClient` for all HTTP — removes duplicate Guzzle clients
- All managers now benefit from centralized retry logic and consistent error classification

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
