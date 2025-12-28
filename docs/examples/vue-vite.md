# Vue + Vite Example

This example demonstrates testing a Vue 3 application built with Vite.

## Project Structure

```
my-project/
+-- backend/                 # Laravel API
|   +-- app/
|   +-- tests/
|   |   +-- Browser/         # Browser tests here
|   +-- composer.json
+-- frontend/                # Vue + Vite
    +-- src/
    |   +-- components/
    |   +-- views/
    |   +-- App.vue
    +-- package.json
    +-- vite.config.js
```

## Frontend Setup

### Vue Component with Test IDs

```vue
<!-- src/views/LoginView.vue -->
<template>
  <div class="login-container">
    <h1>Login</h1>

    <form @submit.prevent="handleLogin">
      <div class="form-group">
        <label for="email">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          data-testid="email-input"
          required
        />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          data-testid="password-input"
          required
        />
      </div>

      <div v-if="error" class="error" data-testid="error-message">
        {{ error }}
      </div>

      <button type="submit" data-testid="login-button" :disabled="loading">
        {{ loading ? 'Logging in...' : 'Login' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: ''
})

const loading = ref(false)
const error = ref('')

async function handleLogin() {
  loading.value = true
  error.value = ''

  try {
    await authStore.login(form.email, form.password)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>
```

### Dashboard Component

```vue
<!-- src/views/DashboardView.vue -->
<template>
  <div class="dashboard">
    <header data-testid="dashboard-header">
      <h1>Dashboard</h1>
      <div data-testid="user-greeting">
        Welcome, {{ user?.name }}
      </div>
      <button data-testid="logout-button" @click="handleLogout">
        Logout
      </button>
    </header>

    <main data-testid="dashboard-content">
      <div class="stats" data-testid="stats-panel">
        <div class="stat-card" data-testid="stat-users">
          <span class="label">Users</span>
          <span class="value">{{ stats.users }}</span>
        </div>
        <div class="stat-card" data-testid="stat-orders">
          <span class="label">Orders</span>
          <span class="value">{{ stats.orders }}</span>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const router = useRouter()
const authStore = useAuthStore()

const user = ref(null)
const stats = ref({ users: 0, orders: 0 })

onMounted(async () => {
  user.value = authStore.user
  const response = await api.get('/stats')
  stats.value = response.data
})

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>
```

## Backend Test Setup

### Pest.php Configuration

```php
<?php

use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:5173');
```

### Test File

::: tip Use typeSlowly() for Vue
Vue's `v-model` doesn't sync with Playwright's `fill()` because it sets DOM values directly without firing input events. Use `typeSlowly()` instead.
:::

```php
<?php

// tests/Browser/VueAppTest.php

declare(strict_types=1);

use App\Models\User;

describe('Vue Application', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    });

    test('login page displays correctly', function () {
        $this->bridge('/login')
            ->assertSee('Login')
            ->assertVisible('[data-testid="email-input"]')
            ->assertVisible('[data-testid="password-input"]')
            ->assertVisible('[data-testid="login-button"]');
    });

    test('shows validation error for invalid credentials', function () {
        $this->bridge('/login')
            ->waitForEvent('networkidle')
            ->click('[data-testid="email-input"]')
            ->typeSlowly('[data-testid="email-input"]', 'wrong@example.com', 20)
            ->typeSlowly('[data-testid="password-input"]', 'wrongpassword', 20)
            ->click('[data-testid="login-button"]')
            ->waitForEvent('networkidle')
            ->assertVisible('[data-testid="error-message"]');
    });

    test('successful login redirects to dashboard', function () {
        $this->bridge('/login')
            ->waitForEvent('networkidle')
            ->click('[data-testid="email-input"]')
            ->typeSlowly('[data-testid="email-input"]', $this->user->email, 20)
            ->typeSlowly('[data-testid="password-input"]', 'password', 20)
            ->click('[data-testid="login-button"]')
            ->waitForEvent('networkidle')
            ->assertPathContains('/dashboard')
            ->assertVisible('[data-testid="dashboard-header"]')
            ->assertSeeIn('[data-testid="user-greeting"]', 'Welcome, Test User');
    });

    test('dashboard shows stats', function () {
        // Login first
        $this->bridge('/login')
            ->waitForEvent('networkidle')
            ->click('[data-testid="email-input"]')
            ->typeSlowly('[data-testid="email-input"]', $this->user->email, 20)
            ->typeSlowly('[data-testid="password-input"]', 'password', 20)
            ->click('[data-testid="login-button"]')
            ->waitForEvent('networkidle');

        // Check dashboard content
        $this->bridge('/dashboard')
            ->assertVisible('[data-testid="stats-panel"]')
            ->assertVisible('[data-testid="stat-users"]')
            ->assertVisible('[data-testid="stat-orders"]');
    });

    test('logout returns to login page', function () {
        // Login
        $this->bridge('/login')
            ->waitForEvent('networkidle')
            ->click('[data-testid="email-input"]')
            ->typeSlowly('[data-testid="email-input"]', $this->user->email, 20)
            ->typeSlowly('[data-testid="password-input"]', 'password', 20)
            ->click('[data-testid="login-button"]')
            ->waitForEvent('networkidle')
            ->assertPathContains('/dashboard');

        // Logout
        $this->bridge('/dashboard')
            ->click('[data-testid="logout-button"]')
            ->wait(1)
            ->assertPathContains('/login');
    });
});
```

