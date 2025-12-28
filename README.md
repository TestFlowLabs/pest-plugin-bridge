# Pest Bridge Plugin

[![Tests](https://github.com/TestFlowLabs/pest-plugin-bridge/actions/workflows/tests.yml/badge.svg)](https://github.com/TestFlowLabs/pest-plugin-bridge/actions)
[![License](https://img.shields.io/github/license/TestFlowLabs/pest-plugin-bridge)](LICENSE)

Test external frontends from Laravel — write browser tests in PHP for Vue, React, Nuxt, Next.js.

**[Documentation](https://bridge.testflowlabs.dev)** | **[Getting Started](https://bridge.testflowlabs.dev/getting-started/quick-start)**

## The Problem

You have a **headless Laravel API** and a **separate frontend** (Vue, Nuxt, React, Next.js). Two apps, two ports, two problems:

- Your **tests can't reach** the frontend
- Your **frontend can't find** the API during tests

## The Solution

Pest Bridge Plugin solves both directions:

```php
// test → frontend: bridge() visits the external frontend
$this->bridge('/login')->assertSee('Welcome');

// frontend → API: automatic environment variable injection
// VITE_API_URL, NUXT_PUBLIC_API_BASE, NEXT_PUBLIC_API_URL, etc.
```

## Installation

```bash
composer require testflowlabs/pest-plugin-bridge --dev
```

> Requires [pestphp/pest-plugin-browser](https://github.com/pestphp/pest-plugin-browser)

## Quick Start

```php
// tests/Pest.php
use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

```php
// tests/Browser/LoginTest.php
test('user can login', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->bridge('/login')
        ->typeSlowly('[data-testid="email"]', 'test@example.com')
        ->typeSlowly('[data-testid="password"]', 'password')
        ->click('[data-testid="submit"]')
        ->waitForEvent('networkidle')
        ->assertPathContains('/dashboard');
});
```

## Features

### Automatic Server Management

Frontend starts on first `bridge()` call, stops when tests complete. No manual server management.

```php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('VITE.*ready');
```

### Multiple Bridged Frontends

Test multiple frontends in one suite. Child frontends share the parent's server process.

```php
Bridge::add('http://localhost:3000');                  // Default
Bridge::add('http://localhost:3001', 'admin')
    ->child('/analytics', 'analytics')                 // Same server, /analytics path
    ->serve('npm run dev', cwd: '../admin');
```

```php
$this->bridge('/products');                            // Default frontend
$this->bridge('/users', 'admin');                      // Admin frontend
$this->bridge('/', 'analytics');                       // Child of admin
```

### Multi-Repository CI/CD

GitHub Actions checks out both repos side-by-side. Works with private repos.

```yaml
- uses: actions/checkout@v4
  with: { path: backend }
- uses: actions/checkout@v4
  with: { repository: your-org/frontend, path: frontend }
```

### Vue/React Compatible

`typeSlowly()` triggers real keyboard events that Vue v-model and React hooks respond to.

```php
// fill() sets DOM directly — Vue won't see it
// typeSlowly() fires keydown/input/keyup events
->typeSlowly('[data-testid="email"]', 'test@example.com')
```

## Documentation

For complete guides, examples, and CI/CD workflows:

**[bridge.testflowlabs.dev](https://bridge.testflowlabs.dev)**

- [Introduction](https://bridge.testflowlabs.dev/getting-started/introduction) — How it works
- [Configuration](https://bridge.testflowlabs.dev/guide/configuration) — All options
- [CI/CD Setup](https://bridge.testflowlabs.dev/ci-cd/introduction) — GitHub Actions
- [Troubleshooting](https://bridge.testflowlabs.dev/guide/troubleshooting) — Common issues

## Development

```bash
composer install
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
