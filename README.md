# Pest Bridge Plugin

[![Tests](https://github.com/TestFlowLabs/pest-plugin-bridge/actions/workflows/tests.yml/badge.svg)](https://github.com/TestFlowLabs/pest-plugin-bridge/actions)
[![License](https://img.shields.io/github/license/TestFlowLabs/pest-plugin-bridge)](LICENSE)

Test external frontends from Laravel — write browser tests in PHP for Vue, React, Nuxt, Next.js.

**[Documentation](https://pest-plugin-bridge.testflowlabs.dev)** | **[Getting Started](https://pest-plugin-bridge.testflowlabs.dev/getting-started/quick-start)**

This plugin extends [Pest's browser testing](https://pestphp.com/docs/browser-testing) capabilities to work with **external/detached frontend applications** - such as a Vue, React, or Angular app running on a separate server.

## Installation

```bash
composer require testflowlabs/pest-plugin-bridge --dev
```

> **Note:** This plugin requires [pestphp/pest-plugin-browser](https://github.com/pestphp/pest-plugin-browser) to be installed.

## Configuration

Configure in your `tests/Pest.php`:

```php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:5173');
```

## Usage

Use the `bridge()` method to visit pages on your external frontend:

```php
<?php

test('user can login', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'secret')
        ->click('[data-testid="login-button"]')
        ->assertUrlContains('/dashboard');
});

test('dashboard shows welcome message', function () {
    $this->bridge('/dashboard')
        ->assertSee('Welcome');
});
```

The `bridge($path)` method:
- Prepends the configured external URL to the given path
- Returns the same browser page object as Pest's `visit()` method
- Supports all standard browser testing methods (`fill()`, `click()`, `assertSee()`, etc.)

## Example: Testing a Vue/Nuxt Frontend with Laravel Backend

```php
<?php

// tests/Pest.php
use TestFlowLabs\PestPluginBridge\Bridge;

// Your Vue/Nuxt app runs on port 3000
Bridge::setDefault('http://localhost:3000');

// NOTE: Don't use RefreshDatabase with external server tests
pest()->extends(TestCase::class)->in('Browser');

// tests/Browser/RegisterTest.php
test('user can register', function () {
    $email = 'test'.time().'@example.com';

    $this->bridge('/register')
        ->waitForEvent('networkidle')
        ->click('input#name')
        ->typeSlowly('input#name', 'TestUser', 30)
        ->typeSlowly('input#email', $email, 20)
        ->typeSlowly('input#password', 'password123', 20)
        ->typeSlowly('input#password_confirmation', 'password123', 20)
        ->click('button[type="submit"]')
        ->waitForEvent('networkidle')
        ->assertPathContains('/dashboard')
        ->assertSee('Welcome');
});
```

## Vue/Nuxt Best Practices

When testing Vue or Nuxt frontends, follow these guidelines:

### Use `typeSlowly()` Instead of `fill()`

Vue's `v-model` doesn't sync with Playwright's `fill()` method because `fill()` sets the DOM value directly without firing `input` events. Use `typeSlowly()` (which uses Playwright's `pressSequentially()`) to trigger proper keyboard events:

```php
// Won't work with Vue v-model
->fill('input#email', 'test@example.com')

// Works correctly - triggers Vue reactivity
->typeSlowly('input#email', 'test@example.com', 20)
```

### Click First Input Before Typing

Prevent character loss by clicking the first input field:

```php
$this->bridge('/register')
    ->waitForEvent('networkidle')
    ->click('input#name')           // Focus first
    ->typeSlowly('input#name', 'User', 30)
```

### Wait for Network Idle After Form Submit

```php
->click('button[type="submit"]')
->waitForEvent('networkidle')  // Wait for API call
->assertPathContains('/dashboard')
```

### Don't Use RefreshDatabase

The `RefreshDatabase` trait's transaction isolation doesn't work with external servers:

```php
// tests/Pest.php

// Don't use RefreshDatabase for browser tests
pest()->extends(TestCase::class)->in('Browser');
```

### Verify Results Via UI

Since database queries won't see external server changes, use UI assertions:

```php
// Instead of database checks
->assertPathContains('/dashboard')
->assertSee('Welcome')
```

## Multiple Frontends

Configure multiple frontends in `tests/Pest.php`:

```php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:3000');           // Default frontend
Bridge::frontend('admin', 'http://localhost:3001');    // Admin panel
Bridge::frontend('mobile', 'http://localhost:3002');   // Mobile app
```

Then use them in your tests:

```php
<?php

test('customer can view products', function () {
    // Uses default frontend (localhost:3000)
    $this->bridge('/products')->assertSee('Product Catalog');
});

test('admin can manage users', function () {
    // Uses admin frontend (localhost:3001)
    $this->bridge('/users', 'admin')->assertSee('User Management');
});

test('mobile app shows dashboard', function () {
    // Uses mobile frontend (localhost:3002)
    $this->bridge('/dashboard', 'mobile')->assertSee('Mobile Dashboard');
});
```

## API Reference

### Bridge Class

| Method | Description |
|--------|-------------|
| `Bridge::setDefault(string $url)` | Set the default frontend URL |
| `Bridge::frontend(string $name, string $url)` | Add a named frontend |
| `Bridge::url(?string $name = null): string` | Get URL for a frontend |
| `Bridge::has(?string $name = null): bool` | Check if frontend is configured |
| `Bridge::buildUrl(string $path, ?string $frontend): string` | Build full URL |
| `Bridge::reset()` | Reset all configuration |

### BridgeTrait Methods

| Method | Description |
|--------|-------------|
| `$this->bridge(string $path = '/', ?string $frontend = null)` | Visit a page on an external frontend |

## Troubleshooting

### Form Values Not Submitting (Vue/Nuxt)
**Problem:** Form appears filled but submits empty values.

**Solution:** Use `typeSlowly()` instead of `fill()`. Vue's v-model doesn't sync with Playwright's direct DOM value setting.

### First Characters Lost When Typing
**Problem:** Typing "TestUser" results in "User" or similar.

**Solution:** Add `->click('input#field')` before the first `typeSlowly()` call.

### Database Assertions Fail But UI Shows Success
**Problem:** `User::where(...)->exists()` returns false even though registration succeeded.

**Solution:** Don't use `RefreshDatabase` trait. The transaction isolation prevents seeing external server changes. Use UI assertions instead.

### CSRF Token Mismatch
**Problem:** API returns 419 CSRF token mismatch error.

**Solution:** For token-based API auth, remove `$middleware->statefulApi()` from `bootstrap/app.php`.

### Test Hangs/Timeouts
**Problem:** Tests hang indefinitely.

**Solution:**
- Ensure frontend server is running
- Use `waitForEvent('networkidle')` instead of fixed `wait()` calls
- Check browser console for JavaScript errors

## Automatic Frontend Server Management

The plugin can automatically start and stop frontend servers during test execution:

```php
<?php
// tests/Pest.php

use TestFlowLabs\PestPluginBridge\Bridge;

// Automatically start Nuxt frontend before tests, stop after
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('Local:.*http');  // Custom ready pattern (optional)

// Multiple frontends with auto-start
Bridge::frontend('admin', 'http://localhost:3001')
    ->serve('npm run dev', cwd: '../admin-panel');
```

### How It Works

1. **Lazy Start**: Frontend servers start on the first `bridge()` call
2. **API URL Injection**: Automatically injects the Laravel API URL via environment variables:
   - `API_URL`, `VITE_API_URL`, `NUXT_PUBLIC_API_BASE`, `NEXT_PUBLIC_API_URL`, `REACT_APP_API_URL`
3. **Ready Detection**: Waits for server output to match the ready pattern before continuing
4. **Auto Stop**: Servers are automatically stopped when tests complete

### FrontendDefinition API

| Method | Description |
|--------|-------------|
| `->serve(string $command, ?string $cwd = null)` | Set the command to start the server |
| `->readyWhen(string $pattern)` | Custom regex pattern to detect server ready (default: `ready\|localhost\|started\|listening`) |

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run individual checks
./vendor/bin/pint --test    # Lint
./vendor/bin/phpstan        # Static analysis
./vendor/bin/pest           # Tests
```

## License

MIT License. See [LICENSE](LICENSE) for details.
