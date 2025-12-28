# Writing Tests

Learn how to write effective browser tests for your external frontend applications.

## Basic Structure

A browser test uses `bridge()` to navigate to your frontend:

```php
test('homepage loads correctly', function () {
    $this->bridge('/')
        ->assertSee('Welcome');
});
```

## The bridge() Method

`bridge()` is the core method provided by the plugin. It:

1. Takes a path (e.g., `/login`, `/dashboard`)
2. Prepends your configured base URL
3. Returns a Pest browser page object for chaining

```php
// If base URL is http://localhost:5173
$this->bridge('/login');
// Actually visits: http://localhost:5173/login
```

## Page Navigation

### Basic Navigation

```php
test('navigation works', function () {
    $this->bridge('/')
        ->click('a[href="/about"]')
        ->assertPathContains('/about');
});
```

### Direct Navigation

```php
test('can access different pages', function () {
    $this->bridge('/products')
        ->assertSee('Products');

    $this->bridge('/contact')
        ->assertSee('Contact Us');
});
```

## Form Interactions

### Filling Inputs

::: warning Vue/React/Nuxt Users
Use `typeSlowly()` instead of `fill()` for reactive frameworks. Vue's `v-model` and React's controlled inputs don't sync with `fill()` because it sets DOM values directly without firing input events.
:::

```php
// For non-reactive forms (plain HTML):
$this->bridge('/register')
    ->fill('input[name="name"]', 'John Doe');

// For Vue, React, Nuxt, Next.js (recommended):
$this->bridge('/register')
    ->waitForEvent('networkidle')
    ->click('input[name="name"]')
    ->typeSlowly('input[name="name"]', 'John Doe', 20);
```

