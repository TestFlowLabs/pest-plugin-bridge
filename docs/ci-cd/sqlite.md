# SQLite Database

When your tests need database access, SQLite is the simplest option for CI. However, there are important constraints.

## Why In-Memory Doesn't Work

SQLite in-memory databases (`:memory:`) create a **separate database per connection**. When your frontend makes API calls, those use different database connections that can't see your test data.

```
┌─────────────────────────────────────────────────────────────────┐
│  :memory: Problem                                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   Test Process              API Process                         │
│   ┌─────────────┐          ┌─────────────┐                      │
│   │ Connection A│          │ Connection B│                      │
│   │ :memory: DB │          │ :memory: DB │  ← Different DB!     │
│   │             │          │             │                      │
│   │ User::create│          │ User::find  │  ← Can't see data    │
│   └─────────────┘          └─────────────┘                      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

**Solution**: Use a file-based SQLite database.

## Configuration

### phpunit.xml

```xml
<phpunit>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value="database/database.sqlite"/>
    </php>
</phpunit>
```

### Pest.php

Use `DatabaseTruncation` instead of `RefreshDatabase`:

```php
// tests/Pest.php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

pest()->extends(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Browser');

Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

## Database Trait Compatibility

| Trait | Works? | Speed | Why |
|-------|--------|-------|-----|
| `RefreshDatabase` | No | - | Transaction isolation hides data |
| `LazilyRefreshDatabase` | No | - | Same as RefreshDatabase |
| `DatabaseTransactions` | No | - | Same isolation issue |
| `DatabaseMigrations` | Yes | Slow | Runs `migrate:fresh` each test |
| `DatabaseTruncation` | Yes | Fast | Truncates tables, keeps schema |

::: tip Use DatabaseTruncation
`DatabaseTruncation` is recommended. It's faster than `DatabaseMigrations` because it only truncates data, not the schema.
:::

## Workflow Snippet

Add this to your workflow:

```yaml
- name: Prepare Laravel
  run: |
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite
    php artisan migrate --force
```

## Complete Example

```yaml
name: Browser Tests

on: [push, pull_request]

jobs:
  browser-tests:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend

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

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pdo_sqlite

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 'lts/*'

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Install npm dependencies
        run: npm ci

      - name: Install Playwright browsers
        run: npx playwright install --with-deps chromium

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
        run: ./vendor/bin/pest tests/Browser
```

## Using Same Database for All Tests

You can use the same SQLite database for both unit/feature tests and browser tests, with different traits:

```php
// tests/Pest.php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTruncation;

// Unit/Feature tests: RefreshDatabase (fast, transaction-based)
pest()->extends(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

// Browser tests: DatabaseTruncation (commits data, visible to API)
pest()->extends(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Browser');
```

## Troubleshooting

### "Database does not exist"

Ensure you create the file before migrating:

```yaml
- run: touch database/database.sqlite
- run: php artisan migrate --force
```

### Tests Create Data But API Returns Empty

1. Check you're using `DatabaseTruncation`, not `RefreshDatabase`
2. Verify `phpunit.xml` has the correct `DB_DATABASE` path
3. Ensure both test and API use the same database file
