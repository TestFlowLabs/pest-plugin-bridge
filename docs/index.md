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
---

<div class="feature-sections">

<div class="feature-section">
<div class="feature-text">

## bridge() to External Frontends

**One method, full access.** Create test data in Laravel, test the UI in Vue/React/Nuxt. All with familiar Pest syntax.

No JavaScript test files. No separate test runners. Just PHP.

[Get started →](/getting-started/quick-start)

</div>
<div class="feature-code">

```php
test('user can login', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->bridge('/login')
        ->typeSlowly('[data-testid="email"]', 'test@example.com')
        ->typeSlowly('[data-testid="password"]', 'password')
        ->click('[data-testid="login-button"]')
        ->waitForEvent('networkidle')
        ->assertPathContains('/dashboard')
        ->assertSee('Welcome');
});
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## Automatic Server Management

**No manual server start.** Frontend starts on first `bridge()` call, stops when tests complete.

API URL automatically injected for Vite, Nuxt, Next.js, and React.

[Configuration →](/guide/configuration)

</div>
<div class="feature-code">

```php
// tests/Pest.php
use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('Local:.*http');

// That's it! Frontend starts automatically.
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## Multi-Repository CI/CD

**Separate repos? No problem.** GitHub Actions checks out both repositories side-by-side.

Tests run from Laravel, frontend served automatically. Works with private repos too.

[Multi-repo setup →](/ci-cd/multi-repo)

</div>
<div class="feature-code">

```yaml
steps:
  - name: Checkout API
    uses: actions/checkout@v4
    with:
      path: backend

  - name: Checkout Frontend
    uses: actions/checkout@v4
    with:
      repository: your-org/frontend-repo
      path: frontend
```

```php
// tests/Pest.php
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## Manual Triggers with Branch Selection

**QA-ready workflows.** Trigger tests manually from GitHub UI or `gh` CLI.

Select branches for both frontend and backend. Choose test groups to run.

[Manual triggers →](/ci-cd/manual-trigger)

</div>
<div class="feature-code">

```bash
# Trigger with specific branches and test group
gh workflow run browser-tests.yml \
  -f backend_branch=feature/payment \
  -f frontend_branch=develop \
  -f test_group=smoke
```

```yaml
workflow_dispatch:
  inputs:
    backend_branch:
      description: 'Backend branch'
      default: 'develop'
    test_group:
      type: choice
      options: [all, smoke, critical]
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## Multiple Frontends

**Admin panel + Customer portal + Mobile?** Test them all in one test suite with named instances.

Each frontend gets its own port and server command.

[Multiple frontends →](/ci-cd/multi-repo#multiple-frontends)

</div>
<div class="feature-code">

```php
// tests/Pest.php
Bridge::setDefault('http://localhost:3000');
Bridge::make('admin', 'http://localhost:3001')
    ->serve('npm run dev -- --port 3001', cwd: '../admin');
```

```php
test('customer views products', function () {
    $this->bridge('/products')->assertSee('Catalog');
});

test('admin manages users', function () {
    $this->bridge('/users', 'admin')->assertSee('User Management');
});
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## Vue/React Compatible

**Reactive frameworks just work.** `typeSlowly()` triggers real keyboard events that Vue v-model and React hooks respond to.

Works with Vue, Nuxt, React, Next.js, Angular, Svelte.

[Best practices →](/guide/best-practices)

</div>
<div class="feature-code">

```php
// fill() sets DOM value directly — Vue v-model won't see it
->fill('[data-testid="email"]', 'test@example.com')

// typeSlowly() triggers keydown/input/keyup events
->typeSlowly('[data-testid="email"]', 'test@example.com')
```

```php
test('form validation works', function () {
    $this->bridge('/register')
        ->typeSlowly('[data-testid="email"]', 'invalid')
        ->click('body')  // blur triggers validation
        ->assertSee('Invalid email format');
});
```

</div>
</div>

</div>
