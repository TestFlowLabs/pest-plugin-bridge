# Writing Tests

Learn how to write effective browser tests for your external frontend applications.

## Basic Structure

A browser test uses `bridge()` to navigate to your frontend:

```php
test('homepage loads correctly', function () {
    $this->bridge('/')
        ->assertSee('Welcome');
});
```

## The bridge() Method

`bridge()` is the core method provided by the plugin. It:

1. Takes a path (e.g., `/login`, `/dashboard`)
2. Prepends your configured base URL
3. Returns a Pest browser page object for chaining

```php
// If base URL is http://localhost:5173
$this->bridge('/login');
// Actually visits: http://localhost:5173/login
```

## Page Navigation

### Basic Navigation

```php
test('navigation works', function () {
    $this->bridge('/')
        ->click('a[href="/about"]')
        ->assertPathContains('/about');
});
```

### Direct Navigation

```php
test('can access different pages', function () {
    $this->bridge('/products')
        ->assertSee('Products');

    $this->bridge('/contact')
        ->assertSee('Contact Us');
});
```

## Form Interactions

### Filling Inputs

::: warning Vue/React/Nuxt Users
Use `typeSlowly()` instead of `fill()` for reactive frameworks. Vue's `v-model` and React's controlled inputs don't sync with `fill()` because it sets DOM values directly without firing input events.
:::

```php
// For non-reactive forms (plain HTML):
$this->bridge('/register')
    ->fill('input[name="name"]', 'John Doe');

// For Vue, React, Nuxt, Next.js (recommended):
$this->bridge('/register')
    ->waitForEvent('networkidle')
    ->click('input[name="name"]')
    ->typeSlowly('input[name="name"]', 'John Doe', 20);
```

### Using Data Test IDs (Recommended)

```php
test('can fill form with data-testid', function () {
    $this->bridge('/register')
        ->fill('[data-testid="name-input"]', 'John Doe')
        ->fill('[data-testid="email-input"]', 'john@example.com')
        ->fill('[data-testid="password-input"]', 'secret123');
});
```

### Clicking Buttons

```php
test('can submit form', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="submit-button"]');
});
```

### Selecting Options

```php
test('can select from dropdown', function () {
    $this->bridge('/settings')
        ->select('[data-testid="language-select"]', 'en')
        ->select('[data-testid="timezone-select"]', 'UTC');
});
```

### Checkboxes and Radio Buttons

```php
test('can interact with checkboxes', function () {
    $this->bridge('/preferences')
        ->check('[data-testid="newsletter-checkbox"]')
        ->uncheck('[data-testid="marketing-checkbox"]');
});
```

## Waiting Strategies

### Fixed Wait

Use `wait()` for simple timing:

```php
test('waits for animation', function () {
    $this->bridge('/dashboard')
        ->click('[data-testid="menu-toggle"]')
        ->wait(0.5) // Wait 500ms for animation
        ->assertVisible('[data-testid="sidebar"]');
});
```

### Wait for Element

Use `assertVisible()` which waits for elements:

```php
test('waits for element to appear', function () {
    $this->bridge('/search')
        ->fill('[data-testid="search-input"]', 'test')
        ->click('[data-testid="search-button"]')
        ->assertVisible('[data-testid="search-results"]'); // Waits automatically
});
```

### Wait After Actions

For async operations, add waits after actions:

```php
test('handles async login', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2) // Wait for API call and redirect
        ->assertPathContains('/dashboard');
});
```

## Working with Text

### Assert Text Visible

```php
test('displays welcome message', function () {
    $this->bridge('/')
        ->assertSee('Welcome to our app')
        ->assertDontSee('Error');
});
```

### Assert Text in Element

```php
test('displays user name in header', function () {
    $this->bridge('/dashboard')
        ->assertSeeIn('[data-testid="user-greeting"]', 'Hello, John');
});
```

## Element Visibility

```php
test('modal opens and closes', function () {
    $this->bridge('/page')
        ->assertNotVisible('[data-testid="modal"]')
        ->click('[data-testid="open-modal"]')
        ->assertVisible('[data-testid="modal"]')
        ->click('[data-testid="close-modal"]')
        ->assertNotVisible('[data-testid="modal"]');
});
```

## URL Assertions

```php
test('redirects after login', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertPathContains('/dashboard')
        ->assertPathIs('/dashboard');
});
```

## Complete Example

Here's a complete test file demonstrating various patterns:

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Product;

describe('E-commerce checkout flow', function () {
    beforeEach(function () {
        // Setup test data in Laravel
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 29.99,
        ]);
    });

    test('guest can browse products', function () {
        $this->bridge('/products')
            ->assertSee('Test Product')
            ->assertSee('$29.99');
    });

    test('guest can add product to cart', function () {
        $this->bridge('/products')
            ->click("[data-testid=\"product-{$this->product->id}\"]")
            ->assertPathContains('/products/')
            ->click('[data-testid="add-to-cart"]')
            ->wait(1)
            ->assertSee('Added to cart');
    });

    test('user can complete checkout', function () {
        $this->bridge('/login')
            ->waitForEvent('networkidle')
            ->click('[data-testid="email"]')
            ->typeSlowly('[data-testid="email"]', $this->user->email, 20)
            ->typeSlowly('[data-testid="password"]', 'password', 20)
            ->click('[data-testid="login-button"]')
            ->waitForEvent('networkidle')
            ->assertPathContains('/dashboard');

        $this->bridge('/cart')
            ->click('[data-testid="checkout-button"]')
            ->waitForEvent('networkidle')
            ->typeSlowly('[data-testid="card-number"]', '4242424242424242', 20)
            ->typeSlowly('[data-testid="card-expiry"]', '12/25', 20)
            ->typeSlowly('[data-testid="card-cvc"]', '123', 20)
            ->click('[data-testid="pay-button"]')
            ->waitForEvent('networkidle')
            ->assertSee('Order confirmed');
    });
});
```