# Configuration

Pest Bridge Plugin uses the `Bridge` class for programmatic configuration of bridged frontend URLs.

## Global Configuration

Configure in your `tests/Pest.php` file:

```php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;

// Add the default frontend URL
Bridge::add('http://localhost:5173');
```

This is the recommended approach for single-frontend projects.

## Automatic Server Management

The plugin can automatically start and stop your frontend server:

```php
<?php
// tests/Pest.php

use TestFlowLabs\PestPluginBridge\Bridge;
use Tests\TestCase;

// Configure frontend with automatic server management
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');

pest()->extends(TestCase::class)->in('Browser');
```

### How It Works

1. **Lazy Start**: Server starts automatically on the first `bridge()` call
2. **API URL Injection**: Laravel API URL is injected via environment variables:
   - `API_URL`, `VITE_API_URL`, `NUXT_PUBLIC_API_BASE`, `NEXT_PUBLIC_API_URL`, `REACT_APP_API_URL`
3. **Ready Detection**: Waits for server output to match the pattern before continuing
4. **Auto Stop**: Server stops automatically when tests complete (via shutdown handler)

### Configuration Options

| Method | Description |
|--------|-------------|
| `->serve(string $command, ?string $cwd = null)` | Command to start the server |
| `->readyWhen(string $pattern)` | Regex pattern to detect server ready (optional) |
| `->warmup(int $milliseconds)` | Extra delay after server reports ready (for large frontends) |
| `->env(array $vars)` | Custom environment variables with path suffixes |
| `->child(string $path, string $name)` | Register a child frontend at a sub-path (same server) |
| `->trustExistingServer()` | Trust any server on the port (escape hatch for manual starts) |

::: tip readyWhen() is Optional
The default pattern (`ready|localhost|started|listening|compiled|http://|https://`) covers most frontend dev servers. Only use `readyWhen()` if your server has a unique output format.
:::

### Multiple Bridged Frontends with Auto-Start

```php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../customer-portal');

Bridge::add('http://localhost:3001', 'admin')
    ->serve('npm run dev', cwd: '../admin-panel');
```

### Custom Environment Variables

The plugin automatically injects the test Laravel server URL into common environment variables. For projects with custom API endpoint structures, use the `env()` method:

```php
Bridge::add('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->env([
        // Custom API endpoints - path suffixes are appended to the test server URL
        'VITE_BACKEND_URL'          => '/',           // http://127.0.0.1:8123/
        'VITE_ADMIN_API'            => '/v1/admin/',  // http://127.0.0.1:8123/v1/admin/
        'VITE_RETAILER_API'         => '/v1/retailer/', // http://127.0.0.1:8123/v1/retailer/
        'VITE_PUBLIC_API'           => '/v1/',        // http://127.0.0.1:8123/v1/
    ]);
```

#### Default Environment Variables

The plugin automatically sets these environment variables (no configuration needed):

| Variable | Framework | Value |
|----------|-----------|-------|
| `API_URL`, `API_BASE_URL`, `BACKEND_URL` | Generic | Test server URL |
| `VITE_API_URL`, `VITE_API_BASE_URL`, `VITE_BACKEND_URL` | Vite (Vue, React, Svelte) | Test server URL |
| `NUXT_PUBLIC_API_BASE`, `NUXT_PUBLIC_API_URL` | Nuxt 3 | Test server URL |
| `NEXT_PUBLIC_API_URL`, `NEXT_PUBLIC_API_BASE_URL` | Next.js | Test server URL |
| `REACT_APP_API_URL`, `REACT_APP_API_BASE_URL` | Create React App | Test server URL |

::: tip Environment Variable Precedence
Process environment variables (injected by the plugin) take precedence over `.env` and `.env.local` files in Vite. This ensures your frontend always calls the test server during tests.
:::

### Warmup Delay

Large frontend applications may need extra time after reporting "ready" before they can efficiently handle page loads. Use `warmup()` to add a delay:

```php
Bridge::add('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('VITE.*ready')
    ->warmup(3000); // Wait 3 seconds after server is ready
```

This is particularly useful for:
- Large monorepo frontends with many dependencies
- Frontends that perform heavy initialization on startup
- Development servers that need time to compile initial bundles

