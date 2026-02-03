# Contributing to Laravel Fibery

Thank you for considering contributing to Laravel Fibery!

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/YOUR_USERNAME/laravel-fibery.git`
3. Install dependencies: `composer install`
4. Create a branch: `git checkout -b feature/amazing-feature`

## Development Workflow

1. Write tests first (TDD encouraged)
2. Run tests: `composer test`
3. Check code style: `composer format`
4. Run static analysis: `composer analyse`
5. Commit with descriptive messages
6. Push and create PR

## Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
vendor/bin/pest tests/Unit/QueryBuilderTest.php
```

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code style:

```bash
# Check code style
composer format

# The CI will auto-fix style issues on push
```

## Static Analysis

We use PHPStan for static analysis:

```bash
composer analyse
```

## Pull Request Process

1. Update README.md if you're adding new features
2. Update CHANGELOG.md (add to "Unreleased" section)
3. Ensure all CI checks pass
4. Request review from maintainers

## Commit Message Guidelines

Use clear, descriptive commit messages:

- `feat: add GraphQL support`
- `fix: handle rate limit correctly`
- `docs: update query builder examples`
- `test: add EntityManager tests`
- `refactor: simplify QueryBuilder where clause handling`

## Code of Conduct

Be respectful and inclusive. We follow the [Contributor Covenant](https://www.contributor-covenant.org/).

## Questions?

Feel free to open an issue for questions or discussions.
