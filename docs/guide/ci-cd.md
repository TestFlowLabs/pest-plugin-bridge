# CI/CD Integration

Running browser tests with pest-plugin-bridge in continuous integration is the primary use case for this plugin. This guide covers GitHub Actions setup for multi-repository projects.

## How It Works in CI

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        CI EXECUTION FLOW                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │  GitHub Actions Runner                                           │   │
│   │                                                                   │   │
│   │   1. Checkout API repo (tests/Browser/ lives here)              │   │
│   │   2. Checkout Frontend repo (to ./frontend directory)           │   │
│   │   3. Install PHP + Composer dependencies                        │   │
│   │   4. Install Node.js + npm dependencies                         │   │
│   │   5. Install Playwright browsers (headless Chromium)            │   │
│   │   6. Install frontend dependencies                              │   │
│   │                                                                   │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                          │
│                            ▼                                             │
│                                                                          │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │  ./vendor/bin/pest tests/Browser                                 │   │
│   │                                                                   │   │
│   │   • Laravel API starts automatically (in-process via amphp)     │   │
│   │   • Frontend dev server starts automatically (via serve())      │   │
│   │   • Playwright browser runs tests headlessly                    │   │
│   │   • Both servers shut down when tests complete                  │   │
│   │                                                                   │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

## Prerequisites

Before setting up CI/CD, ensure you have:

- A Laravel API project with pest-plugin-bridge installed
- A separate frontend project (Nuxt, React, Vue, etc.)
- Basic familiarity with GitHub Actions

## Repository Structures

### Multi-Repository Setup (Recommended)

This is the most common setup for production applications:

```
your-organization/
├── api/                        # Laravel API repository
│   ├── app/
│   ├── tests/
│   │   └── Browser/            # Browser tests live here
│   ├── .github/
│   │   └── workflows/
│   │       └── browser-tests.yml  # CI workflow runs from API repo
│   ├── composer.json
│   └── phpunit.xml
│
└── frontend/                   # Separate frontend repository
    ├── src/                    # Nuxt/React/Vue source
    ├── package.json
    └── nuxt.config.ts          # Or vite.config.ts, etc.
```

In this setup:
- **Browser tests live in the API repo** - they test the full stack
- **CI workflow runs from the API repo** - it checks out both repos
- **Frontend is checked out as a subdirectory** - `./frontend`

### Single Repository (Monorepo)

For smaller projects or monorepos:

```
my-app/
├── backend/                    # Laravel API
│   ├── app/
│   ├── tests/
│   │   └── Browser/
│   └── composer.json
├── frontend/                   # Frontend app
│   ├── src/
│   └── package.json
└── .github/
    └── workflows/
        └── browser-tests.yml
```

## Complete GitHub Actions Workflow

### Multi-Repository Workflow

Create `.github/workflows/browser-tests.yml` in your API repository:

