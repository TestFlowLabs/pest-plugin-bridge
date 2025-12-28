# Bridge Class

The `Bridge` class manages frontend URL configuration for browser tests.

```php
use TestFlowLabs\PestPluginBridge\Bridge;
```

## Methods

### setDefault

Set the default frontend base URL. Returns a `FrontendDefinition` for fluent configuration.

```php
public static function setDefault(string $url): FrontendDefinition
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$url` | `string` | The base URL of your default frontend |

**Returns:**

- `FrontendDefinition` - A fluent builder for additional configuration

**Throws:**

- `InvalidArgumentException` if the URL is invalid or empty

**Example:**

```php
// Simple URL configuration
Bridge::setDefault('http://localhost:5173');

// With automatic server management (fluent API)
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('ready|localhost');

// Valid URLs
Bridge::setDefault('http://localhost:5173');
Bridge::setDefault('https://staging.myapp.com');
Bridge::setDefault('http://192.168.1.100:3000');

// Invalid - throws exception
Bridge::setDefault('localhost:5173'); // Missing scheme
Bridge::setDefault('not-a-url');      // Invalid format
Bridge::setDefault('');               // Empty string
```

---

### frontend

Add a named frontend configuration. Returns a `FrontendDefinition` for fluent configuration.

```php
public static function frontend(string $name, string $url): FrontendDefinition
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | A unique name for the frontend |
| `$url` | `string` | The base URL of the frontend |

**Returns:**

- `FrontendDefinition` - A fluent builder for additional configuration

**Throws:**

- `InvalidArgumentException` if the name is empty or URL is invalid

**Example:**

```php
// Simple named frontends
Bridge::frontend('admin', 'http://localhost:3001');
Bridge::frontend('mobile', 'http://localhost:3002');

// With automatic server management
Bridge::frontend('admin', 'http://localhost:3001')
    ->serve('npm run dev', cwd: '../admin-panel')
    ->readyWhen('ready|localhost');
```

---

### url

Get the configured URL for a frontend.

```php
public static function url(?string $name = null): string
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$name` | `?string` | `null` | Frontend name, or null for default |

**Returns:**

- `string` - The configured base URL

**Throws:**

- `InvalidArgumentException` if no URL is configured for the specified frontend

**Example:**

```php
Bridge::setDefault('http://localhost:3000');
Bridge::frontend('admin', 'http://localhost:3001');

Bridge::url();        // Returns: 'http://localhost:3000'
Bridge::url('admin'); // Returns: 'http://localhost:3001'

// Without configuration
Bridge::url('mobile'); // Throws InvalidArgumentException
```

---

### has

Check if a frontend is configured.

```php
public static function has(?string $name = null): bool
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$name` | `?string` | `null` | Frontend name, or null for default |

**Returns:**

- `bool` - `true` if configured, `false` otherwise

**Example:**

```php
// Before configuration
Bridge::has();        // Returns: false
Bridge::has('admin'); // Returns: false

// After configuration
Bridge::setDefault('http://localhost:3000');
Bridge::frontend('admin', 'http://localhost:3001');

Bridge::has();        // Returns: true
Bridge::has('admin'); // Returns: true
Bridge::has('mobile'); // Returns: false
```

---

### buildUrl

Build a complete URL by combining the base URL with a path.

```php
public static function buildUrl(string $path = '/', ?string $frontend = null): string
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$path` | `string` | `'/'` | The path to append to the base URL |
| `$frontend` | `?string` | `null` | Frontend name, or null for default |

**Returns:**

- `string` - The complete URL

**Throws:**

- `InvalidArgumentException` if no base URL is configured for the specified frontend

**Example:**

```php
Bridge::setDefault('http://localhost:3000');
Bridge::frontend('admin', 'http://localhost:3001');

// Default frontend
Bridge::buildUrl('/');                    // http://localhost:3000/
Bridge::buildUrl('/login');               // http://localhost:3000/login
Bridge::buildUrl('dashboard');            // http://localhost:3000/dashboard

