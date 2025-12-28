# Troubleshooting

Common issues and their solutions when using Pest Bridge Plugin.

## Installation Issues

### Playwright Not Found

**Symptom**: "Playwright is not installed" error

**Solution**:
```bash
npm install playwright
npx playwright install chromium
```

### Browser Launch Fails

**Symptom**: Browser fails to start on Linux servers

**Solution**: Install system dependencies:
```bash
npx playwright install-deps chromium
```

### Connection Refused

**Symptom**: Tests fail with "Connection refused" when trying to reach frontend

**Checklist**:
1. Ensure frontend server is running: `curl http://localhost:3000`
2. Check the port matches your configuration
3. If using `serve()`, check server output for errors

## Frontend Server Issues

### Vite Cold-Start Timeout

**Symptom**: Tests pass when frontend is started manually, but timeout when using `serve()`

**Cause**: Vite's on-demand module compilation. When Vite starts, it reports "ready" immediately, but JavaScript modules are compiled on first browser request. For large applications, this takes 3-5+ seconds.

**What happens:**
1. `npm run dev` starts → Vite outputs "VITE ready in 500ms"
2. Bridge detects "ready" pattern → server is accepting connections
3. Playwright navigates to page → browser requests JS modules
4. Vite compiles modules on-demand → takes 3-5+ seconds
5. Default timeout expires before page is interactive

**Why manual start works**: When you start the frontend manually and refresh the page in your browser, you trigger the compilation. Modules are cached before tests run.

**Solution**: The `bridge()` method uses a 30-second timeout by default to handle cold-start. If you need more time:

```php
// Override timeout per navigation
$this->bridge('/', options: ['timeout' => 60000]);  // 60s timeout

// Or add extra warmup delay after server is ready
Bridge::add('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('VITE.*ready')
    ->warmup(2000);  // Additional 2s delay
```

::: info Why Not Just Wait for Network Idle?
Vite's HMR WebSocket keeps a persistent connection open, preventing the network from ever reaching "idle" state. The extended timeout approach handles this automatically.
:::

### Server Doesn't Start

**Symptom**: Tests hang waiting for server, or "Frontend server failed to start" error

**Common causes**:

1. **Wrong command**: Check `npm run dev` works manually
2. **Wrong directory**: Verify `cwd:` path is correct (relative to Laravel root)
3. **Port already in use**: Kill existing processes

```bash
# Kill process on port 3000
lsof -ti:3000 | xargs kill -9

# Or on Windows
netstat -ano | findstr :3000
taskkill /PID <PID> /F
```

### Server Starts But Tests Fail

**Symptom**: Server starts but `bridge()` can't connect

**Checklist**:
1. URL in `Bridge::add()` matches actual server URL
2. Server is binding to `0.0.0.0` or `localhost`, not just `127.0.0.1`
3. No firewall blocking the port

### Custom Ready Pattern Needed

**Symptom**: Tests start before server is ready

**Solution**: Use `readyWhen()` with a pattern matching your server's output:

```php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('Your custom pattern');
```

Check your server's console output for a reliable "ready" indicator.

## Database Issues

### SQLite :memory: Doesn't Work

**Symptom**: Data created in tests is not visible to API

**Cause**: Each connection gets its own isolated in-memory database

**Solution**: Use file-based SQLite:
```xml
<!-- phpunit.xml -->
<env name="DB_DATABASE" value="database/database.sqlite"/>
```

### RefreshDatabase Breaks Tests

**Symptom**: Test creates data, API returns empty

**Cause**: `RefreshDatabase` wraps tests in transactions that aren't visible to other connections

**Solution**: Use `DatabaseTruncation` instead:
```php
// tests/Pest.php
pest()->extends(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Browser');
```

### Database Assertions Fail But UI Shows Success

**Symptom**: `assertDatabaseHas()` fails even though UI shows success

**Cause**: Browser tests use separate database connections

**Solution**: Don't mix database assertions with browser tests. Use UI assertions instead:
```php
// Instead of: $this->assertDatabaseHas('users', ['email' => $email]);
// Use:
$this->bridge('/users')->assertSee($email);
```

