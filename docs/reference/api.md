# API Reference

Complete reference for all public methods in Pest Bridge Plugin.

## Bridge Class

The main static API for configuring and managing bridged frontends.

```php
use TestFlowLabs\PestPluginBridge\Bridge;
```

### Bridge::add()

Register a bridged frontend URL.

```php
Bridge::add(string $url, ?string $name = null): FrontendDefinition
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$url` | `string` | The frontend URL (must include scheme) |
| `$name` | `?string` | Optional name for multiple frontends |

**Returns:** `FrontendDefinition` for fluent configuration

**Examples:**

```php
// Default frontend
Bridge::add('http://localhost:3000');

// Named frontend
Bridge::add('http://localhost:3001', 'admin');

// With server management
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

**Throws:** `InvalidArgumentException` if URL is invalid or name is empty string.

---

### Bridge::url()

Get the URL for a configured frontend.

```php
Bridge::url(?string $name = null): string
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `?string` | Frontend name, or `null` for default |

**Returns:** The configured URL string

**Throws:** `InvalidArgumentException` if frontend not configured.

---

### Bridge::buildUrl()

Build a full URL by appending a path to a frontend URL.

```php
Bridge::buildUrl(string $path = '/', ?string $frontend = null): string
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Path to append (e.g., `/login`) |
| `$frontend` | `?string` | Frontend name, or `null` for default |

**Returns:** Full URL (e.g., `http://localhost:3000/login`)

**Example:**

```php
Bridge::buildUrl('/dashboard');           // http://localhost:3000/dashboard
Bridge::buildUrl('/users', 'admin');      // http://localhost:3001/users
```

---

### Bridge::has()

Check if a frontend is configured.

```php
Bridge::has(?string $name = null): bool
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `?string` | Frontend name, or `null` for default |

**Returns:** `true` if configured, `false` otherwise

---

### Bridge::reset()

Reset all configuration and stop servers.

```php
Bridge::reset(): void
```

Clears all frontend URLs, stops running servers, and removes all HTTP mocks/fakes.

::: tip Automatic Cleanup
Called automatically via shutdown handler. Manual calls are rarely needed.
:::

---

### Bridge::fake()

Register fake HTTP responses for Laravel backend calls.

```php
Bridge::fake(array $fakes): void
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fakes` | `array` | URL patterns mapped to response configs |

**Response config options:**
- `status` (int): HTTP status code (default: 200)
- `body` (array): Response body
- `headers` (array): Response headers

**Example:**

```php
Bridge::fake([
    'https://api.stripe.com/*' => [
        'status' => 200,
        'body' => ['id' => 'ch_123', 'status' => 'succeeded'],
    ],
]);
```

::: warning Middleware Required
Requires `BridgeHttpFakeMiddleware` registered in your Laravel app.
:::

---

### Bridge::mockBrowser()

Register fake HTTP responses for browser-level (fetch/XHR) calls.

```php
Bridge::mockBrowser(array $mocks): void
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mocks` | `array` | URL patterns mapped to response configs |

**Example:**

```php
Bridge::mockBrowser([
    'https://api.weather.com/*' => [
        'status' => 200,
        'body' => ['temp' => 25, 'city' => 'Istanbul'],
    ],
]);

$this->bridge('/weather')->assertSee('25');
```

---

### Bridge::clearFakes()

Clear all backend HTTP fakes.

```php
Bridge::clearFakes(): void
```

---

### Bridge::clearBrowserMocks()

Clear all browser-level HTTP mocks.

```php
Bridge::clearBrowserMocks(): void
```

---

### Bridge::hasFakes()

Check if any backend HTTP fakes are registered.

```php
Bridge::hasFakes(): bool
```

---

### Bridge::hasBrowserMocks()

Check if any browser HTTP mocks are registered.

```php
Bridge::hasBrowserMocks(): bool
```

---

## BridgeTrait

Provides the `bridge()` method for test classes.

```php
use TestFlowLabs\PestPluginBridge\BridgeTrait;

uses(BridgeTrait::class)->in('Browser');
```

### bridge()

Navigate to a bridged frontend path.

```php
$this->bridge(string $path = '/', ?string $frontend = null, array $options = []): mixed
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Path to visit (e.g., `/login`) |
| `$frontend` | `?string` | Frontend name, or `null` for default |
| `$options` | `array` | Playwright navigation options |

**Returns:** Browser page object (from pest-plugin-browser)

**Default options:**
- `timeout`: 30000ms (handles Vite cold-start)

**Example:**

```php
test('user can login', function () {
    $this->bridge('/login')
        ->typeSlowly('[data-testid="email"]', 'test@example.com')
        ->click('[data-testid="submit"]')
        ->assertPathContains('/dashboard');
});

// With named frontend
$this->bridge('/users', 'admin');

// With custom timeout
$this->bridge('/', options: ['timeout' => 60000]);
```

---

## FrontendDefinition

Fluent builder for frontend server configuration. Returned by `Bridge::add()`.

### ->serve()

Set the command to start the frontend server.

```php
->serve(string $command, ?string $cwd = null): self
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$command` | `string` | Shell command (e.g., `npm run dev`) |
| `$cwd` | `?string` | Working directory |

**Example:**

```php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

---

### ->readyWhen()

Set the pattern to detect when server is ready.

```php
->readyWhen(string $pattern): self
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pattern` | `string` | Regex pattern to match in server output |

**Default pattern:** `ready|localhost|started|listening|compiled|http://|https://`

Covers Nuxt, Vite, Next.js, CRA, and Angular dev servers.

**Example:**

```php
->readyWhen('VITE.*ready')
```

---

### ->warmup()

Add delay after server reports ready.

```php
->warmup(int $milliseconds): self
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$milliseconds` | `int` | Delay in milliseconds |

Useful for large frontends that need extra initialization time.

**Example:**

```php
->warmup(3000)  // Wait 3 seconds after ready
```

---

### ->env()

Set custom environment variables with path suffixes.

```php
->env(array $vars): self
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$vars` | `array` | Env var names mapped to path suffixes |

The test server URL is prepended to each suffix.

**Example:**

```php
->env([
    'VITE_ADMIN_API'    => '/v1/admin/',    // → http://127.0.0.1:8123/v1/admin/
    'VITE_RETAILER_API' => '/v1/retailer/', // → http://127.0.0.1:8123/v1/retailer/
])
```

---

### ->envFile()

Create an env file with test server URLs.

```php
->envFile(string $path): self
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Path to env file to create |

Useful for Vite where `.env.local` takes precedence over process env vars.

**Example:**

```php
->envFile('.env.testing')
```

---

### ->child()

Register a child frontend at a sub-path.

```php
->child(string $path, string $name): self
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Sub-path (e.g., `/admin`) |
| `$name` | `string` | Name to register the child as |

Child frontends share the parent's server process.

**Example:**

```php
Bridge::add('http://localhost:3001', 'admin')
    ->child('/analytics', 'analytics')  // → http://localhost:3001/analytics
    ->child('/reports', 'reports')      // → http://localhost:3001/reports
    ->serve('npm run dev', cwd: '../admin');

// In tests
$this->bridge('/', 'analytics');
```

---

### ->trustExistingServer()

Skip marker-based server verification.

```php
->trustExistingServer(): self
```

By default, Bridge verifies servers via marker files to prevent connecting to wrong applications. Use this escape hatch when:

- Running frontend manually (not via Bridge)
- CI environment with pre-started servers
- You're certain the correct app is running

**Example:**

```php
Bridge::add('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->trustExistingServer();
```

::: warning Use With Caution
Disables the safety check that prevents connecting to wrong applications.
:::
