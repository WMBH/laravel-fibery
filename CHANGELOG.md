# Changelog

All notable changes to `laravel-fibery` will be documented in this file.

## [Unreleased]

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
