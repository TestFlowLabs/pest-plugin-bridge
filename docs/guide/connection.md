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

```ini [React / Vite]
# .env
VITE_API_URL=http://localhost:8000
```

```ini [Next.js]
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000
```

```ini [Vue CLI]
# .env
VUE_APP_API_URL=http://localhost:8000
```

```ini [Angular]
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

With `->serve()` configuration, frontend servers start automatically on first `bridge()` call:

```php
// tests/Pest.php
use TestFlowLabs\PestPluginBridge\Bridge;
use Tests\TestCase;

Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');

pest()->extends(TestCase::class)->in('Browser');
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
   ```ini
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

### Database Configuration

#### Laravel Trait Compatibility

| Trait | Mechanism | Commits Data? | Browser Tests? |
|-------|-----------|---------------|----------------|
| `RefreshDatabase` | Transaction wrap | ❌ No | ❌ **Does NOT work** |
| `LazilyRefreshDatabase` | Same, lazy init | ❌ No | ❌ **Does NOT work** |
| `DatabaseTransactions` | Transaction only | ❌ No | ❌ **Does NOT work** |
| `DatabaseMigrations` | `migrate:fresh` each test | ✅ Yes | ✅ **Works** (slower) |
| `DatabaseTruncation` | First: migrate, then: TRUNCATE | ✅ Yes | ✅ **Works** (faster) |

::: danger SQLite In-Memory Not Supported
SQLite in-memory databases (`:memory:`) **do not work** with browser tests. Each database connection gets its own isolated in-memory database, so the frontend's API calls cannot see test data.
:::

::: warning Transaction-Based Traits Don't Work
`RefreshDatabase`, `LazilyRefreshDatabase`, and `DatabaseTransactions` use database transactions for isolation. Transaction data is only visible to the same connection. When your frontend makes API calls, those use separate database connections that cannot see uncommitted transaction data.
:::

#### Recommended Setup

**1. Configure database in `phpunit.xml`:**

```xml
<phpunit>
    <php>
        <env name="DB_CONNECTION" value="sqlite"/>
        <!-- File-based database (not :memory:) -->
        <env name="DB_DATABASE" value="database/database.sqlite"/>
    </php>
</phpunit>
```

**2. Use `DatabaseTruncation` (recommended) or `DatabaseMigrations`:**

```php
// tests/Pest.php
use Illuminate\Foundation\Testing\DatabaseTruncation;

// Truncation is faster - migrate:fresh once, then TRUNCATE tables
pest()->extends(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Browser');
```

Or use `DatabaseMigrations` if you need completely fresh schema each test:

```php
use Illuminate\Foundation\Testing\DatabaseMigrations;

// Slower - runs migrate:fresh for each test
pest()->extends(TestCase::class)
    ->use(DatabaseMigrations::class)
    ->in('Browser');
```

#### Using Same Database for Unit and Browser Tests

You CAN use the same database for all tests, but with different traits:

```php
// tests/Pest.php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTruncation;

// Unit/Feature tests: RefreshDatabase (fast, transaction-based)
pest()->extends(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

// Browser tests: DatabaseTruncation (commits data, visible to API)
pest()->extends(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Browser');
```

#### Do I Need a Separate .env File?

::: tip No Separate .env Needed
Unlike Laravel Dusk (which ran a separate PHP process for the server), Pest Plugin Bridge uses pest-plugin-browser which runs Laravel **in-process** via amphp. You don't need a separate `.env.dusk.local` file.
:::

Configure your test database in `phpunit.xml` - this works for both unit and browser tests:

```xml
<phpunit>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value="database/database.sqlite"/>
    </php>
</phpunit>
```

#### Alternative: Unique Test Data

If you prefer not to truncate/migrate, use unique identifiers:

```php
test('user can register', function () {
    $email = 'test-'.Str::uuid().'@example.com';

    $this->bridge('/register')
        ->typeSlowly('[data-testid="email"]', $email, 20)
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

**`phpunit.xml`** (database configuration):
```xml
<phpunit>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value="database/database.sqlite"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

**`.env.testing`** (optional, for Sanctum SPA auth):
```ini
APP_URL=http://localhost:8000
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

### Frontend (example for Vite-based apps)

```ini
# .env or .env.local

VITE_API_URL=http://localhost:8000
```