# Connection Architecture

Understanding how the plugin connects your Laravel backend with external frontends is key to successful testing.

## The Testing Triangle

When you run browser tests with Pest Plugin Bridge, three components interact:

```
┌─────────────────────────────────────────────────────────────────┐
│                      TEST EXECUTION FLOW                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   ┌──────────────────┐                                          │
│   │  Laravel Backend │  ◄── You run tests here                  │
│   │   (Port 8000)    │                                          │
│   └────────┬─────────┘                                          │
│            │                                                     │
│            │ 1. Pest runs tests, launches Playwright            │
│            ▼                                                     │
│   ┌──────────────────┐                                          │
│   │    Playwright    │  ◄── Automated browser                   │
│   │     Browser      │                                          │
│   └────────┬─────────┘                                          │
│            │                                                     │
│            │ 2. Browser visits your frontend URL                │
│            ▼                                                     │
│   ┌──────────────────┐                                          │
│   │ External Frontend│  ◄── React, Vue, Nuxt, Next, Angular...  │
│   │   (Port 3000)    │                                          │
│   └────────┬─────────┘                                          │
│            │                                                     │
│            │ 3. Frontend makes API requests                     │
│            ▼                                                     │
│   ┌──────────────────┐                                          │
│   │  Laravel Backend │  ◄── Same backend, now serving API       │
│   │   (Port 8000)    │                                          │
│   └──────────────────┘                                          │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Configuration Responsibilities

Each side needs different configuration:

| Component | Configuration | Purpose |
|-----------|---------------|---------|
| **Laravel Backend** | `Bridge::setDefault()` | Tell tests where frontend is |
| **External Frontend** | `API_URL` or similar | Tell frontend where API is |

### Backend Configuration

In your `tests/Pest.php`:

```php
use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:3000');
```

That's it! The backend just needs to know where to find the frontend.

### Frontend Configuration

Your frontend needs to know where the Laravel API is. This varies by framework:

::: code-group

```typescript [Nuxt 3]
// nuxt.config.ts
export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000'
    }
  }
})
```

```env [React / Vite]
# .env
VITE_API_URL=http://localhost:8000
```

```env [Next.js]
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000
```

```env [Vue CLI]
# .env
VUE_APP_API_URL=http://localhost:8000
```

```env [Angular]
# environment.ts
export const environment = {
  apiUrl: 'http://localhost:8000'
};
```

:::

## Authentication Patterns

### Pattern A: Laravel Sanctum (SPA)

Best for: Same-domain or subdomain setups

```
Frontend (localhost:3000)
    │
    ├── 1. GET /sanctum/csrf-cookie
    │       └── Receives XSRF-TOKEN cookie
    │
    ├── 2. POST /login {email, password}
    │       └── Session cookie set
    │
    └── 3. GET /api/user
            └── Returns authenticated user
```

**Laravel Configuration** (`config/sanctum.php`):
```php
'stateful' => [
    'localhost:3000',
    // Add your frontend domains
],
```

**CORS Configuration** (`config/cors.php`):
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_origins' => ['http://localhost:3000'],
    'supports_credentials' => true,
];
```

### Pattern B: API Tokens

Best for: Mobile apps, third-party integrations

```
Frontend (localhost:3000)
    │
    ├── 1. POST /api/login {email, password}
    │       └── Returns {token: "..."}
    │
    └── 2. GET /api/user
            └── Header: Authorization: Bearer {token}
```

**Laravel Configuration**:
```php
// routes/api.php
Route::post('/login', function (Request $request) {
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'token' => $user->createToken('api')->plainTextToken,
        'user' => $user,
    ]);
});
```

### Pattern C: External Auth Provider

Best for: OAuth, SSO, enterprise apps

```
Frontend (localhost:3000)
    │
    ├── 1. Redirect to Auth0/Firebase/Okta
    │       └── User authenticates
    │
    ├── 2. Callback with token/code
    │       └── Exchange for JWT
    │
    └── 3. GET /api/user
            └── Header: Authorization: Bearer {jwt}
```

## Running Tests

### Automatic Server Management (Recommended)

With `->serve()` configuration, both servers start automatically:

```php
// tests/Pest.php
uses(TestCase::class, BridgeTrait::class)
    ->beforeAll(fn () => Bridge::setDefault('http://localhost:3000')
        ->serve('npm run dev', cwd: '../frontend')
        ->readyWhen('ready|localhost'))
    ->in('Browser');
```

Then simply run:

```bash
./vendor/bin/pest tests/Browser
```

The Laravel API is started automatically by pest-plugin-browser (in-process via amphp), and the frontend is started by pest-plugin-bridge via `->serve()`. Both are cleaned up automatically when tests complete.

### Manual Approach

If you prefer to start servers manually:

**Terminal 1 - Frontend Dev Server:**
```bash
npm run dev
```

**Terminal 2 - Tests:**
```bash
./vendor/bin/pest tests/Browser
```

Note: The Laravel API is still started automatically by pest-plugin-browser.

## Troubleshooting

### CORS Errors

**Symptom**: Frontend console shows "Access-Control-Allow-Origin" errors

**Solution**: Update `config/cors.php`:
```php
'allowed_origins' => ['http://localhost:3000'],
'supports_credentials' => true,  // Important for cookies!
```

### Cookies Not Sent

**Symptom**: Authentication works in browser but not in tests

**Solution**:
1. Check `SESSION_DOMAIN` in `.env`:
   ```env
   SESSION_DOMAIN=localhost
   ```
2. Ensure Sanctum stateful domains include your frontend
3. Frontend must send credentials with requests

### Port Conflicts

**Symptom**: "Port already in use" errors

**Solution**:
```bash
# Kill process on port 8000
lsof -ti:8000 | xargs kill -9

# Kill process on port 3000
lsof -ti:3000 | xargs kill -9
```

### Test Database Isolation

**Symptom**: Tests interfere with each other

**Solution**: Use Laravel's `RefreshDatabase` or `DatabaseTransactions`:
```php
uses(RefreshDatabase::class);

test('user can login', function () {
    $user = User::factory()->create();
    // ...
});
```

## Configuration Summary

### Laravel Backend

**`tests/Pest.php`**:
```php
use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:3000');
```

**`.env.testing`** (for Laravel itself):
```env
APP_URL=http://localhost:8000

# Sanctum (if using SPA auth)
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

### Frontend (example for Vite-based apps)

```env
# .env or .env.local

VITE_API_URL=http://localhost:8000
```

## Next Steps

- Learn about [Writing Tests](/guide/writing-tests)
- See [Best Practices](/guide/best-practices)
- Check out [Examples](/examples/vue-vite)