// Named frontend
Bridge::buildUrl('/users', 'admin');      // http://localhost:3001/users
Bridge::buildUrl('/settings', 'admin');   // http://localhost:3001/settings

// Nested paths
Bridge::buildUrl('/users/profile/settings');
// http://localhost:3000/users/profile/settings

// With query strings
Bridge::buildUrl('/search?q=test&page=1');
// http://localhost:3000/search?q=test&page=1

// With fragments
Bridge::buildUrl('/docs#installation');
// http://localhost:3000/docs#installation
```

**Trailing Slash Handling:**

The method handles trailing slashes intelligently:

```php
// Base URL without trailing slash
Bridge::setDefault('http://localhost:5173');
Bridge::buildUrl('/dashboard'); // http://localhost:5173/dashboard

// Base URL with trailing slash
Bridge::setDefault('http://localhost:5173/');
Bridge::buildUrl('/dashboard'); // http://localhost:5173/dashboard
```

---

### reset

Reset all configuration and stop any running frontend servers.

```php
public static function reset(): void
```

**Description:**

Clears both the default URL and all named frontends, and stops any frontend servers started via `->serve()`. After reset, `url()` will throw an exception until new URLs are configured.

This method is called automatically via a shutdown handler when tests complete. Manual calls are rarely needed.

**Example:**

```php
Bridge::setDefault('http://localhost:3000');
Bridge::frontend('admin', 'http://localhost:3001');

Bridge::url();        // Returns: 'http://localhost:3000'
Bridge::url('admin'); // Returns: 'http://localhost:3001'

Bridge::reset();

Bridge::url();        // Throws InvalidArgumentException
Bridge::url('admin'); // Throws InvalidArgumentException
```

**Automatic Cleanup:**

The plugin automatically calls `Bridge::reset()` via a shutdown handler, so manual cleanup is not needed:

```php
// No afterAll needed! Cleanup is automatic.
```

---

## FrontendDefinition (Fluent API)

The `FrontendDefinition` class is returned by `setDefault()` and `frontend()` methods, enabling fluent configuration of automatic server management.

### serve

Configure automatic server startup.

```php
public function serve(string $command, ?string $cwd = null): self
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$command` | `string` | The command to start the server (e.g., `npm run dev`) |
| `$cwd` | `?string` | Working directory for the command |

**Returns:**

- `self` - For method chaining

**Example:**

```php
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

---

### readyWhen

Set the pattern to detect when the server is ready.

```php
public function readyWhen(string $pattern): self
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pattern` | `string` | Regex pattern to match in server output |

**Returns:**

- `self` - For method chaining

**Example:**

```php
// For Nuxt
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../nuxt-app')
    ->readyWhen('Local:.*localhost:3000');

// For Vite (React/Vue)
Bridge::setDefault('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('VITE.*ready|localhost:5173');
```

---

## Complete Usage Example

```php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;

// In tests/Pest.php - Global configuration
Bridge::setDefault('http://localhost:3000');
Bridge::frontend('admin', 'http://localhost:3001');

// In a test file
test('can access the default frontend', function () {
    $this->bridge('/dashboard')
        ->assertSee('Dashboard');
});

test('can access admin frontend', function () {
    $this->bridge('/users', 'admin')
        ->assertSee('User Management');
});
```

## API Reference

### Bridge Class

| Method | Returns | Description |
|--------|---------|-------------|
| `Bridge::setDefault(string $url)` | `FrontendDefinition` | Set the default frontend URL |
| `Bridge::frontend(string $name, string $url)` | `FrontendDefinition` | Add a named frontend |
| `Bridge::url(?string $name = null)` | `string` | Get URL for a frontend |
| `Bridge::has(?string $name = null)` | `bool` | Check if frontend is configured |
| `Bridge::buildUrl(string $path, ?string $frontend)` | `string` | Build full URL |
| `Bridge::reset()` | `void` | Reset all configuration and stop servers |

### FrontendDefinition Class

| Method | Returns | Description |
|--------|---------|-------------|
| `->serve(string $command, ?string $cwd)` | `self` | Configure automatic server startup |
| `->readyWhen(string $pattern)` | `self` | Set server ready detection pattern |
