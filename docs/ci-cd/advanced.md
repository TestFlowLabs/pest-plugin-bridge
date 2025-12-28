# Advanced Topics

This page covers advanced CI/CD configurations for power users.

## PHP Version Matrix

Test against multiple PHP versions:

```yaml
jobs:
  browser-tests:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend
    strategy:
      fail-fast: false
      matrix:
        php: ['8.2', '8.3', '8.4']

    steps:
      - name: Checkout API
        uses: actions/checkout@v4
        with:
          path: backend

      - name: Checkout Frontend
        uses: actions/checkout@v4
        with:
          repository: your-org/frontend-repo
          path: frontend

      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip

      # ... rest of steps
```

## Node.js Version Matrix

```yaml
strategy:
  matrix:
    node: ['18', '20', '22']

steps:
  - name: Setup Node.js ${{ matrix.node }}
    uses: actions/setup-node@v4
    with:
      node-version: ${{ matrix.node }}
```

## Combined Matrix

```yaml
strategy:
  fail-fast: false
  matrix:
    php: ['8.2', '8.3']
    node: ['18', '20']

steps:
  - name: Setup PHP ${{ matrix.php }}
    uses: shivammathur/setup-php@v2
    with:
      php-version: ${{ matrix.php }}

  - name: Setup Node.js ${{ matrix.node }}
    uses: actions/setup-node@v4
    with:
      node-version: ${{ matrix.node }}
```

## Parallel Test Execution

Run browser tests in parallel using Pest's parallel option:

```yaml
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser --parallel --processes=4
```

::: warning Database Isolation Required
When running parallel tests, each process needs database isolation. Options:
- Separate SQLite files per process
- MySQL with separate databases
- Laravel's `RefreshDatabase` with `--parallel` support
:::

## Custom Timeouts

### Step Timeout

```yaml
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser
  timeout-minutes: 30
```

### Job Timeout

```yaml
jobs:
  browser-tests:
    runs-on: ubuntu-latest
    timeout-minutes: 60
```

## Conditional Execution

### Only on Relevant Changes

```yaml
on:
  push:
    branches: [main]
    paths:
      - 'app/**'
      - 'tests/Browser/**'
      - 'resources/**'
      - 'routes/**'
```

### Skip CI

```yaml
on:
  push:
    branches: [main]

jobs:
  browser-tests:
    if: "!contains(github.event.head_commit.message, '[skip ci]')"
```

### Only on Pull Requests

```yaml
on:
  pull_request:
    types: [opened, synchronize, reopened]
```

## Scheduled Runs

Run tests on a schedule (e.g., nightly):

```yaml
on:
  schedule:
    - cron: '0 2 * * *'  # 2 AM UTC daily

  # Also run on push
  push:
    branches: [main]
```

## Framework-Specific Notes

### Nuxt 3

```php
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

For production build testing:

```yaml
- name: Build frontend
  working-directory: frontend
  run: npm run build

- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser
  env:
    FRONTEND_COMMAND: 'npm run preview'  # Use preview server
```

### React / Vite

Default port is 5173:

```php
Bridge::setDefault('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend');
```

### Next.js

```php
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

### Vue CLI (Legacy)

```php
Bridge::setDefault('http://localhost:8080')
    ->serve('npm run serve', cwd: '../frontend');
```

## Self-Hosted Runners

For faster or customized builds:

```yaml
jobs:
  browser-tests:
    runs-on: self-hosted
    # Or use labels
    # runs-on: [self-hosted, linux, x64]
```

::: tip Pre-installed Dependencies
Self-hosted runners can have Playwright browsers pre-installed, saving download time.
:::

## Concurrency Control

Prevent multiple runs on the same branch:

```yaml
concurrency:
  group: browser-tests-${{ github.ref }}
  cancel-in-progress: true
```

## Environment Variables

### From Secrets

```yaml
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser
  env:
    API_KEY: ${{ secrets.API_KEY }}
    THIRD_PARTY_URL: ${{ secrets.THIRD_PARTY_URL }}
```

### Dynamic Values

```yaml
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser
  env:
    TEST_TIMESTAMP: ${{ github.run_id }}
    GIT_SHA: ${{ github.sha }}
```

## Required Status Checks

After setting up your workflow, make it a required status check:

1. Go to repository Settings → Branches
2. Add branch protection rule for `main`
3. Enable "Require status checks to pass before merging"
4. Select your browser tests workflow

## Complete Advanced Workflow

```yaml
name: Browser Tests

on:
  push:
    branches: [main, develop]
    paths:
      - 'app/**'
      - 'tests/Browser/**'
      - 'resources/**'
  pull_request:
    branches: [main, develop]
  schedule:
    - cron: '0 2 * * *'

concurrency:
  group: browser-tests-${{ github.ref }}
  cancel-in-progress: true

jobs:
  browser-tests:
    runs-on: ubuntu-latest
    timeout-minutes: 30
    defaults:
      run:
        working-directory: backend

    strategy:
      fail-fast: false
      matrix:
        php: ['8.2', '8.3']

    steps:
      - name: Checkout API
        uses: actions/checkout@v4
        with:
          path: backend

      - name: Checkout Frontend
        uses: actions/checkout@v4
        with:
          repository: your-org/frontend-repo
          path: frontend

      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, pdo_sqlite

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 'lts/*'

      # ... caching steps from caching.md ...

      - name: Install dependencies
        run: |
          composer install --no-interaction --prefer-dist
          npm ci
          npx playwright install --with-deps chromium
      - name: Install frontend dependencies
        working-directory: frontend
        run: npm ci

      - name: Prepare Laravel
        run: |
          cp .env.example .env
          php artisan key:generate
          touch database/database.sqlite
          php artisan migrate --force

      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser --parallel

      - name: Upload artifacts on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: browser-artifacts-php${{ matrix.php }}
          path: |
            backend/tests/Browser/screenshots/
            backend/storage/logs/
          retention-days: 7
```
