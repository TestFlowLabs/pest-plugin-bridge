---
layout: home

hero:
  name: Pest Plugin Bridge
  text: Test External Frontends from Laravel
  tagline: Write browser tests in PHP for Vue, React, Nuxt, Next.js — no JavaScript test code required
  actions:
    - theme: brand
      text: Get Started
      link: /getting-started/quick-start
    - theme: alt
      text: View on GitHub
      link: https://github.com/TestFlowLabs/pest-plugin-bridge

features:
  - icon: 🌉
    title: Bridge the Gap
    details: Test frontends running on different ports or servers — all from your Laravel test suite using Pest.
    link: /getting-started/introduction
    linkText: Learn more →
  - icon: 🧪
    title: Extends Pest Browser Testing
    details: Built on top of pestphp/pest-plugin-browser. Same elegant syntax, now for external frontends.
    link: /getting-started/installation
    linkText: Installation →
  - icon: ⚡
    title: 4-Step Setup
    details: One line of code. Configure once, test everywhere.
    link: /getting-started/quick-start
    linkText: Quick start →
  - icon: 🔀
    title: Multiple Frontends
    details: Test admin panels, customer portals, and micro-frontends in a single test file.
    link: /guide/configuration#multiple-frontends
    linkText: See how →
---

<style>
.code-section {
  margin: 2rem 0;
  padding: 1.5rem;
  border-radius: 8px;
  background: var(--vp-c-bg-soft);
}
.section-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.section-icon {
  font-size: 1.5rem;
}
.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
}
.section-link {
  margin-left: auto;
  font-size: 0.9rem;
}
.section-desc {
  color: var(--vp-c-text-2);
  margin-bottom: 1rem;
}
</style>

## The Problem

Modern apps use **decoupled architectures**:

```
┌─────────────────┐         ┌─────────────────┐
│  Laravel API    │ ◄─────► │  Vue/React/Nuxt │
│  localhost:8000 │   API   │  localhost:3000 │
└─────────────────┘         └─────────────────┘
        ▲                           ▲
        │                           │
        └─── Where tests run        └─── What we need to test
```

<div class="code-section">
<div class="section-header">
  <span class="section-icon">🌉</span>
  <span class="section-title">bridge() — The Bridge</span>
  <a class="section-link" href="/guide/writing-tests">How it works →</a>
</div>
<div class="section-desc">
One method bridges your Laravel tests to any external frontend. Use familiar Pest syntax.
</div>

```php
test('user can login to external frontend', function () {
    // Create user in Laravel
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    // Test the Vue/React/Nuxt frontend running on port 3000
    $this->bridge('/login')
        ->fill('[data-testid="email-input"]', 'test@example.com')
        ->fill('[data-testid="password-input"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertPathContains('/dashboard')
        ->assertSee('Welcome');
});
```

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">🔀</span>
  <span class="section-title">Multiple Frontends in One File</span>
  <a class="section-link" href="/guide/configuration#multiple-frontends">Configuration →</a>
</div>
<div class="section-desc">
Test different frontends using <code>describe()</code> blocks. Each block targets a different URL.
</div>

```php
// tests/Pest.php - Configure all frontends once
use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:3000');           // Customer portal
Bridge::frontend('admin', 'http://localhost:3001');    // Admin dashboard
```

```php
// tests/Browser/MultiFrontendTest.php
test('customer can view products', function () {
    // Uses default frontend (localhost:3000)
    $this->bridge('/products')->assertSee('Product Catalog');
});

test('customer can add to cart', function () {
    $this->bridge('/products/1')
        ->click('[data-testid="add-to-cart"]')
        ->assertVisible('[data-testid="cart-badge"]');
});

test('admin can manage users', function () {
    // Uses admin frontend (localhost:3001)
    $this->bridge('/users', 'admin')->assertSee('User Management');
});
```

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">🎭</span>
  <span class="section-title">Debug with Headed Mode</span>
  <a class="section-link" href="/guide/best-practices">Best practices →</a>
</div>
<div class="section-desc">
See exactly what's happening. Run with <code>--headed</code> or pause mid-test with <code>debug()</code>.
</div>

```bash
# Run with visible browser
./vendor/bin/pest tests/Browser --headed
```

```php
test('debugging a complex flow', function () {
    $this->bridge('/checkout')
        ->fill('[data-testid="card-number"]', '4242424242424242')
        ->debug()  // ← Browser opens, test pauses here for inspection
        ->click('[data-testid="pay-button"]')
        ->assertSee('Payment successful');
});
```

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">🧪</span>
  <span class="section-title">Full Pest Assertion Power</span>
  <a class="section-link" href="/guide/assertions">All assertions →</a>
</div>
<div class="section-desc">
All Pest browser assertions work seamlessly. Chain them for expressive, readable tests.
</div>

```php
test('complete checkout flow', function () {
    $this->bridge('/cart')
        // Visibility assertions
        ->assertVisible('[data-testid="cart-items"]')
        ->assertNotVisible('[data-testid="empty-cart-message"]')

        // Text assertions
        ->assertSee('Shopping Cart')
        ->assertSeeIn('[data-testid="total"]', '$99.00')

        // Form interactions
        ->fill('[data-testid="coupon-input"]', 'SAVE10')
        ->click('[data-testid="apply-coupon"]')
        ->wait(1)

        // URL assertions after navigation
        ->click('[data-testid="checkout-button"]')
        ->wait(2)
        ->assertPathContains('/checkout')
        ->assertTitle('Checkout - MyStore');
});
```

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">⚙️</span>
  <span class="section-title">Simple Configuration</span>
  <a class="section-link" href="/guide/configuration">Configuration →</a>
</div>
<div class="section-desc">
One line of code. That's all you need.
</div>

```php
// Configuration with automatic server management (in tests/Pest.php)
use TestFlowLabs\PestPluginBridge\Bridge;
use TestFlowLabs\PestPluginBridge\BridgeTrait;
use Tests\TestCase;

// Simple: just URL (manual server start)
Bridge::setDefault('http://localhost:3000');

// With auto-start: servers launch automatically
uses(TestCase::class, BridgeTrait::class)
    ->beforeAll(fn () => Bridge::setDefault('http://localhost:3000')
        ->serve('npm run dev', cwd: '../frontend')
        ->readyWhen('ready|localhost'))
    ->in('Browser');
```

Cleanup is automatic — no `afterAll` needed.

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">📸</span>
  <span class="section-title">Automatic Screenshots on Failure</span>
  <a class="section-link" href="/guide/best-practices#debugging">Debugging →</a>
</div>
<div class="section-desc">
When a test fails, a screenshot is automatically captured for debugging.
</div>

```bash
./vendor/bin/pest tests/Browser/CheckoutTest.php

   FAIL  Tests\Browser\CheckoutTest
  ✕ complete checkout flow                                   3.2s
    Screenshot saved: Tests/Browser/Screenshots/checkout_flow_1703847293.png

  Tests:    1 failed
  Duration: 3.89s
```

```
Tests/Browser/Screenshots/
└── checkout_flow_1703847293.png   ← See exactly where it failed
```

</div>
