# Best Practices

Follow these best practices to write maintainable, reliable browser tests.

## Use Data Test IDs

**Don't** rely on CSS classes or element structure:

```php
// ❌ Bad - brittle, breaks when styling changes
$this->bridge('/login')
    ->fill('.form-control.email-field', 'user@example.com')
    ->click('.btn.btn-primary.submit-btn');

// ❌ Bad - relies on DOM structure
$this->bridge('/login')
    ->fill('form > div:nth-child(2) > input', 'user@example.com');
```

**Do** use `data-testid` attributes:

```php
// ✅ Good - explicit test selectors
$this->bridge('/login')
    ->fill('[data-testid="email-input"]', 'user@example.com')
    ->click('[data-testid="login-button"]');
```

Add these attributes in your frontend:

```html
<input data-testid="email-input" type="email" class="form-control" />
<button data-testid="login-button" type="submit">Login</button>
```

## Wait Strategically

### After Form Submissions

```php
test('login redirects to dashboard', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2) // Wait for API call + redirect
        ->assertPathContains('/dashboard');
});
```

### Between Sequential Fills

For apps that validate on blur:

```php
test('form validation', function () {
    $this->bridge('/register')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->wait(0.3) // Allow validation to trigger
        ->fill('[data-testid="password"]', 'password')
        ->wait(0.3)
        ->click('[data-testid="submit"]');
});
```

### Use assertVisible for Dynamic Content

```php
// ✅ assertVisible waits for element to appear
$this->bridge('/dashboard')
    ->assertVisible('[data-testid="user-data"]');

// Instead of:
// ❌ wait(2)->assertSee('...')
```

## Test Isolation

### Automatic Cleanup

The plugin automatically resets Bridge configuration when tests complete. No manual cleanup needed!

### Clear Browser State

Each test gets a fresh browser context, but you may need to clear app state:

```php
beforeEach(function () {
    // Clear database
    $this->artisan('migrate:fresh');

    // Seed test data
    $this->seed(TestSeeder::class);
});
```

## Organize Tests

### Group Related Tests

```php
describe('Authentication', function () {
    test('user can login', function () { /* ... */ });
    test('user can logout', function () { /* ... */ });
    test('user can reset password', function () { /* ... */ });
});

describe('Shopping Cart', function () {
    test('user can add item', function () { /* ... */ });
    test('user can remove item', function () { /* ... */ });
    test('user can checkout', function () { /* ... */ });
});
```

### Use Test Groups

```php
test('admin can manage users', function () {
    // ...
})->group('admin', 'browser');

// Run specific groups
// ./vendor/bin/pest --group=admin
```

## Debug Effectively

### Use Headed Mode

```bash
./vendor/bin/pest tests/Browser --headed
```

### Use debug()

```php
test('debugging', function () {
    $this->bridge('/complex-page')
        ->debug() // Pauses for inspection
        ->click('[data-testid="submit"]');
});
```

### Check Screenshots

Failed tests create screenshots in `Tests/Browser/Screenshots/`.

## CI/CD Considerations

### GitHub Actions Example

```yaml
name: Browser Tests

on: [push, pull_request]

jobs:
  browser-tests:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install PHP dependencies
        run: composer install

      - name: Install Playwright
        run: |
          npm install playwright
          npx playwright install chromium
          npx playwright install-deps chromium

      - name: Install frontend dependencies
        run: cd frontend && npm install

      - name: Run tests
        run: ./vendor/bin/pest --group=browser
```

::: tip Automatic Server Management
With `->serve()` configuration, you don't need to manually start the frontend in CI. The plugin handles it automatically!
:::

### Headless by Default

Tests run headless in CI. Only use `--headed` locally:

```bash
# Local development
./vendor/bin/pest tests/Browser --headed

# CI (default headless)
./vendor/bin/pest tests/Browser
```

## Performance Tips

### Minimize Waits

```php
// ❌ Arbitrary waits
->wait(5)

// ✅ Wait for specific element
->assertVisible('[data-testid="content"]')
```

### Parallel Test Execution

```bash
./vendor/bin/pest --parallel
```

### Skip Slow Tests Locally

```php
test('slow integration test', function () {
    // ...
})->skip(fn () => !env('CI'), 'Skipping slow test locally');
```

## Error Handling

### Test Error States

```php
test('shows validation errors', function () {
    $this->bridge('/register')
        ->fill('[data-testid="email"]', 'invalid-email')
        ->click('[data-testid="submit"]')
        ->assertVisible('[data-testid="email-error"]')
        ->assertSee('Please enter a valid email');
});
```

### Test Loading States

```php
test('shows loading indicator', function () {
    $this->bridge('/dashboard')
        ->click('[data-testid="refresh"]')
        ->assertVisible('[data-testid="loading-spinner"]')
        ->wait(2)
        ->assertNotVisible('[data-testid="loading-spinner"]');
});
```

## Vue/Nuxt Framework-Specific Best Practices

When testing Vue, Nuxt, or other reactive frontends, follow these additional guidelines.

### Understanding the `fill()` vs `typeSlowly()` Problem

::: warning Why This Matters
This is not a bug — it's a fundamental difference in how Playwright methods interact with JavaScript frameworks.
:::

**Playwright has two approaches for entering text:**

| Method | Playwright Equivalent | What It Does | Framework Reactivity |
|--------|----------------------|--------------|---------------------|
| `fill()` | `locator.fill()` | Sets DOM `value` property directly | ❌ No events fired |
| `typeSlowly()` | `locator.pressSequentially()` | Simulates keydown → input → keyup for each character | ✅ Full event chain |

