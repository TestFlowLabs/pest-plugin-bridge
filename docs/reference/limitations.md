# Limitations & Known Issues

Understanding what Pest Bridge Plugin can and cannot do helps you choose the right tool and avoid surprises.

## Database Limitations

### Transaction Isolation

Laravel's `RefreshDatabase` trait wraps tests in database transactions. These transactions are **not visible** to the external API process.

**What this means**:
- `RefreshDatabase` **does not work** with browser tests
- Data created in tests won't be seen by the frontend
- Must use `DatabaseTruncation` or `DatabaseMigrations`

**Required setup**:
```php
// tests/Pest.php
pest()->extends(TestCase::class)
    ->use(DatabaseTruncation::class)  // Not RefreshDatabase!
    ->in('Browser');
```

### SQLite In-Memory Not Supported

Each database connection gets its own isolated `:memory:` database.

**What this means**:
- Test process and API process have different databases
- Data is invisible across processes
- Must use file-based SQLite or real database

**Required setup**:
```xml
<!-- phpunit.xml -->
<env name="DB_DATABASE" value="database/database.sqlite"/>
```

## Frontend Interaction Limitations

### Reactive Framework Gotchas

Vue's `v-model`, React's `useState`, and similar reactive bindings don't see direct DOM manipulation.

**What this means**:
- `fill()` may not trigger reactivity
- Form validation may not fire
- Computed values may not update

**Solution**: Always use `typeSlowly()` for reactive forms:
```php
// This triggers real keyboard events
->typeSlowly('[data-testid="email"]', 'test@example.com')
```

### Shadow DOM Limited Support

Testing inside Shadow DOM (Web Components, certain UI libraries) has limitations.

**What this means**:
- Can't easily query elements inside shadow roots
- Some selectors won't work
- May need workarounds for component libraries using Shadow DOM

### iframe Navigation

Testing content inside iframes requires additional handling.

**What this means**:
- Can't directly interact with iframe content using standard selectors
- Payment forms (Stripe, PayPal) often use iframes
- Use `withinIframe()` method for iframe interactions

**Example**:
```php
$this->bridge('/checkout')
    ->withinIframe('[data-testid="payment-frame"]', function ($page) {
        $page->fill('[data-testid="card-number"]', '4242424242424242');
    });
```

## Server Management Limitations

### Single Instance Per Frontend

Each configured frontend runs one server instance.

**What this means**:
- Can't run multiple instances of same frontend
- Parallel tests share the same frontend server
- Test isolation depends on proper state reset

### Process Cleanup on Windows

Windows doesn't support SIGTERM signals the same way Unix does.

**What this means**:
- Server shutdown may be less graceful on Windows
- Orphaned processes possible in some edge cases
- May need manual cleanup: `taskkill /IM node.exe /F`

### Server Startup Timeout

Default server startup uses pattern matching with no explicit timeout.

**What this means**:
- Slow servers may cause test hangs
- No built-in timeout configuration
- Must ensure your `readyWhen()` pattern is reliable

## CI/CD Considerations

### Documentation Examples

Our CI/CD documentation uses GitHub Actions for examples, but **the plugin works with any CI/CD platform**:

- GitHub Actions
- GitLab CI/CD
- CircleCI
- Jenkins
- Bitbucket Pipelines
- Azure DevOps
- Any platform that can run PHP and Node.js

The core concepts (checkout repos, install dependencies, run Playwright, execute tests) apply universally. Only the YAML/config syntax differs between platforms.

### Artifact Upload

Screenshot and video capture is handled by pest-plugin-browser. Uploading these artifacts to your CI/CD platform requires platform-specific configuration:

```yaml
# GitHub Actions
- uses: actions/upload-artifact@v4
  with:
    name: screenshots
    path: tests/Browser/Screenshots

# GitLab CI
artifacts:
  paths:
    - tests/Browser/Screenshots

# CircleCI
- store_artifacts:
    path: tests/Browser/Screenshots
```

## Known Issues

### First Characters Lost in Input

When typing immediately after page load, first few characters may be lost.

**Status**: Known limitation of browser automation timing

**Workaround**:
```php
->waitForEvent('networkidle')
->click('input#email')  // Focus first
->typeSlowly('input#email', 'test@example.com', 30)
```

### Parallel Test Data Conflicts

Tests running in parallel may conflict if they use the same data.

**Status**: Expected behavior with shared database

**Workaround**: Use unique identifiers:
```php
$email = 'test-' . Str::uuid() . '@example.com';
```

### Server Process Orphaning

If tests crash hard, frontend servers may be left running.

**Status**: Edge case, usually handles gracefully

**Workaround**: Kill orphaned processes manually:
```bash
# Unix/macOS
pkill -f "npm run dev"

# Windows
taskkill /IM node.exe /F
```

## What This Plugin Is NOT

To set proper expectations, here's what the plugin doesn't aim to replace:

| If you need... | Consider... |
|----------------|-------------|
| Time-travel debugging | Cypress |
| Full Playwright API access | Playwright directly |
| Component testing | Vitest, Jest, Vue Test Utils |
| API-only testing | Laravel's HTTP tests |
| Load/performance testing | k6, Artillery, Locust |

## Requesting Features

If a limitation is blocking your use case:

1. Check [GitHub Issues](https://github.com/TestFlowLabs/pest-plugin-bridge/issues) for existing requests
2. Open a new issue with your use case
3. Consider contributing a PR

Many limitations are by design to keep the plugin simple and focused. Complex features may be better served by using Playwright directly or complementary tools.
