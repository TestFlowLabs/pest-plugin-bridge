# Manual Triggers

Manually trigger browser tests from GitHub Actions UI or the `gh` CLI with configurable branch and test selection.

## Why Manual Triggers?

Manual triggers are essential for:

- **QA Testing**: Test specific feature branches before merge
- **Pre-merge Validation**: Verify branch combinations work together
- **Debugging**: Run specific test groups to isolate issues
- **Cross-repo Testing**: Test frontend feature branch with backend develop

## Triggering Methods

Both methods use the same `workflow_dispatch` configuration:

### GitHub UI

1. Go to your repository → **Actions** tab
2. Select your browser tests workflow
3. Click **"Run workflow"** button
4. Fill in the inputs and click **"Run workflow"**

### gh CLI

```bash
# List available workflows
gh workflow list

# Trigger with default inputs
gh workflow run browser-tests.yml

# Trigger with specific inputs
gh workflow run browser-tests.yml \
  -f backend_branch=feature/payment \
  -f frontend_branch=develop \
  -f test_group=smoke

# Watch the run progress
gh run watch
```

## Basic Configuration

Add `workflow_dispatch` to your existing workflow triggers:

```yaml
name: Browser Tests

on:
  # Automatic triggers
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

  # Manual trigger
  workflow_dispatch:
```

This enables the "Run workflow" button without any inputs.

## Branch Selection

### Single Repository

For monorepo setups, the workflow uses the branch you select in the UI:

```yaml
on:
  workflow_dispatch:

jobs:
  browser-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      # Uses the branch selected in the UI
```

### Multi-Repository

For separate frontend/backend repos, add branch inputs:

```yaml
on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]
  workflow_dispatch:
    inputs:
      backend_branch:
        description: 'Backend branch (empty = current ref)'
        required: false
        default: ''
      frontend_branch:
        description: 'Frontend branch'
        required: false
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
          ref: ${{ inputs.backend_branch || github.ref }}

      - name: Checkout Frontend
        uses: actions/checkout@v4
        with:
          repository: your-org/frontend-repo
          path: frontend
          ref: ${{ inputs.frontend_branch || 'develop' }}
```

::: tip Fallback Logic
The expression `inputs.backend_branch || github.ref` uses the input if provided, otherwise falls back to the triggering ref. This makes the workflow work for both automatic and manual triggers.
:::

## Test Selection with Groups

Use Pest's `--group` feature to organize and selectively run tests.

### Organizing Tests

Add groups to your tests:

```php
// tests/Browser/LoginTest.php
test('user can login with valid credentials', function () {
    $this->bridge('/login')
        ->type('[data-testid="email"]', 'test@example.com')
        ->type('[data-testid="password"]', 'password')
        ->click('[data-testid="submit"]')
        ->assertPathContains('/dashboard');
})->group('smoke', 'auth');

test('user sees error with invalid credentials', function () {
    $this->bridge('/login')
        ->type('[data-testid="email"]', 'wrong@example.com')
        ->type('[data-testid="password"]', 'wrong')
        ->click('[data-testid="submit"]')
        ->assertSee('Invalid credentials');
})->group('regression', 'auth');

test('user can reset password', function () {
    // ...
})->group('critical', 'auth');
```

### Common Group Names

| Group | Purpose |
|-------|---------|
| `smoke` | Quick sanity checks, run frequently |
| `critical` | Core business flows that must never break |
| `regression` | Comprehensive tests, run before releases |
| `auth` | Authentication-related tests |
| `checkout` | E-commerce checkout flow tests |

### Workflow Configuration

```yaml
on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]
  workflow_dispatch:
    inputs:
      test_group:
        description: 'Test group to run'
        required: false
        default: 'all'
        type: choice
        options:
          - all
          - smoke
          - critical
          - regression

jobs:
  browser-tests:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend

    steps:
      # ... checkout and setup steps ...

      - name: Run browser tests
        run: |
          if [ "${{ inputs.test_group }}" == "all" ] || [ -z "${{ inputs.test_group }}" ]; then
            ./vendor/bin/pest tests/Browser
          else
            ./vendor/bin/pest tests/Browser --group=${{ inputs.test_group }}
          fi
```