**Vue's `v-model` listens for `input` events** to update its reactive state. When `fill()` sets the DOM value directly, no `input` event fires, so Vue never sees the change.

```
fill('test')           → DOM: value="test"  → Vue state: ""     ❌
typeSlowly('test', 20) → DOM: value="test"  → Vue state: "test" ✅
                          ↑ keydown/input/keyup events fired
```

This applies to **all reactive frameworks**: Vue, React, Angular, Svelte — any framework that relies on input events for data binding.

### Use `typeSlowly()` Instead of `fill()` for Reactive Forms

Vue's `v-model` directive doesn't sync with Playwright's `fill()` method because it sets the DOM value directly without triggering proper input events.

```php
// ❌ Bad - Vue v-model won't see the value
$this->bridge('/login')
    ->fill('[data-testid="email"]', 'user@example.com')
    ->click('[data-testid="login-button"]');

// ✅ Good - Triggers proper input events for Vue reactivity
$this->bridge('/login')
    ->typeSlowly('[data-testid="email"]', 'user@example.com', 20)
    ->typeSlowly('[data-testid="password"]', 'password', 20)
    ->click('[data-testid="login-button"]');
```

### Click First Input Before Typing

There's a timing issue where the first few characters can get lost when typing immediately after page load:

```php
// ❌ Bad - First characters may be lost
$this->bridge('/register')
    ->typeSlowly('input#name', 'TestUser', 30);  // Might become "User"

// ✅ Good - Click focuses the input and ensures readiness
$this->bridge('/register')
    ->waitForEvent('networkidle')
    ->click('input#name')           // Focus first
    ->typeSlowly('input#name', 'TestUser', 30);  // Full "TestUser"
```

### Use `waitForEvent('networkidle')` for API Calls

Instead of arbitrary waits, use network idle to wait for API calls to complete:

```php
// ❌ Bad - Arbitrary wait may be too short or too long
->click('[data-testid="submit"]')
->wait(3)
->assertPathContains('/dashboard');

// ✅ Good - Waits until network is idle
->click('[data-testid="submit"]')
->waitForEvent('networkidle')
->assertPathContains('/dashboard');
```

### Don't Use RefreshDatabase Trait

The `RefreshDatabase` trait wraps tests in a database transaction, which creates isolation that prevents seeing changes made by the external server:

```php
// tests/Pest.php

// ❌ Bad - External server writes won't be visible
pest()->extends(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

// ✅ Good - No transaction isolation
pest()->extends(TestCase::class)
    ->in('Browser');
```

::: tip Database Configuration Guide
For comprehensive coverage of database traits, transaction isolation, and recommended configurations, see [How It Works: Database Configuration](/learn/how-it-works#database-configuration).
:::

### Verify Results Via UI Instead of Database

Since database isolation doesn't work with external servers, verify results through UI assertions:

```php
// ❌ Bad - Database check won't see external server's writes
expect(User::where('email', $email)->exists())->toBeTrue();

// ✅ Good - Verify via UI
->assertPathContains('/dashboard')
->assertSee('Welcome')
->assertSee($email);
```

### Use Unique Test Data

Without database refresh, use timestamps or unique IDs to avoid conflicts:

```php
// ❌ Bad - May conflict with previous test runs
$email = 'test@example.com';

// ✅ Good - Unique for each test run
$email = 'test'.time().'@example.com';
```

### Complete Working Pattern for Vue/Nuxt

```php
it('can register a new user', function () {
    $email = 'register'.time().'@example.com';

    $this->bridge('/register')
        ->waitForEvent('networkidle')     // Wait for page load
        ->click('input#name')              // Focus first input
        ->typeSlowly('input#name', 'NewUser', 30)
        ->typeSlowly('input#email', $email, 20)
        ->typeSlowly('input#password', 'password123', 20)
        ->typeSlowly('input#password_confirmation', 'password123', 20)
        ->click('button[type="submit"]')
        ->waitForEvent('networkidle')     // Wait for API call
        ->assertPathContains('/dashboard')
        ->assertSee('Welcome');
});
```

## Troubleshooting Common Issues

### Form Submits Empty Values
- **Cause:** Vue's v-model not syncing with `fill()`
- **Solution:** Use `typeSlowly()` instead

### First Characters Lost When Typing
- **Cause:** Page not fully ready for input
- **Solution:** Add `->click('input#field')` before first `typeSlowly()`

### Database Assertions Fail But UI Shows Success
- **Cause:** RefreshDatabase transaction isolation
- **Solution:** Don't use RefreshDatabase; use UI assertions

### CSRF Token Mismatch (419 Error)
- **Cause:** Laravel's `statefulApi()` middleware
- **Solution:** Remove from `bootstrap/app.php` for token-based auth

### Tests Hang or Timeout
- **Cause:** Waiting for something that never happens
- **Solution:** Use `waitForEvent('networkidle')` instead of fixed `wait()`

## Summary

| Practice | Why |
|----------|-----|
| Use `data-testid` | Stable selectors that don't break with UI changes |
| Wait strategically | Avoid flaky tests from race conditions |
| Reset state | Ensure test isolation |
| Group tests | Better organization and selective running |
| Debug with headed mode | See what's happening |
| Consider CI | Ensure tests work in headless mode |
| Use `typeSlowly()` for Vue | Vue's v-model needs proper input events |
| Click before first type | Prevents character loss on page load |
| Use `waitForEvent('networkidle')` | Better than arbitrary waits for API calls |
| Skip RefreshDatabase | Transaction isolation breaks external server tests |