## Form Validation Example

### Vue Form Component

```vue
<!-- src/views/RegisterView.vue -->
<template>
  <form @submit.prevent="handleSubmit" data-testid="register-form">
    <div class="form-group">
      <input
        v-model="form.name"
        data-testid="name-input"
        placeholder="Name"
        @blur="validateName"
      />
      <span v-if="errors.name" data-testid="name-error" class="error">
        {{ errors.name }}
      </span>
    </div>

    <div class="form-group">
      <input
        v-model="form.email"
        type="email"
        data-testid="email-input"
        placeholder="Email"
        @blur="validateEmail"
      />
      <span v-if="errors.email" data-testid="email-error" class="error">
        {{ errors.email }}
      </span>
    </div>

    <div class="form-group">
      <input
        v-model="form.password"
        type="password"
        data-testid="password-input"
        placeholder="Password"
        @blur="validatePassword"
      />
      <span v-if="errors.password" data-testid="password-error" class="error">
        {{ errors.password }}
      </span>
    </div>

    <button type="submit" data-testid="submit-button">
      Register
    </button>
  </form>
</template>
```

### Form Validation Tests

```php
<?php

describe('Registration Form Validation', function () {
    test('shows name error when empty', function () {
        $this->bridge('/register')
            ->waitForEvent('networkidle')
            ->click('[data-testid="name-input"]')
            ->typeSlowly('[data-testid="name-input"]', '', 20)
            ->click('[data-testid="email-input"]') // Triggers blur
            ->wait(0.3)
            ->assertVisible('[data-testid="name-error"]')
            ->assertSee('Name is required');
    });

    test('shows email error for invalid format', function () {
        $this->bridge('/register')
            ->waitForEvent('networkidle')
            ->click('[data-testid="email-input"]')
            ->typeSlowly('[data-testid="email-input"]', 'invalid-email', 20)
            ->click('[data-testid="password-input"]') // Triggers blur
            ->wait(0.3)
            ->assertVisible('[data-testid="email-error"]')
            ->assertSee('Please enter a valid email');
    });

    test('shows password error when too short', function () {
        $this->bridge('/register')
            ->waitForEvent('networkidle')
            ->click('[data-testid="password-input"]')
            ->typeSlowly('[data-testid="password-input"]', '123', 20)
            ->click('[data-testid="submit-button"]')
            ->wait(0.3)
            ->assertVisible('[data-testid="password-error"]')
            ->assertSee('Password must be at least 8 characters');
    });

    test('successful registration', function () {
        $this->bridge('/register')
            ->waitForEvent('networkidle')
            ->click('[data-testid="name-input"]')
            ->typeSlowly('[data-testid="name-input"]', 'New User', 20)
            ->typeSlowly('[data-testid="email-input"]', 'new@example.com', 20)
            ->typeSlowly('[data-testid="password-input"]', 'securepassword123', 20)
            ->click('[data-testid="submit-button"]')
            ->waitForEvent('networkidle')
            ->assertPathContains('/dashboard')
            ->assertSee('Welcome, New User');
    });
});
```

## Running the Tests

### With Automatic Server Management (Recommended)

Configure in `tests/Pest.php`:

```php
use TestFlowLabs\PestPluginBridge\Bridge;
use Tests\TestCase;

Bridge::setDefault('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('VITE.*ready');

pest()->extends(TestCase::class)->in('Browser');
```

::: tip Cold-Start Handling
Vite compiles JavaScript modules on-demand when the browser first requests them. For large applications, this can take 3-5+ seconds. The `bridge()` method uses a 30-second timeout by default to handle this automatically.

For very large apps, add extra warmup time:
```php
Bridge::setDefault('http://localhost:5173')
    ->serve('npm run dev', cwd: '../frontend')
    ->readyWhen('VITE.*ready')
    ->warmup(3000);  // Additional 3s warmup
```
:::

Then simply run:

```bash
cd backend
./vendor/bin/pest tests/Browser/VueAppTest.php
```

### Manual Approach

If you prefer to start servers manually:

```bash
# Terminal 1: Start Vue
cd frontend && npm run dev

# Terminal 2: Run tests
cd backend && ./vendor/bin/pest tests/Browser/VueAppTest.php
```

### Debug Mode

```bash
./vendor/bin/pest tests/Browser/VueAppTest.php --headed
```

## Tips for Vue Testing

1. **Always use `data-testid`** - Vue's reactivity may change classes or IDs
2. **Wait after form submissions** - API calls take time
3. **Use `wait(0.3)` between fields** - For blur validation
4. **Test loading states** - Use `assertVisible` on loading indicators
5. **Reset state between tests** - Clear localStorage if your Vue app uses it
