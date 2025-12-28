# Playground

This directory contains sample applications for testing the pest-plugin-bridge with real-world scenarios.

## Structure

- `laravel-api/` - Laravel 12 backend with Sanctum SPA authentication
- `nuxt-app/` - Nuxt 3 frontend with authentication UI

## How It Works

pest-plugin-bridge provides **automatic server lifecycle management**:

1. **Laravel API** - Automatically started by pest-plugin-browser (in-process via amphp)
2. **Nuxt Frontend** - Automatically started by pest-plugin-bridge via `->serve()`
3. **API URL Injection** - The Laravel API URL is automatically injected into the Nuxt app via `NUXT_PUBLIC_API_BASE` environment variable

No manual server startup required!

## Setup (One-Time)

### Laravel API

```bash
cd playground/laravel-api
composer install
php artisan migrate --seed
```

Test credentials:
- Email: `test@example.com`
- Password: `password`

### Nuxt App

```bash
cd playground/nuxt-app
npm install
```

## Running Tests

Simply run the tests from the Laravel API directory - servers start automatically:

```bash
cd playground/laravel-api
./vendor/bin/pest tests/Browser
```

The test configuration in `playground/laravel-api/tests/Pest.php`:

```php
uses(TestCase::class, BridgeTrait::class)
    ->beforeAll(fn () => Bridge::setDefault('http://localhost:3000')
        ->serve('npm run dev', cwd: dirname(__DIR__, 2).'/nuxt-app')
        ->readyWhen('Local:.*localhost:3000'))
    ->in('Browser');
```

Cleanup is automatic - no `afterAll` needed.

## Test Scenarios

The `playground/laravel-api/tests/Browser/AuthTest.php` covers:

1. Home page shows login link when not authenticated
2. User can navigate to login page
3. Login page shows form elements
4. User can login with valid credentials
5. User sees error with invalid credentials
6. Authenticated user can access dashboard
7. User can logout from dashboard
8. Unauthenticated user is redirected from dashboard
