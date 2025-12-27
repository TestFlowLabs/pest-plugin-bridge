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

### Option 1: Environment Variable

Set the `PEST_BRIDGE_EXTERNAL_URL` environment variable:

```bash
# .env or .env.testing
PEST_BRIDGE_EXTERNAL_URL=http://localhost:5173
```

### Option 2: Programmatic Configuration

Configure in your `tests/Pest.php`:

```php
<?php

use TestFlowLabs\PestPluginBridge\Configuration;

Configuration::setExternalUrl('http://localhost:5173');
```

## Usage

Use the `visitExternal()` method to visit pages on your external frontend:

```php
<?php

test('user can login', function () {
    $this->visitExternal('/login')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'secret')
        ->click('[data-testid="login-button"]')
        ->assertUrlContains('/dashboard');
});

test('dashboard shows welcome message', function () {
    $this->visitExternal('/dashboard')
        ->assertSee('Welcome');
});
```

The `visitExternal($path)` method:
- Prepends the configured external URL to the given path
- Returns the same browser page object as Pest's `visit()` method
- Supports all standard browser testing methods (`fill()`, `click()`, `assertSee()`, etc.)

## Example: Testing a Vue Frontend with Laravel Backend

```php
<?php

// tests/Pest.php
use TestFlowLabs\PestPluginBridge\Configuration;

// Your Vue app runs on port 5173
Configuration::setExternalUrl('http://localhost:5173');

// tests/Feature/AuthTest.php
use App\Models\User;

test('user can complete login flow', function () {
    // Arrange: Create test user in Laravel
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    // Act: Test the Vue frontend
    $this->visitExternal('/login')
        ->fill('#email', 'test@example.com')
        ->fill('#password', 'password')
        ->click('button[type="submit"]')
        ->assertUrlContains('/dashboard')
        ->assertSee('Welcome');
});
```

## API Reference

### Configuration Class

| Method | Description |
|--------|-------------|
| `Configuration::setExternalUrl(string $url)` | Set the external frontend URL programmatically |
| `Configuration::getExternalUrl(): string` | Get the configured external URL |
| `Configuration::hasExternalUrl(): bool` | Check if an external URL is configured |
| `Configuration::buildUrl(string $path): string` | Build a full URL from a path |
| `Configuration::reset()` | Reset the configuration (useful for testing) |

### BridgeTrait Methods

| Method | Description |
|--------|-------------|
| `$this->visitExternal(string $path = '/')` | Visit a page on the external frontend |

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
