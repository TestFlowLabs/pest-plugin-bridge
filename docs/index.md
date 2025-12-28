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
    title: Bridge to External Frontends
    details: Test frontends running on different ports or servers — all from your Laravel test suite using Pest's elegant syntax.
    link: /getting-started/introduction
    linkText: Learn more →
  - icon: 🚀
    title: Automatic Server Management
    details: Frontend servers start automatically on first test, stop when done. API URL injected for all frameworks.
    link: /guide/configuration#automatic-server-management
    linkText: See how →
  - icon: 🔀
    title: Multiple Frontends
    details: Test admin panels, customer portals, and micro-frontends in a single test file with named frontends.
    link: /guide/configuration#multiple-frontends
    linkText: Configure →
  - icon: 🎯
    title: Vue/React Ready
    details: Built-in patterns for Vue v-model, React hooks, and all reactive frameworks. typeSlowly() triggers proper events.
    link: /guide/best-practices#vue-nuxt-framework-specific-best-practices
    linkText: Best practices →
---

<script setup>
import { VPTeamMembers } from 'vitepress/theme'
</script>

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

<div class="code-section">
<div class="section-header">
  <span class="section-icon">🌉</span>
  <span class="section-title">bridge() — One Method, Full Access</span>
  <a class="section-link" href="/guide/writing-tests">How it works →</a>
</div>
<div class="section-desc">
Test any external frontend with familiar Pest syntax. Create data in Laravel, test the UI in Vue/React/Nuxt.
</div>

```php
test('user can login', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->bridge('/login')
        ->typeSlowly('[data-testid="email"]', 'test@example.com', 20)
        ->typeSlowly('[data-testid="password"]', 'password', 20)
        ->click('[data-testid="login-button"]')
        ->waitForEvent('networkidle')
        ->assertPathContains('/dashboard')
        ->assertSee('Welcome');
});
```

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">🚀</span>
  <span class="section-title">Automatic Frontend Server Management</span>
  <a class="section-link" href="/guide/configuration#automatic-server-management">Configuration →</a>
</div>
<div class="section-desc">
No manual server start. Frontend starts on first bridge() call, stops when tests complete. API URL auto-injected.
</div>

```php
// tests/Pest.php
use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('Local:.*http');

// That's it! Frontend starts automatically when tests run.
```

**Auto-injected environment variables:**
- `VITE_API_URL`, `NUXT_PUBLIC_API_BASE`, `NEXT_PUBLIC_API_URL`, `REACT_APP_API_URL`

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">🔀</span>
  <span class="section-title">Multiple Frontends</span>
  <a class="section-link" href="/guide/configuration#multiple-frontends">See how →</a>
</div>
<div class="section-desc">
Test admin panels, customer portals, and micro-frontends in a single test file.
</div>

```php
// tests/Pest.php
Bridge::setDefault('http://localhost:3000');           // Customer portal
Bridge::frontend('admin', 'http://localhost:3001')     // Admin dashboard
    ->serve('npm run dev', cwd: '../admin-panel');
```

```php
// tests/Browser/MultiFrontendTest.php
test('customer can view products', function () {
    $this->bridge('/products')->assertSee('Product Catalog');
});

test('admin can manage users', function () {
    $this->bridge('/users', 'admin')->assertSee('User Management');
});
```

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">🎯</span>
  <span class="section-title">Vue/React Compatible</span>
  <a class="section-link" href="/guide/best-practices#vue-nuxt-framework-specific-best-practices">Best practices →</a>
</div>
<div class="section-desc">
Playwright's fill() doesn't trigger Vue v-model events. typeSlowly() solves this by simulating real keystrokes.
</div>

```php
// ❌ fill() sets DOM value directly — Vue v-model won't see it
->fill('[data-testid="email"]', 'test@example.com')

// ✅ typeSlowly() triggers keydown/input/keyup — Vue reactivity works
->typeSlowly('[data-testid="email"]', 'test@example.com', 20)
```

**Works with:** Vue, Nuxt, React, Next.js, Angular, Svelte — any reactive framework.

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">🎭</span>
  <span class="section-title">Debug with Headed Mode</span>
  <a class="section-link" href="/guide/best-practices#debug-effectively">Debugging →</a>
</div>
<div class="section-desc">
See exactly what's happening. Run with --headed or pause mid-test with debug().
</div>

```bash
# Run with visible browser
./vendor/bin/pest tests/Browser --headed
```

```php
test('debugging a complex flow', function () {
    $this->bridge('/checkout')
        ->fill('[data-testid="card"]', '4242424242424242')
        ->debug()  // ← Browser opens, test pauses here
        ->click('[data-testid="pay-button"]')
        ->assertSee('Payment successful');
});
```

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">📸</span>
  <span class="section-title">Automatic Screenshots on Failure</span>
  <a class="section-link" href="/guide/best-practices#check-screenshots">Screenshots →</a>
</div>
<div class="section-desc">
When a test fails, a screenshot is automatically captured. See exactly where it went wrong.
</div>

```bash
   FAIL  Tests\Browser\CheckoutTest
  ✕ complete checkout flow
    Screenshot saved: Tests/Browser/Screenshots/complete_checkout_flow.png
```

</div>

<div class="code-section">
<div class="section-header">
  <span class="section-icon">⚡</span>
  <span class="section-title">All Pest Assertions</span>
  <a class="section-link" href="/guide/assertions">All assertions →</a>
</div>
<div class="section-desc">
All Pest browser assertions work seamlessly. Chain them for expressive, readable tests.
</div>

```php
test('complete checkout', function () {
    $this->bridge('/cart')
        ->assertVisible('[data-testid="cart-items"]')
        ->assertSee('Shopping Cart')
        ->assertSeeIn('[data-testid="total"]', '$99.00')
        ->click('[data-testid="checkout-button"]')
        ->waitForEvent('networkidle')
        ->assertPathContains('/checkout');
});
```

</div>
