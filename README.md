# Pest Plugin Bridge

[![Tests](https://github.com/TestFlowLabs/pest-plugin-bridge/actions/workflows/tests.yml/badge.svg)](https://github.com/TestFlowLabs/pest-plugin-bridge/actions)
[![License](https://img.shields.io/github/license/TestFlowLabs/pest-plugin-bridge)](LICENSE)

A Pest plugin for browser testing external frontend applications.

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

## Example: Testing a Vue Frontend with Laravel Backend

```php
<?php

// tests/Pest.php
use TestFlowLabs\PestPluginBridge\Bridge;

// Your Vue app runs on port 5173
Bridge::setDefault('http://localhost:5173');

// tests/Feature/AuthTest.php
use App\Models\User;

test('user can complete login flow', function () {
    // Arrange: Create test user in Laravel
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    // Act: Test the Vue frontend
    $this->bridge('/login')
        ->fill('#email', 'test@example.com')
        ->fill('#password', 'password')
        ->click('button[type="submit"]')
        ->assertUrlContains('/dashboard')
        ->assertSee('Welcome');
});
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
