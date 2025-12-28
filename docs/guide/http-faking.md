# HTTP Faking

When testing frontend applications that interact with external APIs (Stripe, SendGrid, Twilio, etc.), you often need to mock these external HTTP calls. The Bridge plugin provides `Bridge::fake()` to handle this across process boundaries.

## The Challenge

In browser tests, your Laravel application runs in a separate PHP process from your test. This means Laravel's built-in `Http::fake()` won't work — the fake you register in your test isn't visible to the server process.

<HttpFakingChallengeDiagram />

## Bridge::fake() Solution

`Bridge::fake()` writes fake configuration to a temp file that a Laravel middleware reads. This bridges the gap between processes:

<HttpFakingSolutionDiagram />

## Setup

### 1. Register the Middleware

Add the middleware to your Laravel application. This is **only** needed in the testing environment.

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

### 2. Use Bridge::fake() in Tests

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
        ->fill('[data-testid="card-expiry"]', '12/25')
        ->fill('[data-testid="card-cvc"]', '123')
        ->click('[data-testid="pay-button"]')
        ->assertSee('Payment Successful');
});
```

## Configuration Options

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
    'https://api.sendgrid.com/v3/mail/send' => [
        'status' => 202,
        'body' => ['message' => 'queued'],
    ],
]);
```

## URL Pattern Matching

URL patterns support wildcards for flexible matching:

```php
Bridge::fake([
    // Match all Stripe API calls
    'https://api.stripe.com/*' => ['status' => 200, 'body' => []],

    // Match specific endpoint
    'https://api.stripe.com/v1/charges' => ['status' => 201],

    // Match with path wildcards
    'https://api.example.com/users/*/orders' => ['status' => 200],
]);
```

## Cleanup

Fakes are automatically cleaned up after each test via the shutdown handler. You don't need to manually clear them.

If you need to clear fakes during a test:

```php
Bridge::clearFakes();
```

## Checking Fake State

```php
// Check if any fakes are registered
if (Bridge::hasFakes()) {
    // ...
}

// Get current fake configuration
$fakes = Bridge::getFakes();
```

## Common Use Cases

### Payment Gateway Testing

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

### Email Service Testing

```php
test('sends welcome email on registration', function () {
    Bridge::fake([
        'https://api.sendgrid.com/*' => [
            'status' => 202,
            'body' => ['message' => 'queued'],
        ],
    ]);

    $this->bridge('/register')
        ->fill('[data-testid="email"]', 'new@example.com')
        ->fill('[data-testid="password"]', 'password123')
        ->click('[data-testid="register-button"]')
        ->assertSee('Check your email');
});
```

### Multiple Services

```php
test('order process with payment and notification', function () {
    Bridge::fake([
        'https://api.stripe.com/*' => [
            'status' => 200,
            'body' => ['status' => 'succeeded'],
        ],
        'https://api.sendgrid.com/*' => [
            'status' => 202,
        ],
        'https://api.twilio.com/*' => [
            'status' => 201,
            'body' => ['sid' => 'SM123'],
        ],
    ]);

    $this->bridge('/checkout')
        ->click('[data-testid="place-order"]')
        ->assertSee('Order Confirmed');
});
```

## Limitations

1. **Response Sequences**: Currently, all matching requests return the same response. Dynamic response sequences are not yet supported.

2. **Request Verification**: You cannot verify what requests were made to external APIs. The fakes only intercept and return configured responses.

3. **Non-HTTP Mocking**: This only works for HTTP calls made via Laravel's HTTP client. Direct socket connections or other HTTP libraries need different mocking approaches.

## Alternative Approaches

For more complex scenarios, consider:

- **Service Container Binding**: In your Laravel test setup, bind fake implementations of payment/email services
- **Feature Flags**: Use environment variables to switch to sandbox APIs
- **Dedicated Test APIs**: Some services (Stripe, etc.) provide test mode endpoints