```yaml
name: Browser Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  browser-tests:
    runs-on: ubuntu-latest

    steps:
      # ─────────────────────────────────────────────────────────────────
      # 1. CHECKOUT REPOSITORIES
      # ─────────────────────────────────────────────────────────────────

      - name: Checkout API
        uses: actions/checkout@v4

      - name: Checkout Frontend
        uses: actions/checkout@v4
        with:
          repository: your-org/frontend-repo
          path: frontend
          # For public repos, GITHUB_TOKEN works
          # For private repos, use a Personal Access Token
          token: ${{ secrets.GITHUB_TOKEN }}

      # ─────────────────────────────────────────────────────────────────
      # 2. SETUP PHP
      # ─────────────────────────────────────────────────────────────────

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pdo_sqlite
          coverage: none

      # ─────────────────────────────────────────────────────────────────
      # 3. SETUP NODE.JS
      # ─────────────────────────────────────────────────────────────────

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 'lts/*'

      # ─────────────────────────────────────────────────────────────────
      # 4. INSTALL COMPOSER DEPENDENCIES (WITH CACHE)
      # ─────────────────────────────────────────────────────────────────

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      # ─────────────────────────────────────────────────────────────────
      # 5. INSTALL PLAYWRIGHT (WITH CACHE)
      # ─────────────────────────────────────────────────────────────────

      - name: Cache npm dependencies
        uses: actions/cache@v4
        with:
          path: ~/.npm
          key: ${{ runner.os }}-npm-${{ hashFiles('**/package-lock.json') }}
          restore-keys: ${{ runner.os }}-npm-

      - name: Install npm dependencies
        run: npm ci

      - name: Get Playwright version
        id: playwright-version
        run: echo "version=$(npm ls @playwright/test --json | jq -r '.dependencies["@playwright/test"].version')" >> $GITHUB_OUTPUT

      - name: Cache Playwright browsers
        uses: actions/cache@v4
        with:
          path: ~/.cache/ms-playwright
          key: ${{ runner.os }}-playwright-${{ steps.playwright-version.outputs.version }}

      - name: Install Playwright browsers
        run: npx playwright install --with-deps chromium

      # ─────────────────────────────────────────────────────────────────
      # 6. INSTALL FRONTEND DEPENDENCIES
      # ─────────────────────────────────────────────────────────────────

      - name: Cache frontend dependencies
        uses: actions/cache@v4
        with:
          path: frontend/node_modules
          key: ${{ runner.os }}-frontend-${{ hashFiles('frontend/package-lock.json') }}
          restore-keys: ${{ runner.os }}-frontend-

      - name: Install frontend dependencies
        run: cd frontend && npm ci

      # ─────────────────────────────────────────────────────────────────
      # 7. PREPARE LARAVEL
      # ─────────────────────────────────────────────────────────────────

      - name: Prepare Laravel
        run: |
          cp .env.example .env
          php artisan key:generate
          touch database/database.sqlite
          php artisan migrate --force

      # ─────────────────────────────────────────────────────────────────
      # 8. RUN BROWSER TESTS
      # ─────────────────────────────────────────────────────────────────

      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser

      # ─────────────────────────────────────────────────────────────────
      # 9. UPLOAD ARTIFACTS ON FAILURE
      # ─────────────────────────────────────────────────────────────────

      - name: Upload screenshots on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: browser-screenshots
          path: tests/Browser/screenshots/
          retention-days: 7
```

### Monorepo Workflow

For single-repository setups, simplify the checkout step:

```yaml
name: Browser Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  browser-tests:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pdo_sqlite

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 'lts/*'

      # ... rest of steps similar to above, adjust paths as needed

      - name: Install frontend dependencies
        run: cd ../frontend && npm ci

      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser
```

## Pest.php Configuration for CI

Configure your `tests/Pest.php` to match your CI checkout structure:

```php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

// Use DatabaseTruncation for browser tests (commits data, visible to API)
pest()->extends(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Browser');

// Frontend is checked out to 'frontend/' directory by GitHub Actions
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: 'frontend');
```

::: tip Path is Relative to Laravel Root
The `cwd` path is relative to your Laravel project root. In the multi-repo workflow above, the frontend is checked out to `./frontend` relative to the API repo root.
:::

## Database Configuration

### Why SQLite In-Memory Doesn't Work

SQLite in-memory databases (`:memory:`) create a separate database per connection. When your frontend makes API calls, those use different database connections that can't see your test data.

### Required Configuration

**`phpunit.xml`:**

```xml
<phpunit>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value="database/database.sqlite"/>
    </php>
</phpunit>
```

**Create the database file in CI:**

```yaml
- name: Prepare Laravel
  run: |
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite  # Create empty file
    php artisan migrate --force
```

### Database Trait Selection

| Trait | Works? | Speed | Notes |
|-------|--------|-------|-------|
| `RefreshDatabase` | No | - | Transaction isolation breaks API visibility |
| `LazilyRefreshDatabase` | No | - | Same as RefreshDatabase |
| `DatabaseTransactions` | No | - | Same isolation issue |
| `DatabaseMigrations` | Yes | Slow | Runs migrate:fresh each test |
| `DatabaseTruncation` | Yes | Fast | Recommended for browser tests |

## Caching Strategies

Caching significantly speeds up CI runs. The workflow above includes caches for:

| Cache | Key Strategy | Typical Savings |
|-------|--------------|-----------------|
| Composer | `composer.lock` hash | 30-60 seconds |
| npm | `package-lock.json` hash | 20-40 seconds |
| Playwright | Playwright version | 60-90 seconds |
| Frontend npm | Frontend `package-lock.json` | 20-40 seconds |

