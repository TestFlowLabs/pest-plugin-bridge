# HTTP Mocking

When testing frontend applications, you often need to mock external HTTP calls. The Bridge plugin provides two complementary approaches:

| Method | Intercepts | Use Case |
|--------|-----------|----------|
| `Bridge::fake()` | Laravel backend → External APIs | Payment gateways, email services, etc. |
| `Bridge::mockBrowser()` | Browser JavaScript → External APIs | Public APIs called directly from frontend |

<HttpMockingOverviewDiagram />

## Understanding the Difference

### Backend Mocking (`Bridge::fake()`)

When your **Laravel backend** calls external APIs (like Stripe, SendGrid):

```
Browser → Nuxt → Laravel API → Stripe API
                     ↑
              Bridge::fake() intercepts here
```

### Frontend Mocking (`Bridge::mockBrowser()`)

When your **frontend JavaScript** calls external APIs directly:

```
Browser → fetch('https://api.weather.com/...') → Weather API
              ↑
       Bridge::mockBrowser() intercepts here
```

---

## Backend HTTP Mocking

Use `Bridge::fake()` when your Laravel application makes HTTP calls to external services.

### The Challenge

In browser tests, your Laravel application runs in a separate PHP process from your test. Laravel's built-in `Http::fake()` won't work — the fake you register in your test isn't visible to the server process.

<HttpFakingChallengeDiagram />

### Solution

`Bridge::fake()` writes fake configuration to a temp file that a Laravel middleware reads:

<HttpFakingSolutionDiagram />

### Setup

#### 1. Register the Middleware

Add the middleware to your Laravel application (testing environment only):

::: code-group

```php [Laravel 11+ (bootstrap/app.php)]
use TestFlowLabs\PestPluginBridge\Laravel\BridgeHttpFakeMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        if (app()->environment('testing')) {
            $middleware->prepend(BridgeHttpFakeMiddleware::class);
        }
    })
    ->create();
```

```php [Laravel 10 (app/Http/Kernel.php)]
protected $middleware = [
    // Only in testing...
    \TestFlowLabs\PestPluginBridge\Laravel\BridgeHttpFakeMiddleware::class,
    // ... other middleware
];
```

:::

::: tip Environment Check
Always wrap the middleware registration with an environment check. You don't want this middleware running in production.
:::

#### 2. Use in Tests

```php
use TestFlowLabs\PestPluginBridge\Bridge;

test('checkout process with successful payment', function () {
    Bridge::fake([
        'https://api.stripe.com/*' => [
            'status' => 200,
            'body' => [
                'id' => 'ch_test_123',
                'status' => 'succeeded',
                'amount' => 2999,
            ],
        ],
    ]);

    $this->bridge('/checkout')
        ->fill('[data-testid="card-number"]', '4242424242424242')
        ->click('[data-testid="pay-button"]')
        ->assertSee('Payment Successful');
});
```

### Configuration Options

Each fake accepts these options:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `status` | `int` | `200` | HTTP status code |
| `body` | `array` | `[]` | Response body (JSON) |
| `headers` | `array` | `[]` | Response headers |

```php
Bridge::fake([
    'https://api.stripe.com/v1/charges' => [
        'status' => 201,
        'body' => ['id' => 'ch_123', 'amount' => 1000],
        'headers' => ['X-Request-Id' => 'req_abc123'],
    ],
]);
```

### API Reference

```php
// Register fakes
Bridge::fake([...]);

// Check if fakes are registered
Bridge::hasFakes();

// Get current fake configuration
Bridge::getFakes();

// Clear all fakes (automatic on test end)
Bridge::clearFakes();
```

---

## Frontend/Browser HTTP Mocking

Use `Bridge::mockBrowser()` when your frontend JavaScript makes direct HTTP calls to external APIs.

### When to Use

- **Weather APIs** - Frontend fetches weather data directly
- **Public APIs** - Quote of the day, currency rates, etc.
- **Third-party widgets** - Analytics, chat widgets, etc.
- **CDN resources** - When you need to mock CDN responses