::: tip Detailed Framework Guide
For comprehensive explanation of `fill()` vs `typeSlowly()` behavior and complete working patterns for Vue/React, see [Best Practices: Vue/Nuxt Framework-Specific](/guide/best-practices#vue-nuxt-framework-specific-best-practices).
:::

### Using Data Test IDs (Recommended)

```php
test('can fill form with data-testid', function () {
    $this->bridge('/register')
        ->fill('[data-testid="name-input"]', 'John Doe')
        ->fill('[data-testid="email-input"]', 'john@example.com')
        ->fill('[data-testid="password-input"]', 'secret123');
});
```

### Clicking Buttons

```php
test('can submit form', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="submit-button"]');
});
```

### Selecting Options

```php
test('can select from dropdown', function () {
    $this->bridge('/settings')
        ->select('[data-testid="language-select"]', 'en')
        ->select('[data-testid="timezone-select"]', 'UTC');
});
```

### Checkboxes and Radio Buttons

```php
test('can interact with checkboxes', function () {
    $this->bridge('/preferences')
        ->check('[data-testid="newsletter-checkbox"]')
        ->uncheck('[data-testid="marketing-checkbox"]');
});
```

### Radio Buttons

```php
test('can select radio options', function () {
    $this->bridge('/survey')
        ->radio('size', 'large')
        ->radio('color', 'blue');
});
```

### Appending Text

Add text to an input without clearing existing content:

```php
$this->bridge('/editor')
    ->append('[data-testid="description"]', ' Additional text');
```

### Clearing Fields

Clear an input field:

```php
$this->bridge('/search')
    ->clear('[data-testid="search-input"]');
```

### Pressing Buttons

Press a button by its visible text:

```php
$this->bridge('/checkout')
    ->press('Complete Order');
```

### Press and Wait

Press a button and wait for a specified duration:

```php
$this->bridge('/checkout')
    ->pressAndWaitFor('Submit', 2); // Press and wait 2 seconds
```

### Form Submission

Submit the first form on the page:

```php
$this->bridge('/contact')
    ->fill('[data-testid="email"]', 'user@example.com')
    ->fill('[data-testid="message"]', 'Hello!')
    ->submit();
```

### File Uploads

Attach a file to a file input:

```php
$this->bridge('/profile')
    ->attach('[data-testid="avatar-input"]', '/path/to/image.jpg');
```

## Waiting Strategies

### Fixed Wait

Use `wait()` for simple timing:

```php
test('waits for animation', function () {
    $this->bridge('/dashboard')
        ->click('[data-testid="menu-toggle"]')
        ->wait(0.5) // Wait 500ms for animation
        ->assertVisible('[data-testid="sidebar"]');
});
```

### Wait for Element

Use `assertVisible()` which waits for elements:

```php
test('waits for element to appear', function () {
    $this->bridge('/search')
        ->fill('[data-testid="search-input"]', 'test')
        ->click('[data-testid="search-button"]')
        ->assertVisible('[data-testid="search-results"]'); // Waits automatically
});
```

### Wait After Actions

For async operations, add waits after actions:

```php
test('handles async login', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2) // Wait for API call and redirect
        ->assertPathContains('/dashboard');
});
```

## Working with Text

### Assert Text Visible

```php
test('displays welcome message', function () {
    $this->bridge('/')
        ->assertSee('Welcome to our app')
        ->assertDontSee('Error');
});
```

### Assert Text in Element

```php
test('displays user name in header', function () {
    $this->bridge('/dashboard')
        ->assertSeeIn('[data-testid="user-greeting"]', 'Hello, John');
});
```

## Element Visibility

```php
test('modal opens and closes', function () {
    $this->bridge('/page')
        ->assertNotVisible('[data-testid="modal"]')
        ->click('[data-testid="open-modal"]')
        ->assertVisible('[data-testid="modal"]')
        ->click('[data-testid="close-modal"]')
        ->assertNotVisible('[data-testid="modal"]');
});
```

## URL Assertions

```php
test('redirects after login', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertPathContains('/dashboard')
        ->assertPathIs('/dashboard');
});
```

## Getting Element Values

### Get Element Text

Retrieve the text content of an element:

```php
$this->bridge('/dashboard')
    ->text('[data-testid="welcome-message"]'); // Returns "Welcome, John"
```

### Get Input Value

Retrieve the current value of an input:

```php
$this->bridge('/profile')
    ->value('[data-testid="email-input"]'); // Returns "user@example.com"
```

### Get Attribute Value

Retrieve an attribute value from an element:

```php
$this->bridge('/products')
    ->attribute('[data-testid="product-image"]', 'alt'); // Returns "Product Name"
```

### Get Page Content

Retrieve the full HTML content of the page:

```php
$html = $this->bridge('/')
    ->content();
```

### Get Current URL

Retrieve the current page URL:

```php
$url = $this->bridge('/dashboard')
    ->url();
```

## Advanced Interactions

### Hover

Hover over an element:

```php
$this->bridge('/menu')
    ->hover('[data-testid="dropdown-trigger"]')
    ->assertVisible('[data-testid="dropdown-menu"]');
```

### Drag and Drop

Drag an element to a target:

```php
$this->bridge('/kanban')
    ->drag('[data-testid="task-1"]', '[data-testid="done-column"]');
```

### Keyboard Input

Send keyboard events to an element:

```php
// Type text with keyboard
$this->bridge('/editor')
    ->keys('[data-testid="editor"]', 'Hello World');

// Keyboard shortcuts
$this->bridge('/editor')
    ->keys('[data-testid="editor"]', ['{Control}', 'a']); // Select all
```

### Hold Key During Actions

Perform actions while holding a key:

```php
$this->bridge('/file-manager')
    ->withKeyDown('Shift', function ($page) {
        $page->click('[data-testid="file-1"]')
             ->click('[data-testid="file-5"]'); // Multi-select
    });
```

## Working with iFrames

Interact with content inside iframes:

```php
$this->bridge('/embedded')
    ->withinIframe('[data-testid="iframe-container"]', function ($page) {
        $page->fill('[data-testid="name"]', 'John Doe')
             ->click('[data-testid="submit"]');
    });
```

## JavaScript Execution

Execute JavaScript on the page:

```php
// Get a value
$title = $this->bridge('/')
    ->script('document.title');

// Modify the page
$this->bridge('/')
    ->script('document.body.style.backgroundColor = "red"');
```

## In-Page Navigation

Navigate within the same browser context (maintains state):

```php
$this->bridge('/login')
    ->typeSlowly('[data-testid="email"]', 'user@example.com', 20)
    ->typeSlowly('[data-testid="password"]', 'password', 20)
    ->click('[data-testid="login-button"]')
    ->navigate('/dashboard') // Navigates without losing session
    ->assertSee('Welcome');
```

## Complete Example

Here's a complete test file demonstrating various patterns:

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Product;

describe('E-commerce checkout flow', function () {
    beforeEach(function () {
        // Setup test data in Laravel
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 29.99,
        ]);
    });

    test('guest can browse products', function () {
        $this->bridge('/products')
            ->assertSee('Test Product')
            ->assertSee('$29.99');
    });

    test('guest can add product to cart', function () {
        $this->bridge('/products')
            ->click("[data-testid=\"product-{$this->product->id}\"]")
            ->assertPathContains('/products/')
            ->click('[data-testid="add-to-cart"]')
            ->wait(1)
            ->assertSee('Added to cart');
    });

    test('user can complete checkout', function () {
        $this->bridge('/login')
            ->waitForEvent('networkidle')
            ->click('[data-testid="email"]')
            ->typeSlowly('[data-testid="email"]', $this->user->email, 20)
            ->typeSlowly('[data-testid="password"]', 'password', 20)
            ->click('[data-testid="login-button"]')
            ->waitForEvent('networkidle')
            ->assertPathContains('/dashboard');

        $this->bridge('/cart')
            ->click('[data-testid="checkout-button"]')
            ->waitForEvent('networkidle')
            ->typeSlowly('[data-testid="card-number"]', '4242424242424242', 20)
            ->typeSlowly('[data-testid="card-expiry"]', '12/25', 20)
            ->typeSlowly('[data-testid="card-cvc"]', '123', 20)
            ->click('[data-testid="pay-button"]')
            ->waitForEvent('networkidle')
            ->assertSee('Order confirmed');
    });
});
```