# Debugging

Learn how to debug browser tests effectively using Pest's built-in debugging tools.

## Screenshots

### Manual Screenshots

Capture a screenshot at any point during a test:

```php
$this->bridge('/dashboard')
    ->screenshot(); // Uses test name as filename
```

### Full Page Screenshots

Capture the entire page, including content below the fold:

```php
$this->bridge('/long-page')
    ->screenshot(fullPage: true);
```

### Custom Filename

Specify a custom filename for the screenshot:

```php
$this->bridge('/checkout')
    ->screenshot(filename: 'checkout-step-1');
```

### Element Screenshots

Capture a specific element:

```php
$this->bridge('/dashboard')
    ->screenshotElement('[data-testid="chart"]');
```

### Screenshot Location

Screenshots are saved to:

```
tests/Browser/Screenshots/
```

## Interactive Debugging

### debug()

Pause execution and open the browser for inspection:

```php
$this->bridge('/complex-page')
    ->fill('[data-testid="email"]', 'test@example.com')
    ->debug() // Pauses here, opens browser
    ->click('[data-testid="submit"]');
```

When `debug()` is called:
1. Execution pauses
2. Browser window opens (headed mode)
3. You can inspect elements, console, network
4. Press Enter in terminal to continue

### tinker()

Open a Tinker session in the page context:

```php
$this->bridge('/dashboard')
    ->tinker();
```

This allows you to:
- Execute PHP code
- Interact with the page programmatically
- Debug state and variables

### waitForKey()

Pause execution and wait for a key press:

```php
$this->bridge('/modal')
    ->click('[data-testid="open-modal"]')
    ->waitForKey() // Pauses until you press a key
    ->assertVisible('[data-testid="modal"]');
```

## Command Line Flags

### --headed

Run tests with visible browser window:

```bash
./vendor/bin/pest tests/Browser --headed
```

Or in specific tests:

```php
$this->bridge('/')
    ->headed()
    ->assertSee('Welcome');
```

### --debug

Automatically pause on test failure:

```bash
./vendor/bin/pest tests/Browser --debug
```

When a test fails:
1. Browser window opens
2. Execution pauses at failure point
3. You can inspect the page state
4. Press Enter to continue to next test

## Automatic Failure Screenshots

When an assertion fails, Pest automatically captures a screenshot. Find them in:

```
tests/Browser/Screenshots/
```

The filename corresponds to the test name, making it easy to identify failures.

## Console & Error Inspection

### Check for Console Logs

Verify no unexpected console output:

```php
$this->bridge('/')
    ->assertNoConsoleLogs();
```

### Check for JavaScript Errors

Verify no JavaScript errors occurred:

```php
$this->bridge('/app')
    ->assertNoJavaScriptErrors();
```

### Smoke Test

Combined check for console logs and JS errors:

```php
$this->bridge('/')
    ->assertNoSmoke();
```

## Debugging Tips

### 1. Use Headed Mode for Development

During development, always run with `--headed` to see what's happening:

```bash
./vendor/bin/pest tests/Browser/LoginTest.php --headed
```

### 2. Add Strategic debug() Calls

Place `debug()` before complex interactions:

```php
$this->bridge('/checkout')
    ->fill('[data-testid="card-number"]', '4242424242424242')
    ->debug() // Check if form is filled correctly
    ->click('[data-testid="submit"]');
```

### 3. Take Screenshots at Key Points

Document the test flow with screenshots:

```php
$this->bridge('/checkout')
    ->screenshot(filename: '01-cart-loaded')
    ->click('[data-testid="checkout"]')
    ->screenshot(filename: '02-checkout-form')
    ->fill('[data-testid="email"]', 'user@example.com')
    ->screenshot(filename: '03-form-filled')
    ->click('[data-testid="submit"]')
    ->screenshot(filename: '04-confirmation');
```

### 4. Check Laravel Logs

Browser tests make real HTTP requests. Check Laravel logs for server-side errors:

```bash
tail -f storage/logs/laravel.log
```

### 5. Use assertNoSmoke() for Quality Checks

Add smoke tests to catch JavaScript errors early:

```php
test('dashboard has no errors', function () {
    $this->bridge('/dashboard')
        ->assertNoSmoke()
        ->assertSee('Dashboard');
});
```

## Visual Regression Testing

For comprehensive visual regression testing including baseline management, CI/CD integration, and handling dynamic content, see the dedicated [Visual Regression Testing](/guide/visual-regression) guide.
