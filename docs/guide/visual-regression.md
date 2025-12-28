# Visual Regression Testing

Detect unintended visual changes in your application by comparing screenshots against baseline images.

## How It Works

Visual regression testing captures screenshots of your pages and compares them pixel-by-pixel against previously saved "baseline" images.

<VisualRegressionDiagram />

**First Run:** No baseline exists → screenshot is saved as the new baseline
**Subsequent Runs:** Screenshot is compared against the baseline → pass if identical, fail if different

## Basic Usage

### Simple Comparison

```php
test('homepage visual appearance', function () {
    $this->bridge('/')
        ->assertScreenshotMatches();
});
```

### Full Page Screenshot

Capture the entire page, including content below the fold:

```php
test('full page layout', function () {
    $this->bridge('/pricing')
        ->assertScreenshotMatches(fullPage: true);
});
```

### Generate Diff Image

When a test fails, generate a visual diff showing what changed:

```php
test('dashboard layout', function () {
    $this->bridge('/dashboard')
        ->assertScreenshotMatches(fullPage: true, diff: true);
});
```

The diff image highlights changed pixels, making it easy to spot differences.

## Screenshot Storage

Screenshots are stored in the `tests/Browser/Screenshots` directory:

```
tests/Browser/Screenshots/
├── homepage-visual-appearance.png      # Baseline
├── full-page-layout.png                # Baseline
└── failures/
    ├── dashboard-layout.png            # Failed screenshot
    └── dashboard-layout-diff.png       # Diff image
```

### Git Configuration