### How It Works

`Bridge::mockBrowser()` injects a JavaScript interceptor into the browser that patches `fetch()` and `XMLHttpRequest` before your page JavaScript runs:

```
┌─────────────────────────────────────────────────────────────────┐
│  Browser Context                                                │
├─────────────────────────────────────────────────────────────────┤
│  1. Bridge injects mock interceptor script                      │
│  2. Script patches window.fetch and XMLHttpRequest              │
│  3. Page loads and JavaScript runs                              │
│  4. fetch('https://api.weather.com/...') is called              │
│  5. Interceptor checks URL against registered patterns          │
│  6. If match found → Return mock response                       │
│  7. If no match → Original fetch proceeds                       │
└─────────────────────────────────────────────────────────────────┘
```

### Basic Usage

```php
use TestFlowLabs\PestPluginBridge\Bridge;

test('displays weather from external API', function () {
    Bridge::mockBrowser([
        'https://api.weather.com/*' => [
            'status' => 200,
            'body' => [
                'city' => 'Istanbul',
                'temperature' => 25,
                'condition' => 'Sunny',
            ],
        ],
    ]);

    $this->bridge('/weather')
        ->waitForEvent('networkidle')
        ->assertSee('Istanbul')
        ->assertSee('25°')
        ->assertSee('Sunny');
});
```

::: tip Automatic Application
When browser mocks are registered, `bridge()` automatically uses the mocking context. You don't need a separate method.
:::

### Configuration Options

Same as backend mocking:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `status` | `int` | `200` | HTTP status code |
| `body` | `mixed` | `{}` | Response body (will be JSON stringified) |
| `headers` | `array` | `[]` | Response headers |

### URL Pattern Matching

Patterns support wildcards (`*`) for flexible matching:

```php
Bridge::mockBrowser([
    // Match all requests to this domain
    'https://api.weather.com/*' => [...],

    // Match specific endpoint
    'https://api.quotable.io/random' => [...],

    // Match with path wildcards
    'https://api.example.com/users/*/profile' => [...],
]);
```

### Error Handling

Test how your frontend handles API failures:

```php
test('shows error when weather API fails', function () {
    Bridge::mockBrowser([
        'https://api.weather.com/*' => [
            'status' => 503,
            'body' => ['error' => 'Service unavailable'],
        ],
    ]);

    $this->bridge('/weather')
        ->waitForEvent('networkidle')
        ->assertSee('Unable to load weather data');
});

test('handles network timeout gracefully', function () {
    Bridge::mockBrowser([
        'https://api.weather.com/*' => [
            'status' => 408,
            'body' => ['error' => 'Request timeout'],
        ],
    ]);

    $this->bridge('/weather')
        ->waitForEvent('networkidle')
        ->assertSee('Please try again');
});
```

### API Reference

```php
// Register browser mocks
Bridge::mockBrowser([...]);

// Check if browser mocks are registered
Bridge::hasBrowserMocks();

// Clear all browser mocks (automatic on test end)
Bridge::clearBrowserMocks();
```

### Best Practices

#### 1. Use `waitForEvent('networkidle')`

When testing pages that make async API calls, wait for network activity to complete:

```php
$this->bridge('/dashboard')
    ->waitForEvent('networkidle')  // Wait for API calls to complete
    ->assertSee('Data loaded');
```

#### 2. Clear Mocks Between Tests

Use `beforeEach` to ensure clean state:

```php
beforeEach(function () {
    Bridge::clearBrowserMocks();
});
```

#### 3. Match Specific Patterns

Be as specific as possible with URL patterns to avoid unintended matches:

```php
// Too broad - might catch unrelated requests
Bridge::mockBrowser([
    'https://*' => [...],  // Don't do this!
]);

// Better - specific domain and path
Bridge::mockBrowser([
    'https://api.weather.com/v1/forecast' => [...],
]);
```

---

## Combined Usage

