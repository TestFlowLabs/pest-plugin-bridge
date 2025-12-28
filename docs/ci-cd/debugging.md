# Debugging

When browser tests fail in CI, debugging can be challenging. This page covers strategies for capturing failure information.

## Screenshot Artifacts

Capture screenshots on test failure and upload them as artifacts:

### Upload Snippet

```yaml
- name: Upload screenshots on failure
  if: failure()
  uses: actions/upload-artifact@v4
  with:
    name: browser-screenshots
    path: tests/Browser/screenshots/
    retention-days: 7
```

### Taking Screenshots in Tests

```php
test('user can login', function () {
    $this->bridge('/login')
        ->typeSlowly('[data-testid="email"]', 'test@example.com', 20)
        ->typeSlowly('[data-testid="password"]', 'password', 20)
        ->click('[data-testid="submit"]')
        ->screenshot('after-login-click')  // Saved to tests/Browser/screenshots/
        ->assertPathContains('/dashboard');
});
```

### Downloading Artifacts

After a failed run:

1. Go to the workflow run page
2. Scroll to "Artifacts" section
3. Download `browser-screenshots`

## HTML Snapshots

Capture page HTML for debugging:

```php
test('page renders correctly', function () {
    $this->bridge('/dashboard')
        ->storeHtml('dashboard-page')  // Saved to tests/Browser/html/
        ->assertVisible('[data-testid="stats"]');
});
```

```yaml
- name: Upload HTML snapshots on failure
  if: failure()
  uses: actions/upload-artifact@v4
  with:
    name: browser-html
    path: tests/Browser/html/
    retention-days: 7
```

## Console Logs

Capture browser console logs:

```php
test('no console errors', function () {
    $this->bridge('/dashboard')
        ->storeConsoleLog('dashboard-console')
        ->assertVisible('[data-testid="content"]');
});
```

## Common CI Failures

### 1. Frontend Server Not Starting

**Symptom:** Tests timeout waiting for frontend

**Debug:**
```yaml
- name: Check frontend directory
  run: ls -la frontend/

- name: Check frontend package.json
  run: cat frontend/package.json
```

**Solutions:**
- Verify `cwd` path matches checkout location
- Check `npm ci` completed successfully
- Ensure `package.json` has a `dev` script

### 2. Database Not Visible

**Symptom:** Tests create data but API returns empty

**Debug:**
```yaml
- name: Check database
  run: |
    php artisan tinker --execute="echo \App\Models\User::count();"
```

**Solutions:**
- Use `DatabaseTruncation` instead of `RefreshDatabase`
- Use file-based SQLite, not `:memory:`
- Check `phpunit.xml` has correct `DB_DATABASE`

### 3. Port Conflicts

**Symptom:** "Port already in use" errors

**Solution:** The plugin uses dynamic port discovery. Don't hardcode ports.

### 4. Playwright Browser Missing

**Symptom:** "Executable doesn't exist" or browser launch failures

**Debug:**
```yaml
- name: List Playwright browsers
  run: npx playwright install --dry-run
```

**Solution:** Ensure installation order:
```yaml
- run: npm ci
- run: npx playwright install --with-deps chromium
```

### 5. Timeout Issues

**Symptom:** Tests pass locally but timeout in CI

**Solutions:**
```php
// Increase wait times
$this->bridge('/slow-page')
    ->waitForEvent('networkidle')  // Wait for network
    ->wait(2)                       // Extra buffer
    ->assertVisible('[data-testid="content"]');
```

```yaml
# Increase job timeout
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser
  timeout-minutes: 30
```

## Local Reproduction

To reproduce CI failures locally:

```bash
# Match CI environment
export APP_ENV=testing
export DB_CONNECTION=sqlite
export DB_DATABASE=database/database.sqlite

# Run same commands
touch database/database.sqlite
php artisan migrate:fresh
./vendor/bin/pest tests/Browser
```

## Verbose Output

Run tests with more output:

```yaml
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser -v
```

## Complete Debugging Workflow

```yaml
name: Browser Tests

on: [push, pull_request]

jobs:
  browser-tests:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      # ... setup steps ...

      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser -v

      - name: Upload screenshots on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: browser-screenshots
          path: tests/Browser/screenshots/
          retention-days: 7

      - name: Upload HTML snapshots on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: browser-html
          path: tests/Browser/html/
          retention-days: 7

      - name: Upload test logs on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: test-logs
          path: storage/logs/
          retention-days: 7
```
