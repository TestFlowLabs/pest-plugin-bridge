# Configuration

Pest Plugin Bridge uses the `Bridge` class for programmatic configuration of external frontend URLs.

## Global Configuration

Configure in your `tests/Pest.php` file:

```php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;

// Set the default frontend URL
Bridge::setDefault('http://localhost:5173');
```

This is the recommended approach for single-frontend projects.

## Automatic Server Management

The plugin can automatically start and stop your frontend server:

```php
<?php
// tests/Pest.php

use TestFlowLabs\PestPluginBridge\Bridge;
use TestFlowLabs\PestPluginBridge\BridgeTrait;
use Tests\TestCase;

uses(TestCase::class, BridgeTrait::class)
    ->beforeAll(fn () => Bridge::setDefault('http://localhost:3000')
        ->serve('npm run dev', cwd: '../frontend')
        ->readyWhen('ready|localhost'))
    ->in('Browser');
```

The `->serve()` method accepts:
- `command` - The command to start the server (e.g., `npm run dev`)
- `cwd` - Working directory for the command

The `->readyWhen()` method accepts a regex pattern to detect when the server is ready.

Cleanup is automatic via shutdown handler — no `afterAll` needed!

## URL Validation

The plugin validates URLs using PHP's `filter_var()` with `FILTER_VALIDATE_URL`. Invalid URLs throw an `InvalidArgumentException`:

```php
// Valid URLs
Bridge::setDefault('http://localhost:5173');     // ✅
Bridge::setDefault('https://staging.app.com');  // ✅
Bridge::setDefault('http://192.168.1.100:3000'); // ✅

// Invalid URLs
Bridge::setDefault('localhost:5173');           // ❌ Missing scheme
Bridge::setDefault('not-a-url');                // ❌ Invalid format
Bridge::setDefault('');                         // ❌ Empty string
```

## Checking Configuration

You can check if a frontend is configured before running tests:

```php
use TestFlowLabs\PestPluginBridge\Bridge;

if (!Bridge::has()) {
    throw new RuntimeException('Default frontend not configured');
}

// Check named frontend
if (!Bridge::has('admin')) {
    throw new RuntimeException('Admin frontend not configured');
}
```

## Resetting Configuration

The plugin automatically resets configuration when tests complete via a shutdown handler. Manual reset is rarely needed, but available:

```php
// Manual reset (rarely needed)
Bridge::reset();
```

## Multiple Frontends

For projects with multiple frontends (micro-frontends, admin panels, customer portals), register named frontends:

```php
<?php
// tests/Pest.php

use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:3000');           // Customer portal
Bridge::frontend('admin', 'http://localhost:3001');    // Admin dashboard
Bridge::frontend('analytics', 'http://localhost:3002'); // Analytics panel
```

Then use them in your tests:

```php
<?php
// tests/Browser/MultiFrontendTest.php

test('customer can view products', function () {
    // Uses default frontend (localhost:3000)
    $this->bridge('/products')
        ->assertSee('Product Catalog');
});

test('customer can add to cart', function () {
    // Uses default frontend (localhost:3000)
    $this->bridge('/products/1')
        ->click('[data-testid="add-to-cart"]')
        ->assertVisible('[data-testid="cart-badge"]');
});

test('admin can view all users', function () {
    // Uses admin frontend (localhost:3001)
    $this->bridge('/users', 'admin')
        ->assertSee('User Management');
});

test('admin can create user', function () {
    // Uses admin frontend (localhost:3001)
    $this->bridge('/users/create', 'admin')
        ->fill('[data-testid="name-input"]', 'New User')
        ->click('[data-testid="save-button"]')
        ->assertSee('User created');
});

test('shows revenue metrics', function () {
    // Uses analytics frontend (localhost:3002)
    $this->bridge('/analytics', 'analytics')
        ->assertVisible('[data-testid="revenue-chart"]');
});
```

::: tip Named Frontends
Named frontends are registered once in `tests/Pest.php` and available in all test files. No need to repeat configuration in each file.
:::

## Next Steps

Now that configuration is set up, learn about [Writing Tests](/guide/writing-tests).