You can use both `Bridge::fake()` and `Bridge::mockBrowser()` in the same test when your application makes both backend and frontend API calls:

```php
test('dashboard loads weather and processes payment', function () {
    // Backend: Laravel → Stripe
    Bridge::fake([
        'https://api.stripe.com/*' => [
            'status' => 200,
            'body' => ['balance' => 10000],
        ],
    ]);

    // Frontend: Browser → Weather API
    Bridge::mockBrowser([
        'https://api.weather.com/*' => [
            'status' => 200,
            'body' => ['temperature' => 22],
        ],
    ]);

    $this->bridge('/dashboard')
        ->waitForEvent('networkidle')
        ->assertSee('Balance: $100.00')  // From Stripe (backend)
        ->assertSee('22°C');             // From Weather API (frontend)
});
```

### When to Use Which

| Scenario | Method |
|----------|--------|
| Laravel calls Stripe API | `Bridge::fake()` |
| Laravel calls SendGrid | `Bridge::fake()` |
| Vue/React fetches from public API | `Bridge::mockBrowser()` |
| JavaScript loads data from CDN | `Bridge::mockBrowser()` |
| Laravel proxies an external API | `Bridge::fake()` |
| Frontend calls your Laravel API | Neither (real call) |

---

## Common Use Cases

### Payment Gateway (Backend)

```php
test('handles payment failure gracefully', function () {
    Bridge::fake([
        'https://api.stripe.com/*' => [
            'status' => 402,
            'body' => [
                'error' => [
                    'type' => 'card_error',
                    'message' => 'Your card was declined.',
                ],
            ],
        ],
    ]);

    $this->bridge('/checkout')
        ->fill('[data-testid="card-number"]', '4000000000000002')
        ->click('[data-testid="pay-button"]')
        ->assertSee('Your card was declined');
});
```

### Quote of the Day (Frontend)

```php
test('displays inspirational quote', function () {
    Bridge::mockBrowser([
        'https://api.quotable.io/*' => [
            'status' => 200,
            'body' => [
                'content' => 'The only way to do great work is to love what you do.',
                'author' => 'Steve Jobs',
            ],
        ],
    ]);

    $this->bridge('/home')
        ->waitForEvent('networkidle')
        ->assertSee('The only way to do great work')
        ->assertSee('Steve Jobs');
});
```

### Geolocation Service (Backend)

```php
test('shows user location from IP', function () {
    Bridge::fake([
        'https://ipapi.co/*' => [
            'status' => 200,
            'body' => [
                'country_name' => 'Turkey',
                'city' => 'Istanbul',
                'latitude' => 41.0082,
                'longitude' => 28.9784,
            ],
        ],
    ]);

    $this->bridge('/location')
        ->assertSee('Istanbul, Turkey');
});
```

---

## Limitations

### Backend Mocking (`Bridge::fake()`)

1. **Response Sequences**: All matching requests return the same response
2. **Request Verification**: Cannot verify what requests were made
3. **HTTP Client**: Only works with Laravel's HTTP client

### Frontend Mocking (`Bridge::mockBrowser()`)

1. **Service Workers**: Cannot intercept requests in Service Workers
2. **WebSockets**: Only HTTP requests (fetch/XHR) are intercepted
3. **Response Sequences**: Same response for all matching requests
4. **Binary Data**: Best suited for JSON responses

---

## Troubleshooting

### Backend mocks not working

1. Verify middleware is registered in `bootstrap/app.php`
2. Check `APP_ENV=testing` is set
3. Ensure URL pattern matches exactly

### Frontend mocks not working

1. Use `waitForEvent('networkidle')` to wait for async calls
2. Check browser console for JavaScript errors
3. Verify URL pattern matches the actual fetch URL
4. Clear mocks between tests with `beforeEach`

### Mocks not being cleared

Both mock types are automatically cleared on test end. If you're seeing stale mocks:

```php
beforeEach(function () {
    Bridge::clearFakes();
    Bridge::clearBrowserMocks();
});
```
