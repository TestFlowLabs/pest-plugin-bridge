# Multi-Repository Setup

When your frontend lives in a separate repository, you need to check it out during CI.

## The Core Concept

GitHub Actions' `actions/checkout` can checkout **any repository**, not just the one triggering the workflow. Use the `repository` parameter to specify which repo, and `path` to specify where it goes:

```yaml
# Checkout frontend repo into ./frontend directory
- uses: actions/checkout@v4
  with:
    repository: your-org/frontend-repo
    path: frontend
```

This is the key to combining two repositories. The rest of this page covers the details.

## Repository Structure

```
your-organization/
├── api/                        # Laravel API repository
│   ├── app/
│   ├── tests/
│   │   └── Browser/            # Browser tests live here
│   ├── .github/
│   │   └── workflows/
│   │       └── browser-tests.yml  # CI runs from API repo
│   ├── composer.json
│   └── phpunit.xml
│
└── frontend/                   # Separate frontend repository
    ├── src/
    ├── package.json
    └── nuxt.config.ts
```

## Pest Configuration

```php
// tests/Pest.php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;
use Tests\TestCase;

pest()->extends(TestCase::class)->in('Browser');

// Frontend is checked out to 'frontend/' by GitHub Actions
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: 'frontend');
```

::: tip Path is Relative to Laravel Root
The `cwd` path is relative to your Laravel project root. After checkout, the frontend is at `./frontend`.
:::

## Checkout Snippet

Add this to your workflow after the API checkout:

```yaml
steps:
  # Checkout API repository (this repo)
  - name: Checkout API
    uses: actions/checkout@v4

  # Checkout Frontend repository
  - name: Checkout Frontend
    uses: actions/checkout@v4
    with:
      repository: your-org/frontend-repo
      path: frontend
      token: ${{ secrets.GITHUB_TOKEN }}
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

    steps:
      - name: Checkout API
        uses: actions/checkout@v4

      - name: Checkout Frontend
        uses: actions/checkout@v4
        with:
          repository: your-org/frontend-repo
          path: frontend
          token: ${{ secrets.GITHUB_TOKEN }}

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
        run: cd frontend && npm ci

      - name: Prepare Laravel
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser
```

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