### Running Locally

Test your groups locally before CI:

```bash
# Run all browser tests
./vendor/bin/pest tests/Browser

# Run only smoke tests
./vendor/bin/pest tests/Browser --group=smoke

# Run multiple groups
./vendor/bin/pest tests/Browser --group=smoke,critical
```

## Complete Example

Full workflow with branch selection and test groups:

```yaml
name: Browser Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]
  workflow_dispatch:
    inputs:
      backend_branch:
        description: 'Backend branch (empty = current ref)'
        required: false
        default: ''
      frontend_branch:
        description: 'Frontend branch'
        required: false
        default: 'develop'
      test_group:
        description: 'Test group to run'
        required: false
        default: 'all'
        type: choice
        options:
          - all
          - smoke
          - critical
          - regression

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
          ref: ${{ inputs.backend_branch || github.ref }}

      - name: Checkout Frontend
        uses: actions/checkout@v4
        with:
          repository: your-org/frontend-repo
          path: frontend
          ref: ${{ inputs.frontend_branch || 'develop' }}

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
        run: |
          if [ "${{ inputs.test_group }}" == "all" ] || [ -z "${{ inputs.test_group }}" ]; then
            ./vendor/bin/pest tests/Browser
          else
            ./vendor/bin/pest tests/Browser --group=${{ inputs.test_group }}
          fi

      - name: Upload artifacts on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: browser-artifacts
          path: |
            backend/tests/Browser/screenshots/
            backend/storage/logs/
          retention-days: 7
```

## Tips & Best Practices

### Using --filter for Debugging

For quick debugging of a specific test:

```bash
# Via gh CLI
gh workflow run browser-tests.yml -f test_filter="user can login"

# In workflow
- name: Run browser tests
  run: ./vendor/bin/pest tests/Browser --filter="${{ inputs.test_filter }}"
```

### Combining with Matrix

Run manual tests against multiple PHP versions:

```yaml
on:
  workflow_dispatch:
    inputs:
      php_version:
        description: 'PHP version'
        required: false
        default: '8.3'
        type: choice
        options:
          - '8.2'
          - '8.3'
          - '8.4'

jobs:
  browser-tests:
    runs-on: ubuntu-latest
    steps:
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ inputs.php_version }}
```

### Notifications

Add Slack notification on completion:

```yaml
- name: Notify Slack
  if: always()
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    fields: repo,message,commit,author,action,eventName,ref,workflow
  env:
    SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

### Required vs Optional Inputs

- Use `required: false` with sensible `default` for flexibility
- Use `required: true` when the value must be explicitly chosen
- Use `type: choice` for predefined options (prevents typos)
- Use `type: string` for free-form input (branch names)

## Troubleshooting

### Checkout Fails: "The process '/usr/bin/git' failed"

**Symptom**: Workflow fails at checkout step with git exit code 1

**Common causes**:

1. **Wrong branch name**: Some repos use `main`, others use `master`
   ```yaml
   # Check your repo's default branch and use it as default
   inputs:
     backend_branch:
       default: 'master'  # or 'main' depending on your repo
   ```

2. **Private repository**: Need a PAT (Personal Access Token)
   ```yaml
   - uses: actions/checkout@v4
     with:
       repository: your-org/private-repo
       token: ${{ secrets.REPO_ACCESS_TOKEN }}
   ```

### Composer Install Fails: "Package requires PHP >= 8.4"

**Symptom**: `composer install` fails with PHP version requirement errors

**Cause**: Some packages (especially Symfony 8.x) require PHP 8.4+

**Solution**: Update the PHP version in your workflow:

```yaml
- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.4'  # Match your composer.lock requirements
```

::: tip Check Your Requirements
Run `composer show --locked | grep symfony` locally to see which Symfony version your project uses. Symfony 8.x requires PHP 8.4+.
:::
