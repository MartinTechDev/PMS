# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PMSv1 is a fresh Laravel 12 application running on PHP 8.2+ with Vite 7, Tailwind CSS 4, and Pest for testing. It uses the Laravel 12 streamlined directory structure (no `app/Http/Kernel.php` or `app/Console/Kernel.php`).

## Commands

### Development
```bash
composer run dev          # Runs server, queue, logs (pail), and Vite concurrently
php artisan serve         # Laravel dev server only
npm run dev               # Vite dev server only
npm run build             # Production frontend build
```

### Testing
```bash
composer run test                        # Full test suite (clears config first)
php artisan test                         # Run all tests
php artisan test --filter=ExampleTest    # Run a specific test class
php artisan test tests/Feature/ExampleTest.php  # Run a specific test file
```

Tests use Pest (not PHPUnit style). Tests run against SQLite `:memory:` (configured in `phpunit.xml`).

### Code Formatting
```bash
vendor/bin/pint --dirty --format agent   # Format changed files (run before finalizing changes)
vendor/bin/pint --format agent           # Format all files
```

### Setup
```bash
composer run setup   # Full setup: install, env, key:generate, migrate, npm install/build
```

## Architecture

- **Laravel 12 structure**: Middleware, exceptions, and routing configured in `bootstrap/app.php`. Providers registered in `bootstrap/providers.php`.
- **Frontend**: Vite 7 with `laravel-vite-plugin`, Tailwind CSS 4 via `@tailwindcss/vite`.
- **Testing**: Pest framework. Feature tests in `tests/Feature/`, unit tests in `tests/Unit/`.
- **Models**: Use `casts()` method (not `$casts` property). See `app/Models/User.php` for conventions.
- **Database**: Default migrations for users, cache, and jobs tables. Uses SQLite for testing.

## Key Conventions

- Use `php artisan make:*` commands with `--no-interaction` to scaffold files.
- Create Form Request classes for validation (not inline in controllers).
- Prefer `Model::query()` over `DB::` facade.
- Use `config()` helper, never `env()` outside config files.
- New models should include factories and seeders.
- PHPDoc blocks over inline comments; use array shape type definitions where helpful.
- Enum keys should be TitleCase.
