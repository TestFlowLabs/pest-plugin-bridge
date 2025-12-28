# Playground Setup

The playground directory contains complete sample applications for testing the Pest Plugin Bridge with real-world scenarios.

## Structure

```
playground/
├── laravel-api/        # Laravel 12 backend with Sanctum SPA authentication
├── nuxt-app/           # Nuxt 3 frontend with authentication UI
└── README.md
```

## How It Works

Pest Plugin Bridge provides **automatic server lifecycle management**:

1. **Laravel API** - Automatically started by pest-plugin-browser (in-process via amphp)
2. **Nuxt Frontend** - Automatically started by pest-plugin-bridge via `->serve()`
3. **API URL Injection** - The Laravel API URL is automatically injected via environment variables

**No manual server startup required!**

## Prerequisites

Before setting up the playground, ensure you have:

- PHP 8.3+
- Composer
- Node.js 18+
- npm or pnpm
- SQLite (default) or MySQL/PostgreSQL

## Laravel API Setup

The Laravel API provides:
- Sanctum SPA authentication
- User login/logout endpoints
- Dashboard API endpoints

### Installation

```bash
cd playground/laravel-api

# Install dependencies
composer install

# Run migrations and seed database
php artisan migrate --seed
```

### Database Configuration

The playground uses SQLite by default:

```ini
# .env
DB_CONNECTION=sqlite
```

This creates a `database/database.sqlite` file automatically.

For MySQL/PostgreSQL, update the `.env` file:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=playground
DB_USERNAME=root
DB_PASSWORD=
```

### Test Credentials

The database seeder creates a test user:

| Field | Value |
|-------|-------|
| Email | `test@example.com` |
| Password | `password` |

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | Authenticate user |
| POST | `/api/logout` | End session |
| GET | `/api/user` | Get authenticated user |

## Nuxt App Setup

The Nuxt 3 frontend provides:
- Login form
- Dashboard with user information
- Full SPA experience with Sanctum authentication

### Installation

```bash
cd playground/nuxt-app

# Install dependencies
npm install
```

**Note:** You don't need to manually start the Nuxt server. The plugin starts it automatically when running tests.

### Pages

| Route | Description |
|-------|-------------|
| `/` | Home page with login link |
| `/login` | Login form |
| `/dashboard` | Protected dashboard (requires auth) |

### Frontend Components

The Nuxt app includes `data-testid` attributes for reliable testing:

```vue
<!-- Example from login.vue -->
<input data-testid="email-input" type="email" v-model="email" />
<input data-testid="password-input" type="password" v-model="password" />
<button data-testid="login-button">Login</button>
```

## Test Configuration

The playground tests are configured in `playground/laravel-api/tests/Pest.php`:

```php
uses(TestCase::class, BridgeTrait::class)
    ->beforeAll(fn () => Bridge::setDefault('http://localhost:3000')
        ->serve('npm run dev', cwd: dirname(__DIR__, 2).'/nuxt-app')
        ->readyWhen('Local:.*localhost:3000'))
    ->in('Browser');
```

This configuration:
1. Uses Laravel's TestCase to bootstrap the application
2. Sets the Nuxt frontend URL
3. Automatically starts the Nuxt dev server
4. Waits for the server to be ready

Cleanup is automatic via the plugin's shutdown handler - no `afterAll` needed.

## Environment Configuration

### Laravel API Environment

```ini
# playground/laravel-api/.env
APP_URL=http://localhost:8000
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

### Nuxt App Environment

The API URL is automatically injected by pest-plugin-bridge via `NUXT_PUBLIC_API_BASE`.

## Troubleshooting

### Port Already in Use

If a port is already in use:

```bash
# Find and kill process on port 3000
lsof -ti:3000 | xargs kill -9
```

### Database Issues

Reset the database:

```bash
cd playground/laravel-api
php artisan migrate:fresh --seed
```

### Composer Dependencies

Clear composer cache:

```bash
cd playground/laravel-api
composer clear-cache
composer install
```

### Node Modules

Clear node modules:

```bash
cd playground/nuxt-app
rm -rf node_modules
npm install
```

### CORS Errors

If you see CORS errors in the browser console:

1. Ensure the Laravel server is running
2. Check that `SANCTUM_STATEFUL_DOMAINS` includes `localhost:3000`
3. Verify cookies are being sent with requests