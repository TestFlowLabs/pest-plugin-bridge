# Pest Bridge Plugin

A Pest plugin for browser testing external frontend applications from Laravel.

## Project Overview

This plugin extends [Pest's browser testing](https://pestphp.com/docs/browser-testing) capabilities to work with **external/detached frontend applications** - such as Vue, React, Nuxt, or Angular apps running on separate servers.

### The Problem This Solves

Modern web development uses decoupled architectures:

```
┌─────────────────┐     API      ┌─────────────────┐
│   Laravel API   │◄────────────►│   Vue/React     │
│   Port: 8000    │              │   Port: 5173    │
└─────────────────┘              └─────────────────┘
```

This plugin lets you write browser tests in PHP that test external frontends.

## Requirements

- PHP 8.3+
- Pest 4.0+
- pestphp/pest-plugin-browser 4.0+
- Node.js 18+ (for Playwright)

## Project Structure

```
src/
├── Autoload.php           # Plugin bootstrap and shutdown handler
├── Bridge.php             # Main configuration class (static API)
├── BridgeTrait.php        # Provides bridge() method for tests
├── BrowserMockStore.php   # In-memory store for browser-level mocks
├── FrontendDefinition.php # Fluent builder for server config
├── FrontendManager.php    # Manages server lifecycles (static class)
├── FrontendServer.php     # Individual server process management
├── ServerMarker.php       # Marker-based server identification
└── Laravel/
    └── BridgeHttpFakeMiddleware.php  # Cross-process HTTP faking

tests/
├── Browser/               # Browser test examples
└── Unit/                  # Unit tests

docs/                      # VitePress documentation site
```

## Core Architecture

### Bridge Class (Static API)

The main entry point for configuration:

```php
use TestFlowLabs\PestPluginBridge\Bridge;

// Add default frontend URL
Bridge::add('http://localhost:3000');

// Add named frontends
Bridge::add('http://localhost:3001', 'admin');

// Get URL
Bridge::url();           // default frontend URL
Bridge::url('admin');    // named frontend URL

// Check if configured
Bridge::has();           // true if default configured
Bridge::has('admin');    // true if admin configured

// Build full URL
Bridge::buildUrl('/login');        // http://localhost:3000/login
Bridge::buildUrl('/users', 'admin'); // http://localhost:3001/users

// Reset all config (rarely needed - automatic on shutdown)
Bridge::reset();
```

### Automatic Server Management

Frontends can be started automatically:

```php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('ready|localhost');
```

- `->serve(command, cwd)` - Command to start server and working directory
- `->readyWhen(pattern)` - Regex pattern to detect when server is ready

Servers are stopped automatically via shutdown handler.

### Vite Cold-Start Handling

**Important:** When using `serve()` with Vite-based frontends, the first page load triggers on-demand module compilation. For large apps, this can take 3-5+ seconds.

```php
// Vite reports "ready" when HTTP server starts
// But actual JS compilation happens on first browser request!

Bridge::add('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('VITE.*ready');

// The bridge() method uses a 30s timeout by default to handle this
$this->bridge('/')->assertPathIs('/');  // Works with cold-start
```

**What happens:**
1. `npm run dev` starts → Vite outputs "VITE ready in 500ms"
2. HTTP check confirms server responds → just the HTML shell
3. Test navigates in Playwright → browser requests JS modules
4. Vite compiles modules on-demand → takes 3-5+ seconds
5. Page becomes interactive

**Why manual start seems faster:** When you manually start the frontend and reload the page in your browser, you trigger the compilation. By the time tests run, modules are cached.

**Options for additional control:**
```php
// Add extra warmup delay after server is ready (optional)
Bridge::add('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('VITE.*ready')
    ->warmup(2000);  // Additional 2s delay

// Override timeout per navigation
$this->bridge('/', options: ['timeout' => 60000]);  // 60s timeout
```

### HTTP Mocking

Two types of HTTP mocking for different scenarios:

#### Backend HTTP Faking (Laravel → External APIs)

Mock HTTP calls made by your Laravel backend to external services:

```php
use TestFlowLabs\PestPluginBridge\Bridge;

// In your test
Bridge::fake([
    'https://api.stripe.com/*' => [
        'status' => 200,
        'body' => ['id' => 'ch_123', 'status' => 'succeeded'],
    ],
    'https://api.sendgrid.com/*' => [
        'status' => 202,
        'body' => ['message' => 'queued'],
    ],
]);

$this->bridge('/checkout')
    ->click('[data-testid="pay"]')
    ->assertSee('Payment successful');
```

**Note:** Requires `BridgeHttpFakeMiddleware` registered in Laravel.

#### Browser HTTP Mocking (Frontend → External APIs)

Mock fetch/XHR calls made directly by JavaScript in the browser:

```php
Bridge::mockBrowser([
    'https://api.weather.com/*' => [
        'status' => 200,
        'body' => ['temp' => 25, 'city' => 'Istanbul'],
    ],
]);

$this->bridge('/weather')
    ->assertSee('25°C');
```

### Server Trust and Marker System

By default, Bridge uses marker files to verify server ownership before reuse:

```php
// Trust an existing server without marker verification
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->trustExistingServer();  // Skip marker check
```

**When to use `trustExistingServer()`:**
- You start the frontend manually before tests
- Working in a shared development environment
- CI environment where servers are pre-started

### BridgeTrait

Provides the `bridge()` method for tests:

```php
test('homepage loads', function () {
    $this->bridge('/')                    // Navigate to frontend
        ->fill('[data-testid="email"]', 'user@example.com')
        ->click('[data-testid="submit"]')
        ->assertSee('Welcome');
});

// With named frontend
$this->bridge('/users', 'admin');
```

## Configuration in tests/Pest.php

### Simple (Manual Server Start)

```php
use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::add('http://localhost:5173');
```

### With Automatic Server Management

```php
use TestFlowLabs\PestPluginBridge\Bridge;
use TestFlowLabs\PestPluginBridge\BridgeTrait;
use Tests\TestCase;

uses(TestCase::class, BridgeTrait::class)
    ->beforeAll(fn () => Bridge::add('http://localhost:3000')
        ->serve('npm run dev', cwd: '../frontend')
        ->readyWhen('ready|localhost'))
    ->in('Browser');
```

## Test Writing Patterns

### Basic Test

```php
test('page loads', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="login-form"]')
        ->fill('[data-testid="email"]', 'test@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="submit"]')
        ->wait(2)
        ->assertPathContains('/dashboard');
});
```

### With Laravel Test Data

```php
use App\Models\User;

test('user can login', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->bridge('/login')
        ->fill('[data-testid="email"]', $user->email)
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="submit"]')
        ->assertSee('Welcome');
});
```

### Multiple Frontends

```php
test('customer sees products', function () {
    $this->bridge('/products')->assertSee('Catalog');
});

test('admin sees users', function () {
    $this->bridge('/users', 'admin')->assertSee('User Management');
});
```

### Child Frontends

Share a server process between related frontends:

```php
// In tests/Pest.php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->child('/admin', 'admin')   // http://localhost:3000/admin
    ->child('/docs', 'docs');    // http://localhost:3000/docs

// In tests
$this->bridge('/');               // Main app
$this->bridge('/', 'admin');      // Admin section
$this->bridge('/guide', 'docs');  // Docs section
```

### Environment Variables

Automatic injection for common frameworks:

```php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');

// Automatically sets (based on detected framework):
// - VITE_API_URL / NUXT_PUBLIC_API_URL / NEXT_PUBLIC_API_URL
// - VITE_API_BASE_URL / NUXT_PUBLIC_API_BASE_URL / etc.
```

Custom environment variables with path suffixes:

```php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->env([
        'CUSTOM_VAR' => 'value',
        'API_KEY'    => 'test-key',
    ]);
```

Vite `.env` file support (bypasses `.env.local` precedence):

```php
Bridge::add('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->envFile('.env.testing');  // Creates temp file, uses --mode flag
```

## Available Browser Methods

### Navigation & Waiting

- `click(selector)` - Click element
- `wait(seconds)` - Wait fixed time
- `debug()` - Pause for inspection

### Form Interactions

- `fill(selector, value)` - Fill input
- `select(selector, value)` - Select dropdown option
- `check(selector)` / `uncheck(selector)` - Toggle checkbox

### Assertions

- `assertSee(text)` / `assertDontSee(text)`
- `assertSeeIn(selector, text)`
- `assertVisible(selector)` / `assertNotVisible(selector)`
- `assertPresent(selector)` / `assertMissing(selector)`
- `assertPathContains(path)` / `assertPathIs(path)`
- `assertTitle(title)` / `assertTitleContains(text)`
- `assertValue(selector, value)`
- `assertChecked(selector)` / `assertNotChecked(selector)`
- `assertAttribute(selector, attr, value)`

## Development Commands

```bash
# Install dependencies
composer install

# Run full test suite
composer test

# Individual checks
composer test:rector     # Rector (code fixes)
composer test:pint      # Laravel Pint (code style)
composer test:phpstan   # PHPStan (static analysis)
composer test:unit      # Pest unit tests
composer test:types     # Type coverage

# Run with visible browser
./vendor/bin/pest tests/Browser --headed
```

## Key Files to Know

| File | Purpose |
|------|---------|
| `src/Bridge.php` | Static API for configuration, HTTP faking |
| `src/BridgeTrait.php` | Provides `bridge()` method with mock injection |
| `src/BrowserMockStore.php` | In-memory store for browser-level mocks |
| `src/FrontendDefinition.php` | Fluent builder (`->serve()`, `->readyWhen()`, `->warmup()`, `->env()`, `->child()`) |
| `src/FrontendManager.php` | Static class managing all server lifecycles |
| `src/FrontendServer.php` | Individual server process management |
| `src/ServerMarker.php` | Marker-based server identification and verification |
| `src/Autoload.php` | Plugin bootstrap, shutdown handler |
| `src/Laravel/BridgeHttpFakeMiddleware.php` | Cross-process HTTP faking middleware |
| `tests/Pest.php` | Test configuration |
| `phpstan.neon` | PHPStan config (level max) |
| `rector.php` | Rector config |
| `pint.json` | Laravel Pint config |

## URL Validation

URLs must be valid with scheme:

```php
// Valid
Bridge::add('http://localhost:5173');
Bridge::add('https://staging.app.com');

// Invalid - throws InvalidArgumentException
Bridge::add('localhost:5173');  // Missing scheme
Bridge::add('not-a-url');
```

## Packagist

Published at: `testflowlabs/pest-plugin-bridge`

Install: `composer require testflowlabs/pest-plugin-bridge --dev`

## Playground Testing

Integration testing uses external playground repositories:

- **API:** [TestFlowLabs/pest-plugin-bridge-playground-api](https://github.com/TestFlowLabs/pest-plugin-bridge-playground-api) (Laravel)
- **Frontend:** [TestFlowLabs/pest-plugin-bridge-playground-nuxt](https://github.com/TestFlowLabs/pest-plugin-bridge-playground-nuxt) (Nuxt)

### Running Playground Tests

Tests are triggered manually via GitHub Actions:

```bash
# Via GitHub CLI
gh workflow run playground-browser-tests.yml

# With custom branches
gh workflow run playground-browser-tests.yml \
  -f api_branch=feature-branch \
  -f frontend_branch=main

# With test filter
gh workflow run playground-browser-tests.yml \
  -f test_filter="login"
```

Or use the GitHub Actions UI: **Actions → Playground Browser Tests → Run workflow**

The workflow (`.github/workflows/playground-browser-tests.yml`) checks out both repos, sets up the environment, and runs browser tests.
