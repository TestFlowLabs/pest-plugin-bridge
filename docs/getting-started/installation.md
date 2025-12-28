# Installation

## Requirements

Before installing Pest Bridge Plugin, ensure you have:

| Requirement | Version |
|-------------|---------|
| PHP | 8.3+ |
| Pest | 4.0+ |
| Composer | 2.0+ |
| Node.js | 18+ |

## Step 1: Install the Plugin

Install the plugin via Composer:

```bash
composer require testflowlabs/pest-plugin-bridge --dev
```

This will also install the required `pestphp/pest-plugin-browser` dependency.

## Step 2: Install Playwright

The browser testing functionality uses Playwright under the hood. Install it via npm:

```bash
npm install playwright
npx playwright install chromium
```

::: tip Installing All Browsers
If you want to test across multiple browsers, install them all:
```bash
npx playwright install
```
This installs Chromium, Firefox, and WebKit.
:::

## Step 3: Configure the External URL

Configure in your `tests/Pest.php` file:

```php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::add('http://localhost:5173');
```

## Step 4: Verify Installation

Create a simple test to verify everything works:

```php
// tests/Browser/ExampleTest.php
<?php

test('can visit external frontend', function () {
    $this->bridge('/')
        ->assertSee('Welcome');
});
```

Run it:

```bash
./vendor/bin/pest tests/Browser/ExampleTest.php
```

::: tip Automatic Server Management
With `->serve()` configuration, the frontend starts automatically when tests run. No need to manually start servers!

```php
// tests/Pest.php
Bridge::add('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('ready|localhost');
```
:::

## Project Structure

After installation, your project should look like this:

```
your-project/
+-- tests/
|   +-- Pest.php              # Plugin configuration
|   +-- Browser/
|   |   +-- ExampleTest.php   # Browser tests
|   +-- Feature/
|   +-- Unit/
+-- composer.json
+-- package.json              # Playwright dependency
```

## Troubleshooting

### Playwright Not Found

If you get "Playwright is not installed" error:

```bash
npm install playwright
npx playwright install chromium
```

### Browser Launch Fails

On Linux servers, you may need additional dependencies:

```bash
npx playwright install-deps chromium
```

### Connection Refused

Ensure your frontend is running and accessible:

```bash
curl http://localhost:5173
```
