# Nuxt 3 Example

This example demonstrates testing a Nuxt 3 application with SSR considerations.

## Project Structure

```
my-project/
├── backend/                 # Laravel API
│   ├── app/
│   ├── tests/
│   │   └── Browser/         # Browser tests
│   └── composer.json
└── frontend/                # Nuxt 3
    ├── pages/
    ├── components/
    ├── composables/
    ├── nuxt.config.ts
    └── package.json
```

## Nuxt Frontend Setup

### Login Page

```vue
<!-- pages/login.vue -->
<template>
  <div class="login-page">
    <h1>Login</h1>

    <form @submit.prevent="handleLogin" data-testid="login-form">
      <div class="form-group">
        <label for="email">Email</label>
        <input
          id="email"
          v-model="credentials.email"
          type="email"
          data-testid="email-input"
          required
        />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input
          id="password"
          v-model="credentials.password"
          type="password"
          data-testid="password-input"
          required
        />
      </div>

      <p v-if="error" class="error" data-testid="error-message">
        {{ error }}
      </p>

      <button
        type="submit"
        data-testid="login-button"
        :disabled="pending"
      >
        <span v-if="pending">Loading...</span>
        <span v-else>Login</span>
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'guest'
})

const { login } = useAuth()
const router = useRouter()

const credentials = ref({
  email: '',
  password: ''
})

const pending = ref(false)
const error = ref('')

async function handleLogin() {
  pending.value = true
  error.value = ''

  try {
    await login(credentials.value)
    await router.push('/dashboard')
  } catch (e: any) {
    error.value = e.data?.message || 'Login failed'
  } finally {
    pending.value = false
  }
}
</script>
```

### Dashboard Page

```vue
<!-- pages/dashboard.vue -->
<template>
  <div class="dashboard" data-testid="dashboard-page">
    <header data-testid="dashboard-header">
      <h1>Dashboard</h1>
      <p data-testid="welcome-message">Welcome, {{ user?.name }}</p>
      <button data-testid="logout-button" @click="handleLogout">
        Logout
      </button>
    </header>

    <main>
      <div
        v-if="pendingStats"
        data-testid="loading-indicator"
        class="loading"
      >
        Loading stats...
      </div>

      <div v-else class="stats" data-testid="stats-container">
        <div class="stat" data-testid="stat-total-users">
          <span class="label">Total Users</span>
          <span class="value">{{ stats?.totalUsers }}</span>
        </div>
        <div class="stat" data-testid="stat-active-users">
          <span class="label">Active Users</span>
          <span class="value">{{ stats?.activeUsers }}</span>
        </div>
      </div>

      <section class="recent-activity" data-testid="activity-section">
        <h2>Recent Activity</h2>
        <ul data-testid="activity-list">
          <li
            v-for="activity in activities"
            :key="activity.id"
            :data-testid="`activity-${activity.id}`"
          >
            {{ activity.description }}
          </li>
        </ul>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const { user, logout } = useAuth()
const router = useRouter()

// Fetch stats with SSR
const { data: stats, pending: pendingStats } = await useFetch('/api/stats')

// Fetch activities client-side only
const { data: activities } = await useFetch('/api/activities', {
  lazy: true,
  server: false
})

async function handleLogout() {
  await logout()
  await router.push('/login')
}
</script>
```

### Auth Composable

```typescript
// composables/useAuth.ts
export const useAuth = () => {
  const user = useState<User | null>('user', () => null)
  const config = useRuntimeConfig()

  async function login(credentials: { email: string; password: string }) {
    const response = await $fetch<{ user: User; token: string }>(
      `${config.public.apiBase}/auth/login`,
      {
        method: 'POST',
        body: credentials
      }
    )

    user.value = response.user
    useCookie('token').value = response.token
  }

  async function logout() {
    await $fetch(`${config.public.apiBase}/auth/logout`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${useCookie('token').value}`
      }
    })

    user.value = null
    useCookie('token').value = null
  }

  return { user, login, logout }
}
```

## Backend Test Setup

### Pest Configuration

```php
// tests/Pest.php
use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:3000');
```

### Test File

```php
<?php

// tests/Browser/NuxtAppTest.php

declare(strict_types=1);

use App\Models\User;

