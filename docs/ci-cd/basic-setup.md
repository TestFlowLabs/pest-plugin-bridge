# Basic Setup

This is the simplest CI workflow for pest-plugin-bridge. It assumes:

- **Monorepo structure** (backend + frontend in same repository)
- **No database** (tests don't require database access)

## Repository Structure

```
my-app/
├── backend/                    # Laravel API
│   ├── app/
│   ├── tests/
│   │   └── Browser/            # Browser tests
│   ├── composer.json
│   └── phpunit.xml
├── frontend/                   # Nuxt/React/Vue
│   ├── src/
│   └── package.json
└── .github/
    └── workflows/
        └── browser-tests.yml
```

## Pest Configuration

```php
// backend/tests/Pest.php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;
use Tests\TestCase;

pest()->extends(TestCase::class)->in('Browser');

Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

## Complete Workflow

Create `.github/workflows/browser-tests.yml`:

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
      # ─────────────────────────────────────────────────────────────────
      # CHECKOUT
      # ─────────────────────────────────────────────────────────────────

      - name: Checkout
        uses: actions/checkout@v4

      # ─────────────────────────────────────────────────────────────────
      # SETUP PHP
      # ─────────────────────────────────────────────────────────────────

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip
          coverage: none

      # ─────────────────────────────────────────────────────────────────
      # SETUP NODE.JS
      # ─────────────────────────────────────────────────────────────────

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 'lts/*'

      # ─────────────────────────────────────────────────────────────────
      # INSTALL DEPENDENCIES
      # ─────────────────────────────────────────────────────────────────

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Install npm dependencies
        run: npm ci

      - name: Install Playwright browsers
        run: npx playwright install --with-deps chromium

      - name: Install frontend dependencies
        working-directory: frontend
        run: npm ci

      # ─────────────────────────────────────────────────────────────────
      # PREPARE LARAVEL
      # ─────────────────────────────────────────────────────────────────

      - name: Prepare Laravel
        run: |
          cp .env.example .env
          php artisan key:generate

      # ─────────────────────────────────────────────────────────────────
      # RUN TESTS
      # ─────────────────────────────────────────────────────────────────

      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser
```

## Key Points

1. **`working-directory: backend`** - All commands run from the Laravel directory
2. **Playwright must be installed** - `npx playwright install --with-deps chromium`
3. **Frontend dependencies** - Install with `working-directory: frontend`
4. **No database setup** - This workflow has no database configuration

## Adding More Features

| Need | Add |
|------|-----|
| Separate frontend repo | [Multi-Repository](./multi-repo) |
| SQLite database | [SQLite Database](./sqlite) |
| MySQL database | [MySQL Database](./mysql) |
| Faster builds | [Caching](./caching) |
| Debug failures | [Debugging](./debugging) |
