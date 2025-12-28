# Browser Options

Configure browser behavior, device emulation, and environment settings for your tests.

## Device Emulation

### Mobile Devices

Test on mobile viewports:

```php
$this->bridge('/')
    ->on()->mobile()
    ->assertSee('Mobile Menu');
```

### Specific Devices

Emulate specific devices:

```php
$this->bridge('/')
    ->on()->iPhone14Pro()
    ->assertVisible('[data-testid="mobile-nav"]');

$this->bridge('/')
    ->on()->macbook14()
    ->assertVisible('[data-testid="desktop-nav"]');
```

## Viewport Configuration

### Resize Window

Set custom viewport dimensions:

```php
$this->bridge('/')
    ->resize(1280, 720)
    ->assertVisible('[data-testid="sidebar"]');
```

### Common Viewport Sizes

```php
// Mobile
$this->bridge('/')->resize(375, 667);

// Tablet
$this->bridge('/')->resize(768, 1024);

// Desktop
$this->bridge('/')->resize(1920, 1080);
```

## Browser Selection

### Command Line

Run tests in different browsers:

```bash
# Chrome (default)
./vendor/bin/pest tests/Browser

# Firefox
./vendor/bin/pest tests/Browser --browser firefox

# Safari
./vendor/bin/pest tests/Browser --browser safari
```

### Configuration

Set default browser in `tests/Pest.php`:

```php
pest()->browser()->inFirefox();
```

## Display Mode

### Dark Mode

Test dark color scheme:

```php
$this->bridge('/')
    ->inDarkMode()
    ->assertVisible('[data-testid="dark-theme"]');
```

### Headed Mode

Open browser window during test:

```php
$this->bridge('/')
    ->headed()
    ->assertSee('Welcome');
```

Or via command line:

```bash
./vendor/bin/pest tests/Browser --headed
```

## Locale & Regional Settings

### Locale

Set browser locale:

```php
$this->bridge('/')
    ->withLocale('fr-FR')
    ->assertSee('Bienvenue');
```

### Timezone

Set browser timezone:

```php
$this->bridge('/')
    ->withTimezone('America/New_York')
    ->assertSee('EST');
```

### User Agent

Set custom user agent:

```php
$this->bridge('/')
    ->withUserAgent('CustomBot/1.0')
    ->assertSee('Welcome');
```

## Geolocation

Simulate geographic location:

```php
$this->bridge('/store-locator')
    ->geolocation(40.7128, -74.0060) // New York City
    ->assertSee('Stores near New York');
```

## Timeout Configuration

### Global Timeout

Set default timeout for all browser operations in `tests/Pest.php`:

```php
pest()->browser()->timeout(10000); // 10 seconds
```

### Per-Navigation Timeout

Override timeout for specific navigations:

```php
$this->bridge('/', options: ['timeout' => 60000]) // 60 seconds
    ->assertSee('Welcome');
```

This is useful for:
- Large pages with slow initial load
- Vite cold-start scenarios
- Heavy JavaScript applications

## Running Tests

### Parallel Execution

Speed up tests by running in parallel:

```bash
./vendor/bin/pest tests/Browser --parallel
```

### Filter by Group

Run specific test groups:

```bash
./vendor/bin/pest tests/Browser --group=checkout
```

### Stop on Failure

Stop running tests after first failure:

```bash
./vendor/bin/pest tests/Browser --stop-on-failure
```

## Configuration Examples

### Responsive Testing

Test across multiple viewports:

```php
describe('Responsive Navigation', function () {
    test('shows hamburger menu on mobile', function () {
        $this->bridge('/')
            ->on()->mobile()
            ->assertVisible('[data-testid="hamburger-menu"]')
            ->assertNotVisible('[data-testid="desktop-nav"]');
    });

    test('shows full nav on desktop', function () {
        $this->bridge('/')
            ->resize(1920, 1080)
            ->assertNotVisible('[data-testid="hamburger-menu"]')
            ->assertVisible('[data-testid="desktop-nav"]');
    });
});
```

### Internationalization Testing

Test localized content:

```php
describe('Localization', function () {
    test('displays French content', function () {
        $this->bridge('/')
            ->withLocale('fr-FR')
            ->assertSee('Bienvenue');
    });

    test('displays German content', function () {
        $this->bridge('/')
            ->withLocale('de-DE')
            ->assertSee('Willkommen');
    });
});
```

### Dark Mode Testing

Test theme switching:

```php
describe('Theme', function () {
    test('respects dark mode preference', function () {
        $this->bridge('/')
            ->inDarkMode()
            ->assertVisible('[data-theme="dark"]');
    });

    test('defaults to light mode', function () {
        $this->bridge('/')
            ->assertVisible('[data-theme="light"]');
    });
});
```

### Location-Based Testing

Test geolocation features:

```php
describe('Store Locator', function () {
    test('shows nearby stores in New York', function () {
        $this->bridge('/stores')
            ->geolocation(40.7128, -74.0060)
            ->assertSee('Manhattan Store')
            ->assertSee('Brooklyn Store');
    });

    test('shows nearby stores in London', function () {
        $this->bridge('/stores')
            ->geolocation(51.5074, -0.1278)
            ->assertSee('London Store');
    });
});
```
