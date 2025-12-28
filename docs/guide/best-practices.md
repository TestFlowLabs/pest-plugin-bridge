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

## Summary

| Practice | Why |
|----------|-----|
| Use `data-testid` | Stable selectors that don't break with UI changes |
| Wait strategically | Avoid flaky tests from race conditions |
| Reset state | Ensure test isolation |
| Group tests | Better organization and selective running |
| Debug with headed mode | See what's happening |
| Consider CI | Ensure tests work in headless mode |
