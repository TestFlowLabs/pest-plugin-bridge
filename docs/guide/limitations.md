# Limitations & Known Issues

Understanding what Pest Plugin Bridge can and cannot do helps you choose the right tool and avoid surprises.

## Architectural Limitations

### Single Browser Engine (Chromium)

The plugin uses [pest-plugin-browser](https://github.com/pestphp/pest-plugin-browser) which currently only supports Chromium via Playwright.

**What this means**:
- No Firefox or Safari/WebKit testing
- Cross-browser compatibility must be tested separately
- Some browser-specific bugs may not be caught

**Workaround**: Use Playwright directly for cross-browser tests, or run separate browser-specific test suites.

### No Network Request Mocking

Unlike Cypress or Playwright's native API, the plugin doesn't provide request interception.

**What this means**:
- Can't mock API responses
- Can't test error states without backend changes
- Can't simulate slow networks

**Workaround**:
- Create test endpoints in Laravel for error states
- Use Laravel's HTTP fake in unit tests
- Consider Playwright's `page.route()` for advanced mocking (requires direct Playwright access)

### No Visual Regression Testing

The plugin doesn't include screenshot comparison or visual diff tools.

**What this means**:
- Can't detect unintended visual changes
- No pixel-perfect comparison
- CSS regressions may go unnoticed

**Workaround**: Integrate with visual testing tools like Percy, Chromatic, or BackstopJS.

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
- Requires frame-specific Playwright methods

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

## CI/CD Limitations

### GitHub Actions Specific

Documentation and examples focus on GitHub Actions.

**What this means**:
- GitLab CI, CircleCI, Jenkins need adaptation
- Concepts transfer but syntax differs
- Community contributions welcome for other CI systems

### No Built-in Artifact Management

Screenshot/video capture relies on pest-plugin-browser configuration.

**What this means**:
- Must configure artifact upload separately
- No automatic failure screenshots by default
- Need to set up `upload-artifact` action manually

## Comparison with Alternatives

| Feature | Pest Plugin Bridge | Laravel Dusk | Cypress | Playwright |
|---------|-------------------|--------------|---------|------------|
| External frontend | ✅ Native | ❌ No | ✅ Yes | ✅ Yes |
| PHP test code | ✅ Yes | ✅ Yes | ❌ JS only | ❌ JS/TS |
| Laravel integration | ✅ Excellent | ✅ Excellent | ⚠️ Limited | ⚠️ Limited |
| Database factories | ✅ Yes | ✅ Yes | ❌ No | ❌ No |
| Cross-browser | ❌ Chromium only | ❌ Chrome only | ✅ All | ✅ All |
| Network mocking | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Visual testing | ❌ No | ❌ No | ⚠️ Plugin | ⚠️ Plugin |
| Time-travel debug | ❌ No | ❌ No | ✅ Yes | ⚠️ Trace |
| Learning curve | ✅ Low (if know Pest) | ✅ Low | ⚠️ Medium | ⚠️ Medium |

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
pkill -f "npm run dev"
```

## Requesting Features

If a limitation is blocking your use case:

1. Check [GitHub Issues](https://github.com/TestFlowLabs/pest-plugin-bridge/issues) for existing requests
2. Open a new issue with your use case
3. Consider contributing a PR

Many limitations are by design to keep the plugin simple and focused. Complex features may be better served by using Playwright directly or complementary tools.
