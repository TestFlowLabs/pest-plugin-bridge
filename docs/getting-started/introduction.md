# Introduction

Pest Plugin Bridge extends [Pest's browser testing capabilities](https://pestphp.com/docs/browser-testing) to work with **external frontend applications** running on separate servers.

## When Do You Need This?

### The Architecture Question

Ask yourself: **Where does your frontend live?**

| Your Setup | Frontend Location | What to Use |
|------------|-------------------|-------------|
| Blade, Livewire, Inertia | Inside Laravel | Regular Pest `visit()` |
| Vue/React SPA, Nuxt, Next.js | Separate project/server | **pest-plugin-bridge** `bridge()` |

### The Problem: Two-Way Communication

With a **headless Laravel API + separate frontend** architecture, you have a **bidirectional problem**:

<BidirectionalDiagram />

**Problem 1: Tests can't reach the frontend**

```php
// ❌ Fails - Laravel doesn't serve /shop
$this->visit('/shop')->assertSee('Products');

// ❌ Hardcoded URL - fragile, not portable
$this->visit('http://localhost:3000/shop')->assertSee('Products');
```

**Problem 2: Frontend can't reach the API**

During tests, Laravel runs on a **dynamic port** (assigned by pest-plugin-browser). Your frontend's `.env` file has a static URL like `API_URL=http://localhost:8000` — but that's not where Laravel is running during tests!

```javascript
// Frontend code (Vue/Nuxt/React)
const response = await fetch(process.env.API_URL + '/api/products');
// ❌ Wrong port! Laravel isn't on :8000 during tests
```

### The Solution: Bidirectional Bridge

pest-plugin-bridge solves **both directions**:

**1. Tests → Frontend** via `bridge()`

```php
// ✅ Clean, configurable
$this->bridge('/shop')->assertSee('Products');
```

**2. Frontend → API** via automatic environment injection

When you use `->serve()`, the plugin automatically injects the correct API URL into your frontend:

```php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

The plugin detects your framework and sets the right environment variable:

| Framework | Environment Variable |
|-----------|---------------------|
| Vite | `VITE_API_URL` |
| Nuxt 3 | `NUXT_PUBLIC_API_BASE` |
| Next.js | `NEXT_PUBLIC_API_URL` |
| Create React App | `REACT_APP_API_URL` |
| Generic | `API_URL`, `BACKEND_URL` |

Your frontend code works without changes:

```javascript
// Frontend automatically gets the correct test API URL
const response = await fetch(import.meta.env.VITE_API_URL + '/api/products');
// ✅ Points to Laravel's actual test port
```

## Real-World Scenarios

### Headless Laravel + Vue/Nuxt SPA

Your Laravel app is a pure API. Vue or Nuxt handles all UI rendering.

```php
test('user can browse products', function () {
    $product = Product::factory()->create(['name' => 'Laptop']);

    $this->bridge('/products')
        ->assertSee('Laptop');
});
```

### Multi-Tenant with Separate Frontends

Customer portal and admin panel are different apps:

```php
Bridge::add('http://localhost:3000');                  // Customer (default)
Bridge::add('http://localhost:3001', 'admin');         // Admin

test('customer sees their orders', function () {
    $this->bridge('/orders')->assertSee('Your Orders');
});

test('admin manages all orders', function () {
    $this->bridge('/orders', 'admin')->assertSee('All Orders');
});
```

### Gradual Migration

Moving from Blade to React? Test both during transition:

```php
test('old checkout still works', function () {
    $this->visit('/legacy/checkout')->assertSee('Pay Now');
});

test('new checkout works', function () {
    $this->bridge('/checkout')->assertSee('Pay Now');
});
```

### Microservices / Micro-Frontends

Multiple frontend services consuming your APIs:

```php
Bridge::add('http://localhost:3001', 'shop');
Bridge::add('http://localhost:3002', 'blog');
Bridge::add('http://localhost:3003', 'docs');
```

## How It Works

1. **Configure** the external frontend URL (once)
2. **Use** `bridge()` instead of `visit()` in your tests
3. **Enjoy** all Pest browser assertions and methods

```php
// Configuration (in tests/Pest.php)
Bridge::add('http://localhost:5173');

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
