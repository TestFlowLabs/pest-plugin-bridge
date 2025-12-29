# Quick Start

Get your first external frontend test running in 4 steps.

## The 4-Step Setup

### Step 1: Install the Plugin

```bash
composer require testflowlabs/pest-plugin-bridge --dev
```

### Step 2: Install Playwright

```bash
npm install playwright
npx playwright install chromium
```

### Step 3: Configure in tests/Pest.php

```php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;
use Tests\TestCase;

// Option A: Manual server start (you start the frontend separately)
Bridge::add('http://localhost:3000');

// Option B: Automatic server management (recommended)
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('ready|localhost');

pest()->extends(TestCase::class)->in('Browser');
```

### Step 4: Create and Run a Test

Create `tests/Browser/ExternalTest.php`:

```php
<?php

test('frontend homepage loads', function () {
    $this->bridge('/')
        ->assertSee('Welcome');
});

test('login page has form', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->assertVisible('[data-testid="password-input"]')
        ->assertVisible('[data-testid="login-button"]');
});
```

Run the tests:

```bash
# With automatic server management, just run tests!
./vendor/bin/pest tests/Browser

# Or if using manual server start:
# Terminal 1: npm run dev
# Terminal 2: ./vendor/bin/pest tests/Browser
```

**That's it!** You're now testing your external frontend from Laravel.

---

## Quick Reference

### Common Actions

```php
$this->bridge('/path')            // Navigate to page
    ->fill('selector', 'value')   // Fill input
    ->click('selector')           // Click element
    ->wait(1)                     // Wait 1 second
    ->assertSee('text')           // Assert text visible
    ->assertVisible('selector')   // Assert element visible
    ->assertPathContains('/path') // Assert URL contains path
```

### Using data-testid (Recommended)

Add test IDs to your frontend:

```html
<input data-testid="email-input" type="email" />
<button data-testid="login-button">Login</button>
```

Use in tests:

```php
$this->bridge('/login')
    ->fill('[data-testid="email-input"]', 'user@example.com')
    ->click('[data-testid="login-button"]');
```

### Debug Mode

See what's happening in the browser:

```bash
./vendor/bin/pest tests/Browser --headed
```

Pause test execution:

```php
$this->bridge('/login')
    ->debug()  // Pauses here for inspection
    ->click('[data-testid="login-button"]');
```

---

## Complete Example

Test a login flow with Laravel backend:

```php
<?php

use App\Models\User;

test('user can login', function () {
    // Create test user in Laravel
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    // Test the frontend
    $this->bridge('/login')
        ->fill('[data-testid="email-input"]', 'test@example.com')
        ->fill('[data-testid="password-input"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertPathContains('/dashboard')
        ->assertSee('Welcome');
});
```

