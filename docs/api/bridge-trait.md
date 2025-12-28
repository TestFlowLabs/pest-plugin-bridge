# BridgeTrait

The `BridgeTrait` provides the core method for browser testing external frontend applications.

```php
use TestFlowLabs\PestPluginBridge\BridgeTrait;
```

## Usage

The trait is automatically included when you use Pest's browser testing. You access it via `$this` in your tests:

```php
test('homepage loads', function () {
    $this->bridge('/');
});
```

## Methods

### bridge

Navigate to a path on your external frontend application.

```php
public function bridge(string $path = '/', ?string $frontend = null): mixed
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$path` | `string` | `'/'` | The path to visit on the external frontend |
| `$frontend` | `?string` | `null` | Named frontend, or null for default |

**Returns:**

- `mixed` - A Pest browser page object that supports method chaining

**Throws:**

- `InvalidArgumentException` if no external URL is configured for the specified frontend

**Description:**

The `bridge()` method:
1. Takes a relative path (e.g., `/login`, `/dashboard`)
2. Combines it with your configured base URL via `Bridge::buildUrl()`
3. Navigates the browser to the complete URL
4. Returns a page object for further interactions and assertions

---

## Basic Examples

### Simple Navigation

```php
test('homepage loads', function () {
    $this->bridge('/')
        ->assertSee('Welcome');
});
```

### Navigate to Specific Path

```php
test('login page loads', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="login-form"]');
});
```

### With Named Frontend

```php
// tests/Pest.php
Bridge::setDefault('http://localhost:3000');
Bridge::frontend('admin', 'http://localhost:3001');

// tests/Browser/AdminTest.php
test('admin dashboard loads', function () {
    $this->bridge('/dashboard', 'admin')
        ->assertSee('Admin Panel');
});
```

### With Query Parameters

```php
test('search with query', function () {
    $this->bridge('/search?q=test')
        ->assertSee('Search results');
});
```

---

## Method Chaining

The returned page object supports fluent method chaining for interactions and assertions.

### Interactions

```php
test('can interact with page', function () {
    $this->bridge('/contact')
        ->fill('[data-testid="name"]', 'John Doe')
        ->fill('[data-testid="email"]', 'john@example.com')
        ->fill('[data-testid="message"]', 'Hello!')
        ->click('[data-testid="submit-button"]');
});
```

### Assertions

```php
test('page content is correct', function () {
    $this->bridge('/about')
        ->assertTitle('About Us')
        ->assertSee('Our Mission')
        ->assertVisible('[data-testid="team-section"]')
        ->assertSeeIn('[data-testid="footer"]', 'Contact');
});
```

### Combined Interactions and Assertions

```php
test('login flow', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="login-form"]')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="submit"]')
        ->wait(2)
        ->assertPathContains('/dashboard')
        ->assertSee('Welcome back');
});
```

---

## Available Chainable Methods

### Navigation & Waiting

| Method | Description |
|--------|-------------|
| `click(selector)` | Click an element |
| `wait(seconds)` | Wait for specified seconds |
| `debug()` | Pause execution for debugging |

### Form Interactions

| Method | Description |
|--------|-------------|
| `fill(selector, value)` | Fill an input field |
| `select(selector, value)` | Select a dropdown option |
| `check(selector)` | Check a checkbox |
| `uncheck(selector)` | Uncheck a checkbox |

### Assertions

| Method | Description |
|--------|-------------|
| `assertSee(text)` | Assert text is visible |
| `assertDontSee(text)` | Assert text is not visible |
| `assertSeeIn(selector, text)` | Assert text within element |
| `assertVisible(selector)` | Assert element is visible |
| `assertNotVisible(selector)` | Assert element is not visible |
| `assertPresent(selector)` | Assert element exists in DOM |
| `assertMissing(selector)` | Assert element not in DOM |
| `assertPathContains(path)` | Assert URL contains path |
| `assertPathIs(path)` | Assert exact URL path |
| `assertTitle(title)` | Assert page title |
| `assertTitleContains(text)` | Assert title contains text |
| `assertValue(selector, value)` | Assert input value |
| `assertChecked(selector)` | Assert checkbox is checked |
| `assertNotChecked(selector)` | Assert checkbox is unchecked |
| `assertSelected(selector, value)` | Assert select value |
| `assertAttribute(selector, attr, value)` | Assert attribute value |

---

## Multiple Frontends

Test different frontends in the same file:

```php
test('customer portal shows products', function () {
    $this->bridge('/products')
        ->assertSee('Product Catalog');
});

test('admin panel shows users', function () {
    $this->bridge('/users', 'admin')
        ->assertSee('User Management');
});

test('analytics shows revenue', function () {
    $this->bridge('/revenue', 'analytics')
        ->assertSee('Revenue Dashboard');
});
```

---

## Multiple Page Visits

You can call `bridge()` multiple times in a single test:

```php
test('navigation between pages', function () {
    // First page
    $this->bridge('/')
        ->assertSee('Home');

    // Second page
    $this->bridge('/about')
        ->assertSee('About');

    // Third page
    $this->bridge('/contact')
        ->assertSee('Contact');
});
```

---

## Complete Test Example

```php
<?php

declare(strict_types=1);

use App\Models\User;

describe('User Dashboard', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    test('user can view dashboard after login', function () {
        $this->bridge('/login')
            ->assertVisible('[data-testid="login-form"]')
            ->fill('[data-testid="email"]', $this->user->email)
            ->fill('[data-testid="password"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2)
            ->assertPathContains('/dashboard')
            ->assertSee('Welcome, John Doe')
            ->assertVisible('[data-testid="user-menu"]');
    });

    test('user can update profile', function () {
        // Login first
        $this->bridge('/login')
            ->fill('[data-testid="email"]', $this->user->email)
            ->fill('[data-testid="password"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        // Navigate to profile
        $this->bridge('/profile')
            ->assertValue('[data-testid="name-input"]', 'John Doe')
            ->fill('[data-testid="name-input"]', 'Jane Doe')
            ->click('[data-testid="save-button"]')
            ->wait(1)
            ->assertSee('Profile updated');
    });
});
```

---

## Error Handling

### No URL Configured

If no external URL is configured, `bridge()` throws an exception:

```php
// Without configuration
$this->bridge('/');
// Throws: InvalidArgumentException: Default frontend not configured...
```

**Solution:** Configure the URL in your `tests/Pest.php`:

```php
Bridge::setDefault('http://localhost:5173');
```

### Named Frontend Not Configured

```php
// Without registering 'admin' frontend
$this->bridge('/users', 'admin');
// Throws: InvalidArgumentException: Frontend 'admin' not configured...
```

**Solution:** Register the frontend:

```php
Bridge::frontend('admin', 'http://localhost:3001');
```

### Element Not Found

If a selector doesn't match any element:

```php
$this->bridge('/')
    ->click('[data-testid="non-existent"]');
// Test fails with element not found error
```

**Solution:** Use `assertVisible()` first to ensure element exists:

```php
$this->bridge('/')
    ->assertVisible('[data-testid="button"]')
    ->click('[data-testid="button"]');
```

---

## See Also

- [Bridge Class](/api/bridge) - Configure frontend URLs
- [Writing Tests](/guide/writing-tests) - Test patterns and examples
- [Assertions](/guide/assertions) - Complete assertions reference
