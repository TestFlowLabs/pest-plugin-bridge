# Caching

Caching dependencies significantly speeds up CI runs. A typical uncached run takes 3-5 minutes; with caching, it drops to 1-2 minutes.

## Cache Overview

| Cache | Savings | Key Strategy |
|-------|---------|--------------|
| Composer | 30-60s | `composer.lock` hash |
| npm | 20-40s | `package-lock.json` hash |
| Playwright | 60-90s | Playwright version |
| Frontend npm | 20-40s | Frontend `package-lock.json` hash |

## Composer Cache

```yaml
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
```

## npm Cache

```yaml
- name: Cache npm dependencies
  uses: actions/cache@v4
  with:
    path: ~/.npm
    key: ${{ runner.os }}-npm-${{ hashFiles('**/package-lock.json') }}
    restore-keys: ${{ runner.os }}-npm-

- name: Install npm dependencies
  run: npm ci
```

## Playwright Browser Cache

Playwright browsers are large (~100MB). Cache them by version:

```yaml
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
```

::: tip Version-Based Key
Using the Playwright version as the cache key ensures browsers are re-downloaded when Playwright updates.
:::

## Frontend Dependencies Cache

```yaml
- name: Cache frontend dependencies
  uses: actions/cache@v4
  with:
    path: frontend/node_modules
    key: ${{ runner.os }}-frontend-${{ hashFiles('frontend/package-lock.json') }}
    restore-keys: ${{ runner.os }}-frontend-

- name: Install frontend dependencies
  run: cd frontend && npm ci
```

## Complete Workflow with All Caches

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
          extensions: dom, curl, libxml, mbstring, zip

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 'lts/*'

      # ─────────────────────────────────────────────────────────────────
      # COMPOSER CACHE
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
      # NPM + PLAYWRIGHT CACHE
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
      # FRONTEND CACHE
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
      # RUN TESTS
      # ─────────────────────────────────────────────────────────────────

      - name: Prepare Laravel
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser
```

## pnpm Cache

If using pnpm instead of npm:

```yaml
- name: Setup pnpm
  uses: pnpm/action-setup@v2
  with:
    version: 8

- name: Get pnpm store directory
  id: pnpm-cache
  run: echo "dir=$(pnpm store path)" >> $GITHUB_OUTPUT

- name: Cache pnpm dependencies
  uses: actions/cache@v4
  with:
    path: ${{ steps.pnpm-cache.outputs.dir }}
    key: ${{ runner.os }}-pnpm-${{ hashFiles('**/pnpm-lock.yaml') }}
    restore-keys: ${{ runner.os }}-pnpm-

- name: Install dependencies
  run: pnpm install --frozen-lockfile
```

## Cache Hit Debugging

Check if caches are hitting by looking at the workflow logs. You'll see:

```
Cache restored from key: Linux-composer-abc123...
```

Or:

```
Cache not found for key: Linux-composer-abc123...
```

If caches aren't hitting, verify your lock file is committed and the hash is stable.