::: info Vite Cold-Start
Vite compiles JavaScript modules on-demand when the browser first requests them. The `bridge()` method already uses a 30-second timeout by default to handle this. Use `warmup()` only if you experience consistent timeouts with very large applications. See [Troubleshooting: Vite Cold-Start Timeout](/guide/troubleshooting#vite-cold-start-timeout) for more details.
:::

### Child Frontends

When a single frontend server serves multiple named sections at different paths, use `child()` to avoid URL repetition:

```php
Bridge::add('http://localhost:3001', 'admin')
    ->child('/analytics', 'analytics')
    ->child('/reports', 'reports')
    ->serve('npm run dev', cwd: '../admin-frontend');
```

This registers three bridged frontends sharing the same server:
- `admin` → `http://localhost:3001`
- `analytics` → `http://localhost:3001/analytics`
- `reports` → `http://localhost:3001/reports`

Use them in tests:

```php
test('admin can view dashboard', function () {
    $this->bridge('/', 'admin')
        ->assertSee('Admin Dashboard');
});

test('analytics page shows charts', function () {
    $this->bridge('/', 'analytics')
        ->assertVisible('[data-testid="revenue-chart"]');
});

test('reports page shows data', function () {
    $this->bridge('/', 'reports')
        ->assertVisible('[data-testid="reports-table"]');
});
```

### Automatic Server Identification

When Bridge starts a frontend server, it writes a **marker file** to the system temp directory. This marker contains:
- Port number
- Working directory (CWD)
- Process ID (PID)
- Command used to start

When a port is already in use, Bridge checks this marker to determine what's running:

| Marker Status | Meaning | Action |
|---------------|---------|--------|
| **Match** | Same CWD, PID alive | Safe to reuse (our server) |
| **Stale** | Same CWD, PID dead | Server died, restart it |
| **Mismatch** | Different CWD | Different app! Throw error |
| **None** | No marker file | Unknown process, throw error |

This prevents accidentally connecting to the wrong application when you have multiple frontend projects.

#### Why This Matters

**Problem:** You specify `http://localhost:5173` but a different project's server is running:

```
Your config:        http://localhost:5173 → ../customer-portal
What's running:     http://localhost:5173 → ../admin-panel (different app!)
Without marker:     Tests connect to admin panel → WRONG!
With marker:        Bridge detects mismatch → Clear error message
```

**Solution:** Bridge automatically identifies servers it started, preventing cross-project confusion.

#### Manual Server Starts (Escape Hatch)

If you start the frontend manually (not via Bridge's `serve()`), there's no marker file. Bridge will throw an error by default. Use `trustExistingServer()` to skip verification:

```php
Bridge::add('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->trustExistingServer();  // Skip marker verification
```

::: warning Use With Caution
Only use `trustExistingServer()` when you're certain the correct application is running. It disables the safety check that prevents connecting to the wrong app.
:::

::: tip For Vite Users
Add `--strictPort` to make Vite fail immediately if the port is in use, giving a clearer error:
```php
->serve('npm run dev -- --strictPort', cwd: '../frontend')
```
:::

## URL Validation

The plugin validates URLs using PHP's `filter_var()` with `FILTER_VALIDATE_URL`. Invalid URLs throw an `InvalidArgumentException`:

```php
// Valid URLs
Bridge::add('http://localhost:5173');     // ✅
Bridge::add('https://staging.app.com');  // ✅
Bridge::add('http://192.168.1.100:3000'); // ✅

// Invalid URLs
Bridge::add('localhost:5173');           // ❌ Missing scheme
Bridge::add('not-a-url');                // ❌ Invalid format
Bridge::add('');                         // ❌ Empty string
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

## Multiple Bridged Frontends

For projects with multiple frontends (micro-frontends, admin panels, customer portals), register named bridged frontends:

```php
<?php
// tests/Pest.php

use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::add('http://localhost:3000');                  // Customer portal (default)
Bridge::add('http://localhost:3001', 'admin');         // Admin dashboard
Bridge::add('http://localhost:3002', 'analytics');     // Analytics panel
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

::: tip Bridged Frontends
Bridged frontends are registered once in `tests/Pest.php` and available in all test files. No need to repeat configuration in each file.
:::