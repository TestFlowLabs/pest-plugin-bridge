# Performance Optimization

Strategies for making your browser tests faster and more efficient.

## Quick Wins

### 1. Use `networkidle` Strategically

```php
// ❌ Slow: Waits for ALL network activity to stop
->waitForEvent('networkidle')

// ✅ Faster: Wait for specific element that indicates ready state
->waitForSelector('[data-testid="dashboard-loaded"]')
```

Use `networkidle` only when you truly need all requests to complete (e.g., after page load). Prefer element-based waits for most assertions.

### 2. Parallel Test Execution

Run tests in parallel with Pest's `--parallel` flag:

```bash
# Use all available CPU cores
./vendor/bin/pest tests/Browser --parallel

# Or specify the number of processes
./vendor/bin/pest tests/Browser --parallel --processes=4
```

::: warning Parallel Test Considerations
- Each process shares the same frontend server
- Use unique data per test to avoid conflicts
- Consider database connection pooling
:::

### 3. Minimize Page Visits

```php
// ❌ Slow: Multiple page visits
test('user flow', function () {
    $this->bridge('/login')->fillAndSubmit();
    $this->bridge('/dashboard')->checkWidget();
    $this->bridge('/settings')->updateProfile();
});

// ✅ Faster: Navigate within single session
test('user flow', function () {
    $this->bridge('/login')
        ->fillLoginForm()
        ->click('[data-testid="submit"]')
        ->assertPathContains('/dashboard')
        ->click('[data-testid="settings-link"]')
        ->assertPathContains('/settings');
});
```

## Server Startup Optimization

### Cache Frontend Dependencies

In CI, cache `node_modules` to avoid reinstalling:

```yaml
- name: Cache frontend dependencies
  uses: actions/cache@v4
  with:
    path: frontend/node_modules
    key: frontend-${{ hashFiles('frontend/package-lock.json') }}

- name: Install frontend dependencies
  working-directory: frontend
  run: npm ci --prefer-offline
```

### Pre-build for Production

If your tests don't need hot reload:

```php
Bridge::add('http://localhost:3000')
    ->serve('npm run preview', cwd: '../frontend');  // Serves built files
```

Build once, serve fast:
```bash
cd frontend && npm run build
```

## Database Optimization

### Use SQLite for Speed

SQLite is significantly faster than MySQL/PostgreSQL for test databases:

```xml
<!-- phpunit.xml -->
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value="database/testing.sqlite"/>
```

### Truncate Specific Tables

If your database has many tables, truncate only what's needed:

```php
// tests/Pest.php
use Illuminate\Foundation\Testing\DatabaseTruncation;

pest()->extends(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Browser');
```

Create a custom truncation class for selective cleaning:

```php
// tests/TestCase.php
protected function truncateTables(): array
{
    return [
        'users',
        'orders',
        'sessions',
        // Only tables your browser tests touch
    ];
}
```

### Seed Once, Reset Smartly

For read-heavy tests, seed common data once:

```php
// tests/Pest.php
beforeAll(function () {
    // Seed data that all tests read but don't modify
    Artisan::call('db:seed', ['--class' => 'BrowserTestSeeder']);
});

beforeEach(function () {
    // Only reset data that tests modify
    DB::table('user_sessions')->truncate();
});
```

## Selector Optimization

### Use data-testid Attributes

Faster and more reliable than complex CSS selectors:

```php
// ❌ Slow: Complex traversal
->click('.header nav ul li:nth-child(3) a.btn-primary')

// ✅ Fast: Direct lookup
->click('[data-testid="settings-button"]')
```

### Avoid Text-based Selectors When Possible

```php
// ❌ Slower: Text matching
->click('button:has-text("Submit Order")')

// ✅ Faster: Attribute selector
->click('[data-testid="submit-order"]')
```

## Waiting Strategies

### Prefer Explicit Waits

```php
// ❌ Unreliable: Fixed delay
->wait(2000)

// ✅ Better: Wait for condition
->waitForSelector('[data-testid="results"]')

// ✅ Best: Wait for specific state
->waitForSelector('[data-testid="results"]:not(.loading)')
```

### Chain Related Actions

```php
// Actions that follow naturally don't need waits between them
$this->bridge('/checkout')
    ->typeSlowly('[data-testid="card-number"]', '4242424242424242')
    ->typeSlowly('[data-testid="expiry"]', '12/25')
    ->typeSlowly('[data-testid="cvc"]', '123')
    ->click('[data-testid="pay-button"]')
    ->waitForSelector('[data-testid="success-message"]');
```

## Test Organization

### Group by Speed

Organize tests by execution time for flexible CI:

```php
test('quick validation check', function () {
    // Fast test
})->group('smoke');

test('complete checkout flow', function () {
    // Slower, comprehensive test
})->group('regression');
```

Run fast tests first:
```bash
./vendor/bin/pest tests/Browser --group=smoke
./vendor/bin/pest tests/Browser --group=regression
```

### Skip Heavy Tests Locally

```php
test('full e2e flow', function () {
    // This test takes 30+ seconds
})->group('slow')->skip(fn () => !env('CI'), 'Skipped locally');
```

## CI-Specific Optimizations

### Use Larger Runners

If available, use larger GitHub Actions runners:

```yaml
jobs:
  browser-tests:
    runs-on: ubuntu-latest-4-cores  # 4x faster than ubuntu-latest
```

### Split Tests Across Jobs

```yaml
strategy:
  matrix:
    shard: [1, 2, 3, 4]

steps:
  - name: Run browser tests (shard ${{ matrix.shard }})
    run: |
      ./vendor/bin/pest tests/Browser \
        --parallel \
        --processes=2 \
        --group=shard${{ matrix.shard }}
```

### Fail Fast

Stop on first failure to save time:

```yaml
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser --stop-on-failure
```

## Monitoring Performance

### Track Test Duration

Add timing to your tests:

```php
beforeEach(function () {
    $this->startTime = microtime(true);
});

afterEach(function () {
    $duration = microtime(true) - $this->startTime;
    if ($duration > 10) {
        dump("Slow test: {$this->name()} took {$duration}s");
    }
});
```

### Identify Bottlenecks

Use Pest's `--profile` to find slow tests:

```bash
./vendor/bin/pest tests/Browser --profile
```

## Performance Checklist

- [ ] Using `data-testid` attributes instead of complex selectors
- [ ] Waiting for elements, not arbitrary timeouts
- [ ] Running tests in parallel where possible
- [ ] Caching npm dependencies in CI
- [ ] Using SQLite for faster database operations
- [ ] Grouping tests by speed (smoke, regression)
- [ ] Minimizing page visits within tests
- [ ] Using pre-built frontend when hot reload isn't needed
