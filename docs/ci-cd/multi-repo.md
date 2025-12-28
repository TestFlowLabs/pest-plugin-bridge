# Multi-Repository Setup

When your frontend lives in a separate repository, you need to check it out during CI.

## The Core Concept

GitHub Actions' `actions/checkout` can checkout **multiple repositories side by side**. Use the `path` parameter to specify the directory for each:

```yaml
steps:
  # Checkout API repo into ./backend
  - uses: actions/checkout@v4
    with:
      path: backend

  # Checkout frontend repo into ./frontend
  - uses: actions/checkout@v4
    with:
      repository: your-org/frontend-repo
      path: frontend
```

This creates a side-by-side structure in the runner. The rest of this page covers the details.

## Repository Structure

**Your GitHub Organization:**
```
your-organization/
+-- api/                        # Laravel API repository
|   +-- app/
|   +-- tests/
|   |   +-- Browser/            # Browser tests live here
|   +-- .github/
|   |   +-- workflows/
|   |       +-- browser-tests.yml  # CI runs from API repo
|   +-- composer.json
|   +-- phpunit.xml
|
+-- frontend/                   # Separate frontend repository
    +-- src/
    +-- package.json
    +-- nuxt.config.ts
```

**After Checkout in CI Runner:**
```
$GITHUB_WORKSPACE/
+-- backend/                    # API repo (path: backend)
|   +-- app/
|   +-- tests/Browser/
|   +-- composer.json
+-- frontend/                   # Frontend repo (path: frontend)
    +-- src/
    +-- package.json
```

## Pest Configuration

```php
// backend/tests/Pest.php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;
use Tests\TestCase;

pest()->extends(TestCase::class)->in('Browser');

// Frontend is at ../frontend relative to backend
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

::: tip Path is Relative to Laravel Root
The `cwd` path is relative to your Laravel project root. Since both repos are side by side, use `../frontend` to go up one level and into the frontend directory.
:::

## Checkout Snippet

Add both checkouts at the start of your workflow:

```yaml
steps:
  # Checkout API repository into ./backend
  - name: Checkout API
    uses: actions/checkout@v4
    with:
      path: backend

  # Checkout Frontend repository into ./frontend
  - name: Checkout Frontend
    uses: actions/checkout@v4
    with:
      repository: your-org/frontend-repo
      path: frontend
```

## Private Repositories

For private frontend repositories, `GITHUB_TOKEN` won't work across repos. Use a Personal Access Token:

### 1. Create a PAT

1. Go to GitHub → Settings → Developer settings → Personal access tokens
2. Generate a new token with `repo` scope
3. Copy the token

### 2. Add as Repository Secret

1. Go to your API repository → Settings → Secrets and variables → Actions
2. Click "New repository secret"
3. Name: `FRONTEND_REPO_TOKEN`
4. Value: Your PAT

### 3. Use in Workflow

```yaml
- name: Checkout Frontend
  uses: actions/checkout@v4
  with:
    repository: your-org/private-frontend
    path: frontend
    token: ${{ secrets.FRONTEND_REPO_TOKEN }}
```

## Specific Branch or Tag

```yaml
- name: Checkout Frontend
  uses: actions/checkout@v4
  with:
    repository: your-org/frontend-repo
    path: frontend
    ref: develop  # Branch, tag, or commit SHA
    token: ${{ secrets.GITHUB_TOKEN }}
```

## Complete Workflow Example

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
          extensions: dom, curl, libxml, mbstring, zip
          coverage: none

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

      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser
```

::: tip working-directory
`defaults.run.working-directory: backend` sets the default directory for all `run` commands. For frontend commands, override with `working-directory: frontend`.
:::

## Synchronizing Branches

If your API and frontend branches should match:

```yaml
- name: Checkout Frontend
  uses: actions/checkout@v4
  with:
    repository: your-org/frontend-repo
    path: frontend
    ref: ${{ github.head_ref || github.ref_name }}
    token: ${{ secrets.GITHUB_TOKEN }}
```

This checks out the same branch name in the frontend repo (falls back to default if it doesn't exist).

## Multiple Frontends

Some projects have multiple frontend applications (web app, admin dashboard, mobile PWA). The same pattern extends naturally.

### Workflow Configuration

```yaml
on:
  workflow_dispatch:
    inputs:
      frontend_web_branch:
        description: 'Web app branch'
        default: 'develop'
      frontend_admin_branch:
        description: 'Admin dashboard branch'
        default: 'develop'

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

      - name: Checkout Web Frontend
        uses: actions/checkout@v4
        with:
          repository: your-org/frontend-web
          path: frontend-web
          ref: ${{ inputs.frontend_web_branch || 'develop' }}

      - name: Checkout Admin Frontend
        uses: actions/checkout@v4
        with:
          repository: your-org/frontend-admin
          path: frontend-admin
          ref: ${{ inputs.frontend_admin_branch || 'develop' }}

      # ... setup steps ...

      - name: Install web frontend dependencies
        working-directory: frontend-web
        run: npm ci

      - name: Install admin frontend dependencies
        working-directory: frontend-admin
        run: npm ci
```

### Pest Configuration

Configure multiple Bridge instances for different frontends:

```php
// backend/tests/Pest.php
use TestFlowLabs\PestPluginBridge\Bridge;

// Web app on port 3000
Bridge::make('web', 'http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend-web');

// Admin dashboard on port 3001
Bridge::make('admin', 'http://localhost:3001')
    ->serve('npm run dev -- --port 3001', cwd: '../frontend-admin');
```

Use named instances in tests:

```php
test('user can access web dashboard', function () {
    $this->bridge('/dashboard', 'web')
        ->assertSee('Welcome');
});

test('admin can access admin panel', function () {
    $this->bridge('/admin', 'admin')
        ->assertSee('Admin Panel');
});
```

### Triggering with gh CLI

```bash
gh workflow run browser-tests.yml \
  -f backend_branch=feature/api-changes \
  -f frontend_web_branch=develop \
  -f frontend_admin_branch=feature/new-admin-ui \
  -f test_group=smoke
```

### Directory Structure

```
$GITHUB_WORKSPACE/
+-- backend/           # Laravel API
+-- frontend-web/      # Main web application
+-- frontend-admin/    # Admin dashboard
```

::: tip Consistent Naming
Use descriptive directory names (`frontend-web`, `frontend-admin`) instead of generic names when working with multiple frontends.
:::
