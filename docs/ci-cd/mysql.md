# MySQL Database

For production-like testing or when your app requires MySQL-specific features, you can use MySQL in CI.

## Two Patterns

| Pattern | Use Case |
|---------|----------|
| **Service Container** | Run MySQL in the CI runner |
| **External Connection** | Connect to existing MySQL server |

## Pattern A: MySQL Service in Runner

GitHub Actions can run MySQL as a service container alongside your tests.

### Service Configuration

```yaml
jobs:
  browser-tests:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
```

### Configure Laravel Connection

```yaml
- name: Configure database
  run: |
    echo "DB_CONNECTION=mysql" >> .env
    echo "DB_HOST=127.0.0.1" >> .env
    echo "DB_PORT=3306" >> .env
    echo "DB_DATABASE=testing" >> .env
    echo "DB_USERNAME=root" >> .env
    echo "DB_PASSWORD=password" >> .env
```

### Complete Workflow

```yaml
name: Browser Tests

on: [push, pull_request]

jobs:
  browser-tests:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pdo_mysql

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
        run: cd frontend && npm ci

      - name: Prepare Laravel
        run: |
          cp .env.example .env
          php artisan key:generate
          echo "DB_CONNECTION=mysql" >> .env
          echo "DB_HOST=127.0.0.1" >> .env
          echo "DB_PORT=3306" >> .env
          echo "DB_DATABASE=testing" >> .env
          echo "DB_USERNAME=root" >> .env
          echo "DB_PASSWORD=password" >> .env
          php artisan migrate --force

      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser
```

## Pattern B: External MySQL Connection

Connect to an existing MySQL server (e.g., cloud database, shared testing server).

### Using Secrets

Store credentials as repository secrets:

1. Go to repository → Settings → Secrets and variables → Actions
2. Add secrets:
   - `DB_HOST`
   - `DB_DATABASE`
   - `DB_USERNAME`
   - `DB_PASSWORD`

### Workflow Configuration

```yaml
- name: Configure database
  run: |
    echo "DB_CONNECTION=mysql" >> .env
    echo "DB_HOST=${{ secrets.DB_HOST }}" >> .env
    echo "DB_PORT=3306" >> .env
    echo "DB_DATABASE=${{ secrets.DB_DATABASE }}" >> .env
    echo "DB_USERNAME=${{ secrets.DB_USERNAME }}" >> .env
    echo "DB_PASSWORD=${{ secrets.DB_PASSWORD }}" >> .env
```

::: warning Security
Never commit database credentials. Always use GitHub Secrets.
:::

## Pest Configuration

Same as SQLite - use `DatabaseTruncation`:

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
    ->serve('npm run dev', cwd: 'frontend');
```

## phpunit.xml Configuration

For MySQL, update your `phpunit.xml`:

```xml
<phpunit>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="mysql"/>
        <!-- Other values come from .env in CI -->
    </php>
</phpunit>
```

## MariaDB Alternative

MariaDB is a drop-in replacement for MySQL:

```yaml
services:
  mariadb:
    image: mariadb:10.11
    env:
      MARIADB_ROOT_PASSWORD: password
      MARIADB_DATABASE: testing
    ports:
      - 3306:3306
    options: >-
      --health-cmd="healthcheck.sh --connect --innodb_initialized"
      --health-interval=10s
      --health-timeout=5s
      --health-retries=3
```

## Troubleshooting

### Connection Refused

1. Check the service health check passed
2. Verify port mapping (`3306:3306`)
3. Ensure `DB_HOST=127.0.0.1` (not `localhost`)

### Access Denied

1. Check `MYSQL_ROOT_PASSWORD` matches `DB_PASSWORD`
2. Verify `DB_USERNAME=root`

### Slow Startup

MySQL takes time to initialize. The health check ensures it's ready before tests run.