Baseline images should be committed to version control (they're your "expected" results):

```gitignore
# .gitignore

# Ignore failure screenshots, keep baselines
tests/Browser/Screenshots/failures/
```

## Workflow

### 1. Creating Baselines

Run your tests for the first time to create baseline images:

```bash
./vendor/bin/pest tests/Browser/VisualTest.php
```

On first run, tests will pass and create baseline screenshots.

### 2. Developing with Visual Tests

As you develop, visual tests will catch unintended changes:

```bash
# Run visual tests
./vendor/bin/pest tests/Browser --group=visual

# If a test fails, review the diff
# Open tests/Browser/Screenshots/failures/
```

### 3. Updating Baselines

When you intentionally change the UI, update the baselines:

```bash
# Option 1: Delete old baseline and re-run
rm tests/Browser/Screenshots/homepage-visual-appearance.png
./vendor/bin/pest tests/Browser/VisualTest.php

# Option 2: Delete all baselines and regenerate
rm -rf tests/Browser/Screenshots/*.png
./vendor/bin/pest tests/Browser
```

### 4. Commit Updated Baselines

After verifying the new screenshots are correct:

```bash
git add tests/Browser/Screenshots/
git commit -m "Update visual regression baselines"
```

## Responsive Testing

Test visual appearance across different viewports:

```php
describe('Responsive Design', function () {
    test('mobile layout', function () {
        $this->bridge('/')
            ->on()->mobile()
            ->assertScreenshotMatches();
    });

    test('tablet layout', function () {
        $this->bridge('/')
            ->resize(768, 1024)
            ->assertScreenshotMatches();
    });

    test('desktop layout', function () {
        $this->bridge('/')
            ->resize(1920, 1080)
            ->assertScreenshotMatches();
    });
});
```

Each viewport generates its own baseline, allowing you to catch responsive design issues.

## Handling Dynamic Content

Dynamic content (timestamps, random data, animations) can cause false positives. Here are strategies to handle them:

### 1. Wait for Stability

Ensure animations and loading states complete:

```php
test('dashboard after load', function () {
    $this->bridge('/dashboard')
        ->waitForEvent('networkidle')
        ->wait(0.5) // Wait for animations
        ->assertScreenshotMatches();
});
```

### 2. Use Consistent Test Data

Use factories with fixed values:

```php
test('user profile', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'created_at' => '2024-01-01 00:00:00',
    ]);

    $this->bridge("/users/{$user->id}")
        ->assertScreenshotMatches();
});
```

### 3. Hide Dynamic Elements

Use JavaScript to hide or normalize dynamic content:

```php
test('page without timestamps', function () {
    $this->bridge('/dashboard')
        ->script("document.querySelectorAll('[data-testid=\"timestamp\"]').forEach(el => el.style.visibility = 'hidden')")
        ->assertScreenshotMatches();
});
```

### 4. Test Specific Elements

Instead of full page, test stable components:

```php
test('navigation bar', function () {
    $this->bridge('/dashboard')
        ->screenshotElement('[data-testid="navbar"]');

    // Compare element screenshot manually or use custom assertion
});
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Visual Regression Tests

on: [push, pull_request]

jobs:
  visual-tests:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install dependencies
        run: composer install

      - name: Install Playwright
        run: npx playwright install chromium --with-deps

      - name: Run visual tests
        run: ./vendor/bin/pest tests/Browser --group=visual

      - name: Upload failure screenshots
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: visual-regression-failures
          path: tests/Browser/Screenshots/failures/
```

### Reviewing Failures in CI

When visual tests fail in CI:

1. Download the failure artifacts
2. Review the diff images
3. If changes are intentional:
   - Update baselines locally
   - Commit and push
4. If changes are unintended:
   - Fix the UI bug
   - Re-run tests

## Best Practices

### 1. Group Visual Tests

```php
test('homepage appearance', function () {
    // ...
})->group('visual');
```

Run separately from functional tests:

```bash
# Fast functional tests
./vendor/bin/pest tests/Browser --exclude-group=visual

# Slower visual tests
./vendor/bin/pest tests/Browser --group=visual
```

### 2. Test Critical Pages Only

Visual regression adds overhead. Focus on:

- Landing pages
- Key user flows (checkout, signup)
- Component libraries
- Marketing pages

### 3. Use Consistent Environment

Visual differences can occur due to:

- Different fonts (install same fonts in CI)
- Different screen densities
- Anti-aliasing differences

Ensure your CI environment matches development:

```yaml
# Install fonts in CI
- name: Install fonts
  run: |
    sudo apt-get update
    sudo apt-get install -y fonts-liberation fonts-noto
```

### 4. Review Baselines in PRs

Add baseline changes to PR review checklist:

```markdown
## PR Checklist
- [ ] Visual regression baselines reviewed
- [ ] No unintended UI changes
```

## Troubleshooting

### Flaky Tests

If tests randomly fail due to minor pixel differences:

**Cause:** Animations, font rendering, or timing issues

**Solutions:**
1. Add waits for animations to complete
2. Disable animations in test environment
3. Use `waitForEvent('networkidle')`

```php
// Disable CSS animations
$this->bridge('/')
    ->script("document.head.insertAdjacentHTML('beforeend', '<style>*, *::before, *::after { animation: none !important; transition: none !important; }</style>')")
    ->assertScreenshotMatches();
```

### Different Results Locally vs CI

**Cause:** Different fonts, screen resolution, or browser versions

**Solutions:**
1. Use Docker for consistent environment
2. Install same fonts in CI
3. Pin Playwright version

### Large Baseline Files

**Cause:** Full-page screenshots of long pages

**Solutions:**
1. Use viewport-sized screenshots instead of full page
2. Test specific elements instead of full pages
3. Use Git LFS for large files

```bash
# Setup Git LFS for screenshots
git lfs install
git lfs track "tests/Browser/Screenshots/*.png"
```

## Example Test Suite

```php
<?php

declare(strict_types=1);

describe('Visual Regression', function () {
    beforeEach(function () {
        // Disable animations for consistent screenshots
        $this->disableAnimations = "
            document.head.insertAdjacentHTML('beforeend',
                '<style>*, *::before, *::after {
                    animation: none !important;
                    transition: none !important;
                }</style>'
            );
        ";
    });

    test('homepage', function () {
        $this->bridge('/')
            ->waitForEvent('networkidle')
            ->script($this->disableAnimations)
            ->assertScreenshotMatches(fullPage: true);
    })->group('visual');

    test('login page', function () {
        $this->bridge('/login')
            ->waitForEvent('networkidle')
            ->script($this->disableAnimations)
            ->assertScreenshotMatches();
    })->group('visual');

    test('mobile navigation', function () {
        $this->bridge('/')
            ->on()->mobile()
            ->waitForEvent('networkidle')
            ->script($this->disableAnimations)
            ->click('[data-testid="hamburger-menu"]')
            ->wait(0.3)
            ->assertScreenshotMatches();
    })->group('visual');

    test('dark mode', function () {
        $this->bridge('/')
            ->inDarkMode()
            ->waitForEvent('networkidle')
            ->script($this->disableAnimations)
            ->assertScreenshotMatches();
    })->group('visual');
})->group('visual');
```
