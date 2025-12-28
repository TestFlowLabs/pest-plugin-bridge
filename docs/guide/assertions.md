# Assertions

Pest Plugin Bridge inherits all assertions from Pest's browser plugin. Here's a comprehensive reference.

## Text Assertions

### assertSee

Assert that text is visible on the page:

```php
$this->bridge('/')
    ->assertSee('Welcome');
```

### assertDontSee

Assert that text is NOT visible on the page:

```php
$this->bridge('/')
    ->assertDontSee('Error');
```

### assertSeeIn

Assert that text is visible within a specific element:

```php
$this->bridge('/dashboard')
    ->assertSeeIn('[data-testid="header"]', 'Dashboard')
    ->assertSeeIn('.user-name', 'John Doe');
```

## Element Assertions

### assertVisible

Assert that an element is visible:

```php
$this->bridge('/login')
    ->assertVisible('[data-testid="login-form"]')
    ->assertVisible('input[type="email"]')
    ->assertVisible('button[type="submit"]');
```

### assertNotVisible

Assert that an element is NOT visible:

```php
$this->bridge('/')
    ->assertNotVisible('[data-testid="modal"]')
    ->assertNotVisible('.error-message');
```

### assertPresent

Assert that an element exists in the DOM (even if not visible):

```php
$this->bridge('/')
    ->assertPresent('[data-testid="hidden-field"]');
```

### assertMissing

Assert that an element does NOT exist in the DOM:

```php
$this->bridge('/')
    ->assertMissing('[data-testid="admin-panel"]');
```

## URL Assertions

### assertPathContains

Assert that the current URL path contains a string:

```php
$this->bridge('/login')
    ->fill('[data-testid="email"]', 'user@example.com')
    ->fill('[data-testid="password"]', 'password')
    ->click('[data-testid="login-button"]')
    ->wait(2)
    ->assertPathContains('/dashboard');
```

### assertPathIs

Assert that the current URL path exactly matches:

```php
$this->bridge('/')
    ->assertPathIs('/');
```

### assertQueryStringHas

Assert that the URL has a query parameter:

```php
$this->bridge('/search?q=test')
    ->assertQueryStringHas('q', 'test');
```

## Form Assertions

### assertValue

Assert that an input has a specific value:

```php
$this->bridge('/profile')
    ->assertValue('[data-testid="email-input"]', 'user@example.com');
```

### assertChecked

Assert that a checkbox is checked:

```php
$this->bridge('/settings')
    ->assertChecked('[data-testid="newsletter-checkbox"]');
```

### assertNotChecked

Assert that a checkbox is NOT checked:

```php
$this->bridge('/settings')
    ->assertNotChecked('[data-testid="marketing-checkbox"]');
```

### assertSelected

Assert that a select option is selected:

```php
$this->bridge('/settings')
    ->assertSelected('[data-testid="language-select"]', 'en');
```

## Page Assertions

### assertTitle

Assert the page title:

```php
$this->bridge('/')
    ->assertTitle('Home - My App');
```

### assertTitleContains

Assert the page title contains text:

```php
$this->bridge('/dashboard')
    ->assertTitleContains('Dashboard');
```

## Attribute Assertions

### assertAttribute

Assert that an element has a specific attribute value:

```php
$this->bridge('/form')
    ->assertAttribute('[data-testid="email-input"]', 'type', 'email')
    ->assertAttribute('[data-testid="submit-button"]', 'disabled', 'true');
```

## Chaining Assertions

All assertions are chainable:

```php
$this->bridge('/dashboard')
    ->assertTitle('Dashboard - My App')
    ->assertSee('Welcome back')
    ->assertVisible('[data-testid="user-menu"]')
    ->assertSeeIn('[data-testid="sidebar"]', 'Settings')
    ->assertNotVisible('[data-testid="loading-spinner"]');
```

## Assertions with Actions

Combine assertions with actions:

```php
test('complete user flow', function () {
    $this->bridge('/login')
        ->assertVisible('[data-testid="login-form"]')
        ->fill('[data-testid="email"]', 'user@example.com')
        ->assertValue('[data-testid="email"]', 'user@example.com')
        ->fill('[data-testid="password"]', 'password')
        ->click('[data-testid="submit"]')
        ->wait(2)
        ->assertPathContains('/dashboard')
        ->assertSee('Welcome')
        ->assertNotVisible('[data-testid="login-form"]');
});
```

## Custom Assertion Messages

For clearer test failures, assertions support custom messages:

```php
$this->bridge('/')
    ->assertSee('Welcome', 'Homepage should display welcome message');
```

## Screenshot on Failure

When an assertion fails, Pest automatically captures a screenshot. Find them in:

```
Tests/Browser/Screenshots/
```

The filename corresponds to the test name, making it easy to debug failures.