### Playwright Cache Key

The Playwright cache key uses the exact Playwright version to ensure browser compatibility:

```yaml
- name: Get Playwright version
  id: playwright-version
  run: echo "version=$(npm ls @playwright/test --json | jq -r '.dependencies["@playwright/test"].version')" >> $GITHUB_OUTPUT

- name: Cache Playwright browsers
  uses: actions/cache@v4
  with:
    path: ~/.cache/ms-playwright
    key: ${{ runner.os }}-playwright-${{ steps.playwright-version.outputs.version }}
```

## Debugging Failed Tests

### Screenshot Artifacts

The workflow uploads screenshots on failure:

```yaml
- name: Upload screenshots on failure
  if: failure()
  uses: actions/upload-artifact@v4
  with:
    name: browser-screenshots
    path: tests/Browser/screenshots/
    retention-days: 7
```

Take screenshots in your tests on failure:

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

### Common CI Failures

#### 1. Frontend Server Not Starting

**Symptom:** Tests timeout waiting for frontend

**Solutions:**
- Check `cwd` path matches checkout location
- Verify `npm ci` completed successfully
- Check frontend's `package.json` has `dev` script

#### 2. Database Not Visible

**Symptom:** Tests create data but API returns empty

**Solutions:**
- Use `DatabaseTruncation` instead of `RefreshDatabase`
- Use file-based SQLite, not `:memory:`
- Ensure `phpunit.xml` has correct DB_DATABASE path

#### 3. Port Conflicts

**Symptom:** "Port already in use" errors

**Solution:** The plugin uses dynamic port discovery. If you hardcoded ports, remove them:

```php
// Don't hardcode ports
Bridge::setDefault('http://localhost:3000');  // Plugin finds available port

// The serve() command output is monitored for the actual URL
->serve('npm run dev', cwd: 'frontend');
```

#### 4. Playwright Browser Missing

**Symptom:** "Executable doesn't exist" or browser launch failures

**Solution:** Ensure browser installation runs after npm ci:

```yaml
- name: Install npm dependencies
  run: npm ci

- name: Install Playwright browsers
  run: npx playwright install --with-deps chromium
```

## Framework-Specific Notes

### Nuxt 3

Nuxt's dev server works out of the box:

```php
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: 'frontend');
```

The default ready pattern detects Nuxt's `Nitro` startup message.

### React / Vite

```php
Bridge::setDefault('http://localhost:5173')
    ->serve('npm run dev', cwd: 'frontend');
```

### Next.js

```php
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: 'frontend');
```

### Vue CLI (Legacy)

```php
Bridge::setDefault('http://localhost:8080')
    ->serve('npm run serve', cwd: 'frontend');
```

## Private Repositories

For private frontend repositories, use a Personal Access Token:

1. Create a PAT with `repo` scope
2. Add it as a repository secret (e.g., `FRONTEND_REPO_TOKEN`)
3. Use it in the checkout step:

```yaml
- name: Checkout Frontend
  uses: actions/checkout@v4
  with:
    repository: your-org/private-frontend
    path: frontend
    token: ${{ secrets.FRONTEND_REPO_TOKEN }}
```

## Advanced Topics

### PHP Version Matrix

Test against multiple PHP versions:

```yaml
jobs:
  browser-tests:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.2', '8.3', '8.4']

    steps:
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
```

### Parallel Test Execution

Run browser tests in parallel using Pest's parallel option:

```yaml
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser --parallel
```

::: warning Database Isolation
When running parallel tests, each process needs its own database. Consider using separate SQLite files per process or a proper database server.
:::

### Custom Timeout

For long-running test suites:

```yaml
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser
  timeout-minutes: 30
```

### Conditional Browser Tests

Only run browser tests when relevant files change:

```yaml
on:
  push:
    branches: [main]
    paths:
      - 'app/**'
      - 'tests/Browser/**'
      - 'resources/**'
```

## Complete Example Repository

See our playground repositories for working examples:

- **API:** [pest-plugin-bridge-playground-api](https://github.com/TestFlowLabs/pest-plugin-bridge-playground-api)
- **Frontend:** [pest-plugin-bridge-playground-nuxt](https://github.com/TestFlowLabs/pest-plugin-bridge-playground-nuxt)
