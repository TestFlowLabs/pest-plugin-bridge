# Assertions

Pest Bridge Plugin inherits all assertions from Pest's browser plugin. Here's a comprehensive reference.

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

### assertDontSeeIn

Assert that text is NOT visible within a specific element:

```php
$this->bridge('/dashboard')
    ->assertDontSeeIn('[data-testid="error-container"]', 'Error occurred');
```

### assertSeeAnythingIn

Assert that an element contains any text content:

```php
$this->bridge('/dashboard')
    ->assertSeeAnythingIn('[data-testid="content"]');
```

### assertSeeNothingIn

Assert that an element is empty (contains no text):

```php
$this->bridge('/form')
    ->assertSeeNothingIn('[data-testid="error-message"]');
```

### assertSeeLink

Assert that a link with specific text is visible:

```php
$this->bridge('/')
    ->assertSeeLink('About Us')
    ->assertSeeLink('Contact');
```

### assertDontSeeLink

Assert that a link with specific text is NOT visible:

```php
$this->bridge('/')
    ->assertDontSeeLink('Admin Panel');
```

### assertSourceHas

Assert that the page source contains specific HTML:

```php
$this->bridge('/')
    ->assertSourceHas('<h1>Welcome</h1>')
    ->assertSourceHas('data-testid="main-content"');
```

### assertSourceMissing

Assert that the page source does NOT contain specific HTML:

```php
$this->bridge('/')
    ->assertSourceMissing('<div class="error">');
```

### assertCount

Assert the number of elements matching a selector:

```php
$this->bridge('/products')
    ->assertCount('[data-testid="product-card"]', 5)
    ->assertCount('.cart-item', 3);
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

### assertNotPresent

Assert that an element is NOT present in the DOM (alias for assertMissing):

```php
$this->bridge('/')
    ->assertNotPresent('[data-testid="deleted-item"]');
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

// Check parameter exists without value
$this->bridge('/search?q=test')
    ->assertQueryStringHas('q');
```

### assertQueryStringMissing

Assert that the URL does NOT have a query parameter:

```php
$this->bridge('/products')
    ->assertQueryStringMissing('page');
```

### assertUrlIs

Assert the full URL matches exactly:

```php
$this->bridge('/dashboard')
    ->assertUrlIs('http://localhost:5173/dashboard');
```

### assertSchemeIs

Assert the URL scheme:

```php
$this->bridge('/')
    ->assertSchemeIs('https');
```

### assertSchemeIsNot

Assert the URL scheme is NOT a specific value:

```php
$this->bridge('/')
    ->assertSchemeIsNot('http');
```

### assertHostIs

Assert the URL host:

```php
$this->bridge('/')
    ->assertHostIs('localhost');
```

### assertHostIsNot

Assert the URL host is NOT a specific value:

```php
$this->bridge('/')
    ->assertHostIsNot('example.com');
```

### assertPortIs

Assert the URL port:

```php
$this->bridge('/')
    ->assertPortIs('5173');
```

### assertPortIsNot

Assert the URL port is NOT a specific value:

```php
$this->bridge('/')
    ->assertPortIsNot('3000');
```

### assertPathIsNot

Assert the URL path is NOT a specific value:

```php
$this->bridge('/dashboard')
    ->assertPathIsNot('/login');
```

### assertPathBeginsWith

Assert the URL path starts with a string:

```php
$this->bridge('/users/123/profile')
    ->assertPathBeginsWith('/users');
```

### assertPathEndsWith

Assert the URL path ends with a string:

```php
$this->bridge('/users/123/profile')
    ->assertPathEndsWith('/profile');
```

### assertFragmentIs

Assert the URL fragment (hash):

```php
$this->bridge('/docs#installation')
    ->assertFragmentIs('installation');
```

### assertFragmentIsNot

Assert the URL fragment is NOT a specific value:

```php
$this->bridge('/docs#installation')
    ->assertFragmentIsNot('getting-started');
```

### assertFragmentBeginsWith

Assert the URL fragment starts with a string:

```php
$this->bridge('/docs#section-2')
    ->assertFragmentBeginsWith('section');
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