::: tip Comprehensive Database Guide
For detailed explanation of database traits, transaction isolation, and recommended configurations, see [Connection Architecture: Database Configuration](/guide/connection#database-configuration).
:::

## Form & Input Issues

### Form Submits Empty Values

**Symptom**: Form validation fails with "field required" even though you filled it

**Cause**: Vue/React's reactive binding (`v-model`, `useState`) doesn't see `fill()` DOM changes

**Solution**: Use `typeSlowly()` which triggers real keyboard events:
```php
// Instead of:
->fill('[data-testid="email"]', 'test@example.com')

// Use:
->typeSlowly('[data-testid="email"]', 'test@example.com')
```

::: tip Framework-Specific Guide
For detailed explanation of `fill()` vs `typeSlowly()` behavior with Vue, React, and other reactive frameworks, see [Best Practices: Vue/Nuxt Framework-Specific](/guide/best-practices#vue-nuxt-framework-specific-best-practices).
:::

### First Characters Lost When Typing

**Symptom**: Input shows "xample.com" instead of "example.com"

**Cause**: Page not fully ready for input, first keystrokes lost

**Solution**: Click the field first and/or wait:
```php
$this->bridge('/login')
    ->waitForEvent('networkidle')
    ->click('input#email')  // Focus first
    ->typeSlowly('input#email', 'test@example.com', 30);
```

### Tests Hang or Timeout

**Symptom**: Test hangs indefinitely or times out

**Cause**: Waiting for something that never happens

**Solutions**:

1. Use event-based waits instead of fixed delays:
   ```php
   ->waitForEvent('networkidle')  // Better than ->wait(2)
   ```

2. Add timeout to waits:
   ```php
   ->waitForSelector('[data-testid="result"]', timeout: 5000)
   ```

3. Check if element actually exists in the DOM

## Authentication Issues

### CORS Errors

**Symptom**: Console shows "Access-Control-Allow-Origin" errors

**Solution**: Update `config/cors.php`:
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_origins' => ['http://localhost:3000'],
    'supports_credentials' => true,  // Important for cookies!
];
```

### Cookies Not Sent

**Symptom**: Authentication works manually but not in tests

**Checklist**:
1. Set `SESSION_DOMAIN=localhost` in `.env`
2. Add frontend to Sanctum stateful domains:
   ```php
   // config/sanctum.php
   'stateful' => ['localhost:3000'],
   ```
3. Frontend sends credentials with requests:
   ```javascript
   fetch(url, { credentials: 'include' })
   ```

### CSRF Token Mismatch (419 Error)

**Symptom**: POST requests return 419 error

**Solutions**:

1. **For Sanctum SPA auth**: Ensure CSRF cookie is fetched first:
   ```php
   // Frontend should call this before login
   await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
   ```

2. **For token-based auth**: Remove `statefulApi()` middleware:
   ```php
   // bootstrap/app.php - remove or comment out:
   // ->withMiddleware(function (Middleware $middleware) {
   //     $middleware->statefulApi();
   // })
   ```

## CI/CD Issues

### Checkout Fails in GitHub Actions

**Symptom**: "The process '/usr/bin/git' failed with exit code 1"

**Common causes**:

1. **Wrong branch name**: Check if repo uses `main` or `master`:
   ```yaml
   inputs:
     branch:
       default: 'master'  # or 'main'
   ```

2. **Private repository**: Add access token:
   ```yaml
   - uses: actions/checkout@v4
     with:
       repository: your-org/private-repo
       token: ${{ secrets.REPO_ACCESS_TOKEN }}
   ```

### Composer Install Fails: PHP Version

**Symptom**: "Package requires PHP >= 8.4"

**Solution**: Match PHP version to your dependencies:
```yaml
- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.4'  # Match composer.lock requirements
```

::: tip Check Requirements Locally
```bash
composer show --locked | grep symfony
```
Symfony 8.x requires PHP 8.4+.
:::

### MySQL Connection Refused

**Symptom**: Can't connect to MySQL in CI

**Checklist**:
1. Health check passed (check workflow logs)
2. Port mapping is correct (`3306:3306`)
3. Use `DB_HOST=127.0.0.1` (not `localhost`)
4. Credentials match service configuration

### SQLite "Database does not exist"

**Solution**: Create file before migrating:
```yaml
- run: touch database/database.sqlite
- run: php artisan migrate --force
```

## Still Stuck?

1. **Enable headed mode** to see what's happening:
   ```bash
   PLAYWRIGHT_HEADLESS=false ./vendor/bin/pest tests/Browser
   ```

2. **Take screenshots** at failure points:
   ```php
   ->screenshot('debug-screenshot')
   ```

3. **Check Laravel logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Open an issue** with:
   - PHP version (`php -v`)
   - Package versions (`composer show testflowlabs/pest-plugin-bridge`)
   - Minimal reproduction code
   - Full error message/stack trace