describe('Nuxt 3 Application', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    });

    test('login page renders correctly', function () {
        $this->bridge('/login')
            ->assertVisible('[data-testid="login-form"]')
            ->assertVisible('[data-testid="email-input"]')
            ->assertVisible('[data-testid="password-input"]')
            ->assertVisible('[data-testid="login-button"]');
    });

    test('user can login and see dashboard', function () {
        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $this->user->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2)
            ->assertPathContains('/dashboard')
            ->assertVisible('[data-testid="dashboard-page"]')
            ->assertSeeIn('[data-testid="welcome-message"]', 'Welcome, Test User');
    });

    test('dashboard loads stats after authentication', function () {
        // Login first
        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $this->user->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        // Stats should load
        $this->bridge('/dashboard')
            ->assertVisible('[data-testid="stats-container"]')
            ->assertVisible('[data-testid="stat-total-users"]')
            ->assertVisible('[data-testid="stat-active-users"]');
    });

    test('dashboard shows loading state for client-side data', function () {
        // Login
        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $this->user->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        // Activity list loads client-side
        $this->bridge('/dashboard')
            ->assertVisible('[data-testid="activity-section"]')
            ->wait(1) // Wait for client-side fetch
            ->assertVisible('[data-testid="activity-list"]');
    });

    test('user can logout', function () {
        // Login
        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $this->user->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2)
            ->assertPathContains('/dashboard');

        // Logout
        $this->bridge('/dashboard')
            ->click('[data-testid="logout-button"]')
            ->wait(1)
            ->assertPathContains('/login');
    });

    test('unauthenticated user is redirected to login', function () {
        $this->bridge('/dashboard')
            ->wait(1)
            ->assertPathContains('/login');
    });
});
```

## SSR Considerations

### Testing SSR Content

Nuxt 3 renders content on the server. Test that SSR content appears immediately:

```php
test('SSR content appears without waiting', function () {
    // Login first
    $this->bridge('/login')
        ->fill('[data-testid="email-input"]', $this->user->email)
        ->fill('[data-testid="password-input"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2);

    // SSR content should be immediately visible
    $this->bridge('/dashboard')
        ->assertVisible('[data-testid="dashboard-header"]') // SSR
        ->assertVisible('[data-testid="stats-container"]'); // SSR via useFetch
});
```

### Testing Client-Side Hydration

```php
test('client-side features work after hydration', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email-input"]', $this->user->email)
        ->fill('[data-testid="password-input"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2);

    // Button click requires hydration
    $this->bridge('/dashboard')
        ->wait(0.5) // Small wait for hydration
        ->click('[data-testid="logout-button"]')
        ->wait(1)
        ->assertPathContains('/login');
});
```

### Testing Lazy-Loaded Content

```php
test('lazy loaded content appears after client fetch', function () {
    $this->bridge('/login')
        ->fill('[data-testid="email-input"]', $this->user->email)
        ->fill('[data-testid="password-input"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2);

    // Lazy content with server: false
    $this->bridge('/dashboard')
        ->assertVisible('[data-testid="activity-section"]')
        // Activity list is fetched client-side
        ->wait(1)
        ->assertVisible('[data-testid="activity-list"]');
});
```

## Nuxt Middleware Testing

### Protected Routes

```php
describe('Auth Middleware', function () {
    test('protected route redirects to login', function () {
        $this->bridge('/dashboard')
            ->wait(1)
            ->assertPathContains('/login');
    });

    test('protected route accessible when authenticated', function () {
        // Login
        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', 'test@example.com')
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        // Now can access
        $this->bridge('/dashboard')
            ->assertVisible('[data-testid="dashboard-page"]');
    });
});
```

### Guest Middleware

```php
describe('Guest Middleware', function () {
    test('login page accessible when not authenticated', function () {
        $this->bridge('/login')
            ->assertVisible('[data-testid="login-form"]');
    });

    test('login page redirects to dashboard when authenticated', function () {
        // Login
        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', 'test@example.com')
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2)
            ->assertPathContains('/dashboard');

        // Try to access login again
        $this->bridge('/login')
            ->wait(1)
            ->assertPathContains('/dashboard');
    });
});
```

## Running the Tests

### With Automatic Server Management (Recommended)

Configure in `tests/Pest.php`:

```php
uses(TestCase::class, BridgeTrait::class)
    ->beforeAll(fn () => Bridge::setDefault('http://localhost:3000')
        ->serve('npm run dev', cwd: '../frontend')
        ->readyWhen('Local:.*localhost:3000'))
    ->in('Browser');
```

Then simply run:

```bash
cd backend
./vendor/bin/pest tests/Browser/NuxtAppTest.php
```

### Manual Approach

If you prefer to start servers manually:

```bash
# Terminal 1: Start Nuxt
cd frontend && npm run dev

# Terminal 2: Run tests
cd backend && ./vendor/bin/pest tests/Browser/NuxtAppTest.php
```

## Tips for Nuxt Testing

1. **Account for SSR** - SSR content is immediately available, client-side content needs waiting
2. **Wait for hydration** - Interactive elements need hydration to work
3. **Test middleware** - Verify auth and guest middleware work correctly
4. **Use `wait()` appropriately** - More waiting for client-side, less for SSR
5. **Consider lazy loading** - `server: false` content loads after initial render