### assertNotSelected

Assert that a select option is NOT selected:

```php
$this->bridge('/settings')
    ->assertNotSelected('[data-testid="country-select"]', 'UK');
```

### assertValueIsNot

Assert that an input does NOT have a specific value:

```php
$this->bridge('/profile')
    ->assertValueIsNot('[data-testid="email-input"]', 'invalid@example.com');
```

### assertIndeterminate

Assert that a checkbox is in indeterminate state:

```php
$this->bridge('/tree-view')
    ->assertIndeterminate('[data-testid="parent-checkbox"]');
```

### assertRadioSelected

Assert that a radio button option is selected:

```php
$this->bridge('/survey')
    ->assertRadioSelected('size', 'large');
```

### assertRadioNotSelected

Assert that a radio button option is NOT selected:

```php
$this->bridge('/survey')
    ->assertRadioNotSelected('size', 'small');
```

### assertEnabled

Assert that a form field is enabled:

```php
$this->bridge('/form')
    ->assertEnabled('[data-testid="email-input"]');
```

### assertDisabled

Assert that a form field is disabled:

```php
$this->bridge('/form')
    ->assertDisabled('[data-testid="submit-button"]');
```

### assertButtonEnabled

Assert that a button is enabled (by button text):

```php
$this->bridge('/checkout')
    ->assertButtonEnabled('Complete Order');
```

### assertButtonDisabled

Assert that a button is disabled (by button text):

```php
$this->bridge('/checkout')
    ->assertButtonDisabled('Complete Order');
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

### assertAttributeMissing

Assert that an element does NOT have a specific attribute:

```php
$this->bridge('/form')
    ->assertAttributeMissing('[data-testid="submit-button"]', 'disabled');
```

### assertAttributeContains

Assert that an attribute value contains a string:

```php
$this->bridge('/dashboard')
    ->assertAttributeContains('[data-testid="container"]', 'class', 'active');
```

### assertAttributeDoesntContain

Assert that an attribute value does NOT contain a string:

```php
$this->bridge('/dashboard')
    ->assertAttributeDoesntContain('[data-testid="container"]', 'class', 'hidden');
```

### assertAriaAttribute

Assert an ARIA attribute value:

```php
$this->bridge('/modal')
    ->assertAriaAttribute('[data-testid="close-button"]', 'label', 'Close dialog');
```

### assertDataAttribute

Assert a data attribute value:

```php
$this->bridge('/products')
    ->assertDataAttribute('[data-testid="product-card"]', 'id', '123')
    ->assertDataAttribute('[data-testid="product-card"]', 'category', 'electronics');
```

## Quality Assertions

### assertNoSmoke

Assert no console logs or JavaScript errors on the page:

```php
$this->bridge('/')
    ->assertNoSmoke();
```

### assertNoConsoleLogs

Assert no console logs on the page:

```php
$this->bridge('/dashboard')
    ->assertNoConsoleLogs();
```

### assertNoJavaScriptErrors

Assert no JavaScript errors on the page:

```php
$this->bridge('/app')
    ->assertNoJavaScriptErrors();
```

### assertNoAccessibilityIssues

Assert no accessibility issues (WCAG compliance). Levels: 0 (critical), 1 (serious), 2 (moderate), 3 (minor):

```php
// Default level 1 (serious issues and above)
$this->bridge('/')
    ->assertNoAccessibilityIssues();

// Only critical issues (level 0)
$this->bridge('/')
    ->assertNoAccessibilityIssues(0);

// All issues including minor (level 3)
$this->bridge('/')
    ->assertNoAccessibilityIssues(3);
```

### assertScreenshotMatches

Assert that the current page matches a baseline screenshot (visual regression testing):

```php
// Basic screenshot comparison
$this->bridge('/dashboard')
    ->assertScreenshotMatches();

// Full page screenshot with diff output
$this->bridge('/dashboard')
    ->assertScreenshotMatches(fullPage: true, diff: true);
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