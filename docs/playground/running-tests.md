# Running Playground Tests

The playground includes browser tests that demonstrate real-world testing scenarios with Pest Plugin Bridge.

## Automatic Server Management

Pest Plugin Bridge automatically manages both servers:

1. **Laravel API** - Started automatically by pest-plugin-browser (in-process via amphp)
2. **Nuxt Frontend** - Started automatically via `->serve()` configuration

**No manual server startup required!**

## Running Tests

Simply run the tests from the playground's Laravel API directory:

```bash
cd playground/laravel-api
./vendor/bin/pest tests/Browser
```

That's it! The servers start automatically when the first test runs.

## Test File Location

The playground tests are in:

```
playground/laravel-api/tests/Browser/AuthTest.php
```

## Test Scenarios

The playground tests cover a complete authentication flow:

### 1. Home Page

```php
test('home page shows login link when not authenticated', function () {
    $this->bridge('/')
        ->assertSee('Welcome')
        ->assertSee('Please login to continue')
        ->assertVisible('[data-testid="login-link"]');
});
```

### 2. Navigation

```php
test('user can navigate to login page', function () {
    $this->bridge('/')
        ->click('[data-testid="login-link"]')
        ->wait(1)
        ->assertPathContains('/login')
        ->assertVisible('[data-testid="login-form"]');
});
```

### 3. Login Form

```php
test('login page shows form elements', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->assertVisible('[data-testid="password-input"]')
        ->assertVisible('[data-testid="login-button"]');
});
```

### 4. Successful Login

```php
test('user can login with valid credentials', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->fill('[data-testid="email-input"]', 'test@example.com')
        ->wait(0.3)
        ->fill('[data-testid="password-input"]', 'password')
        ->wait(0.3)
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertPathContains('/dashboard')
        ->assertVisible('[data-testid="dashboard-page"]')
        ->assertSee('Test User');
});
```

### 5. Invalid Credentials

```php
test('user sees error with invalid credentials', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->fill('[data-testid="email-input"]', 'wrong@example.com')
        ->wait(0.3)
        ->fill('[data-testid="password-input"]', 'wrongpassword')
        ->wait(0.3)
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertVisible('[data-testid="login-error"]');
});
```

### 6. Protected Routes

```php
test('authenticated user can access dashboard', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->fill('[data-testid="email-input"]', 'test@example.com')
        ->wait(0.3)
        ->fill('[data-testid="password-input"]', 'password')
        ->wait(0.3)
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertVisible('[data-testid="user-name"]')
        ->assertSee('Test User')
        ->assertVisible('[data-testid="user-email"]')
        ->assertSee('test@example.com');
});
```

### 7. Logout

```php
test('user can logout from dashboard', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->fill('[data-testid="email-input"]', 'test@example.com')
        ->wait(0.3)
        ->fill('[data-testid="password-input"]', 'password')
        ->wait(0.3)
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertVisible('[data-testid="logout-button"]')
        ->click('[data-testid="logout-button"]')
        ->wait(2)
        ->assertPathContains('/login');
});
```

### 8. Redirect Unauthenticated

```php
test('unauthenticated user is redirected from dashboard', function () {
    $this->bridge('/dashboard')
        ->wait(2)
        ->assertPathContains('/login');
});
```

## Running Specific Tests

### Run All Browser Tests

```bash
cd playground/laravel-api
./vendor/bin/pest tests/Browser
```

### Run Single Test

```bash
./vendor/bin/pest --filter="user can login"
```

### Run in Headed Mode

```bash
./vendor/bin/pest tests/Browser --headed
```

### Run with Debug Pauses

Add `->debug()` to pause execution:

```php
test('debugging login', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email-input"]', 'test@example.com')
        ->debug() // Pauses here for inspection
        ->fill('[data-testid="password-input"]', 'password')
        ->click('[data-testid="login-button"]');
});
```

## Debugging Tips

### View Browser Console

Run in headed mode to see the browser:

```bash
./vendor/bin/pest tests/Browser --headed
```

### Check Screenshots

Failed tests generate screenshots:

```
Tests/Browser/Screenshots/
```

### Check Server Logs

Laravel logs:
```bash
tail -f playground/laravel-api/storage/logs/laravel.log
```

### Reset Test Data

Before running tests, ensure clean database state:

```bash
cd playground/laravel-api
php artisan migrate:fresh --seed
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Playground Tests

on: [push, pull_request]

jobs:
  test:
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

      - name: Install Playwright
        run: |
          npm install playwright
          npx playwright install chromium
          npx playwright install-deps chromium

      - name: Install PHP dependencies
        run: composer install

      - name: Setup Laravel API
        run: |
          cd playground/laravel-api
          composer install
          cp .env.example .env
          php artisan key:generate
          php artisan migrate --seed

      - name: Setup Nuxt App
        run: |
          cd playground/nuxt-app
          npm install

      - name: Run tests
        run: |
          cd playground/laravel-api
          ./vendor/bin/pest tests/Browser
```

## Summary

| Command | Description |
|---------|-------------|
| `cd playground/laravel-api && ./vendor/bin/pest tests/Browser` | Run all playground tests |
| `./vendor/bin/pest --filter="test name"` | Run specific test |
| `./vendor/bin/pest tests/Browser --headed` | Run with visible browser |

The playground demonstrates how Pest Plugin Bridge enables testing real Laravel + Nuxt applications with full browser automation and automatic server management.
