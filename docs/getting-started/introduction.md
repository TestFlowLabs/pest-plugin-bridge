# Introduction

Pest Plugin Bridge extends [Pest's browser testing capabilities](https://pestphp.com/docs/browser-testing) to work with **external frontend applications** running on separate servers.

## The Problem

Modern web development often uses **decoupled architectures**:

```
┌─────────────────┐     API      ┌─────────────────┐
│   Laravel API   │◄────────────►│   Vue/React     │
│   Port: 8000    │              │   Port: 5173    │
└─────────────────┘              └─────────────────┘
```

Your PHP backend and JavaScript frontend run as separate applications. But how do you write end-to-end tests?

**Traditional approaches:**
- Write tests in JavaScript (Cypress, Playwright) - loses PHP ecosystem benefits
- Use Laravel Dusk - designed for same-application testing
- Maintain two test suites - doubles the work

## The Solution

Pest Plugin Bridge lets you test external frontends from your PHP test suite:

```php
test('user can complete checkout', function () {
    // Create test data in your Laravel app
    $product = Product::factory()->create(['price' => 99.99]);

    // Test the external React frontend
    $this->bridge('/shop')
        ->click("[data-testid=\"product-{$product->id}\"]")
        ->click('[data-testid="add-to-cart"]')
        ->click('[data-testid="checkout"]')
        ->assertSee('$99.99');
});
```

## Use Cases

### Single Page Applications (SPAs)
Test Vue, React, or Angular SPAs that consume your Laravel API.

### Server-Side Rendered Apps
Test Nuxt, Next.js, or other SSR frameworks with API backends.

### Microservices
Test frontend services that communicate with multiple backend services.

### Legacy Modernization
Test new frontend applications while maintaining backend tests in PHP.

## How It Works

1. **Configure** the external frontend URL (once)
2. **Use** `bridge()` instead of `visit()` in your tests
3. **Enjoy** all Pest browser assertions and methods

```php
// Configuration (in tests/Pest.php)
Bridge::setDefault('http://localhost:5173');

// Your test
test('homepage loads', function () {
    $this->bridge('/')
        ->assertSee('Welcome');
});
```

The plugin simply prepends your configured base URL to paths, then delegates to Pest's standard browser testing methods.

## Requirements

- PHP 8.3 or higher
- Pest 4.0 or higher
- Pest Plugin Browser 4.0 or higher
- Node.js (for Playwright)
