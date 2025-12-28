# React Example

This example demonstrates testing a React application (with Vite) using Pest Plugin Bridge.

## Project Structure

```
my-project/
├── backend/                 # Laravel API
│   ├── app/
│   ├── tests/
│   │   └── Browser/         # Browser tests
│   └── composer.json
└── frontend/                # React + Vite
    ├── src/
    │   ├── components/
    │   ├── pages/
    │   ├── hooks/
    │   └── App.tsx
    ├── package.json
    └── vite.config.ts
```

## React Frontend Setup

### Login Component

```tsx
// src/pages/Login.tsx
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export function Login() {
  const navigate = useNavigate()
  const { login } = useAuth()

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setLoading(true)
    setError('')

    try {
      await login(email, password)
      navigate('/dashboard')
    } catch (err: any) {
      setError(err.response?.data?.message || 'Login failed')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="login-page">
      <h1>Login</h1>

      <form onSubmit={handleSubmit} data-testid="login-form">
        <div className="form-group">
          <label htmlFor="email">Email</label>
          <input
            id="email"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            data-testid="email-input"
            required
          />
        </div>

        <div className="form-group">
          <label htmlFor="password">Password</label>
          <input
            id="password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            data-testid="password-input"
            required
          />
        </div>

        {error && (
          <div className="error" data-testid="error-message">
            {error}
          </div>
        )}

        <button
          type="submit"
          data-testid="login-button"
          disabled={loading}
        >
          {loading ? 'Logging in...' : 'Login'}
        </button>
      </form>
    </div>
  )
}
```

### Dashboard Component

```tsx
// src/pages/Dashboard.tsx
import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import api from '../api'

interface Stats {
  totalUsers: number
  activeUsers: number
  revenue: number
}

export function Dashboard() {
  const navigate = useNavigate()
  const { user, logout } = useAuth()

  const [stats, setStats] = useState<Stats | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function fetchStats() {
      try {
        const response = await api.get<Stats>('/stats')
        setStats(response.data)
      } finally {
        setLoading(false)
      }
    }

    fetchStats()
  }, [])

  async function handleLogout() {
    await logout()
    navigate('/login')
  }

  return (
    <div className="dashboard" data-testid="dashboard-page">
      <header data-testid="dashboard-header">
        <h1>Dashboard</h1>
        <span data-testid="user-name">{user?.name}</span>
        <button data-testid="logout-button" onClick={handleLogout}>
          Logout
        </button>
      </header>

      <main>
        {loading ? (
          <div data-testid="loading-spinner">Loading...</div>
        ) : (
          <div className="stats-grid" data-testid="stats-container">
            <div className="stat-card" data-testid="stat-total-users">
              <span className="label">Total Users</span>
              <span className="value">{stats?.totalUsers}</span>
            </div>
            <div className="stat-card" data-testid="stat-active-users">
              <span className="label">Active Users</span>
              <span className="value">{stats?.activeUsers}</span>
            </div>
            <div className="stat-card" data-testid="stat-revenue">
              <span className="label">Revenue</span>
              <span className="value">${stats?.revenue}</span>
            </div>
          </div>
        )}
      </main>
    </div>
  )
}
```

### Modal Component

```tsx
// src/components/Modal.tsx
import { useEffect, useRef } from 'react'

interface ModalProps {
  isOpen: boolean
  onClose: () => void
  title: string
  children: React.ReactNode
}

export function Modal({ isOpen, onClose, title, children }: ModalProps) {
  const overlayRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    function handleEscape(e: KeyboardEvent) {
      if (e.key === 'Escape') onClose()
    }

    if (isOpen) {
      document.addEventListener('keydown', handleEscape)
      return () => document.removeEventListener('keydown', handleEscape)
    }
  }, [isOpen, onClose])

  if (!isOpen) return null

  return (
    <div
      className="modal-overlay"
      data-testid="modal-overlay"
      ref={overlayRef}
      onClick={(e) => e.target === overlayRef.current && onClose()}
    >
      <div className="modal" data-testid="modal">
        <div className="modal-header">
          <h2 data-testid="modal-title">{title}</h2>
          <button
            data-testid="modal-close-button"
            onClick={onClose}
            aria-label="Close modal"
          >
            ×
          </button>
        </div>
        <div className="modal-body" data-testid="modal-body">
          {children}
        </div>
      </div>
    </div>
  )
}
```

## Backend Test Setup

### Pest Configuration

```php
// tests/Pest.php
use TestFlowLabs\PestPluginBridge\Bridge;

Bridge::setDefault('http://localhost:5173');
```

### Test Files

::: tip Use typeSlowly() for React
React's controlled inputs don't sync with Playwright's `fill()` because it sets DOM values directly without firing onChange events. Use `typeSlowly()` instead.
:::

```php
<?php

// tests/Browser/ReactAppTest.php

declare(strict_types=1);

use App\Models\User;

describe('React Application', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    });

    test('login page renders', function () {
        $this->bridge('/login')
            ->assertVisible('[data-testid="login-form"]')
            ->assertVisible('[data-testid="email-input"]')
            ->assertVisible('[data-testid="password-input"]')
            ->assertVisible('[data-testid="login-button"]');
    });

    test('shows error for invalid credentials', function () {
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
            ->assertVisible('[data-testid="dashboard-page"]');
    });

    test('dashboard shows user name', function () {
        // Login first
        $this->bridge('/login')
            ->waitForEvent('networkidle')
            ->click('[data-testid="email-input"]')
            ->typeSlowly('[data-testid="email-input"]', $this->user->email, 20)
            ->typeSlowly('[data-testid="password-input"]', 'password', 20)
            ->click('[data-testid="login-button"]')
            ->waitForEvent('networkidle');

        $this->bridge('/dashboard')
            ->assertSeeIn('[data-testid="user-name"]', 'Test User');
    });

    test('dashboard shows loading then stats', function () {
        // Login
        $this->bridge('/login')
            ->waitForEvent('networkidle')
            ->click('[data-testid="email-input"]')
            ->typeSlowly('[data-testid="email-input"]', $this->user->email, 20)
            ->typeSlowly('[data-testid="password-input"]', 'password', 20)
            ->click('[data-testid="login-button"]')
            ->waitForEvent('networkidle');

        // Dashboard loads
        $this->bridge('/dashboard')
            // Stats load after API call
            ->assertVisible('[data-testid="stats-container"]')
            ->assertVisible('[data-testid="stat-total-users"]')
            ->assertVisible('[data-testid="stat-active-users"]')
            ->assertVisible('[data-testid="stat-revenue"]');
    });

    test('user can logout', function () {
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

### Modal Testing

```php
<?php

// tests/Browser/ModalTest.php

describe('Modal Component', function () {
    test('modal opens when trigger is clicked', function () {
        $this->bridge('/page-with-modal')
            ->assertNotVisible('[data-testid="modal"]')
            ->click('[data-testid="open-modal-button"]')
            ->wait(0.3)
            ->assertVisible('[data-testid="modal"]')
            ->assertVisible('[data-testid="modal-title"]');
    });

    test('modal closes when X button is clicked', function () {
        $this->bridge('/page-with-modal')
            ->click('[data-testid="open-modal-button"]')
            ->wait(0.3)
            ->assertVisible('[data-testid="modal"]')
            ->click('[data-testid="modal-close-button"]')
            ->wait(0.3)
            ->assertNotVisible('[data-testid="modal"]');
    });

    test('modal closes when overlay is clicked', function () {
        $this->bridge('/page-with-modal')
            ->click('[data-testid="open-modal-button"]')
            ->wait(0.3)
            ->assertVisible('[data-testid="modal"]')
            ->click('[data-testid="modal-overlay"]')
            ->wait(0.3)
            ->assertNotVisible('[data-testid="modal"]');
    });

    test('modal displays correct content', function () {
        $this->bridge('/page-with-modal')
            ->click('[data-testid="open-modal-button"]')
            ->wait(0.3)
            ->assertSeeIn('[data-testid="modal-title"]', 'Confirm Action')
            ->assertSeeIn('[data-testid="modal-body"]', 'Are you sure?');
    });
});
```

### Form Validation Testing

```php
<?php

// tests/Browser/FormValidationTest.php

describe('React Form Validation', function () {
    test('shows validation errors on blur', function () {
        $this->bridge('/register')
            ->fill('[data-testid="email-input"]', 'invalid-email')
            ->click('[data-testid="name-input"]') // Trigger blur
            ->wait(0.3)
            ->assertVisible('[data-testid="email-error"]')
            ->assertSee('Please enter a valid email');
    });

    test('shows validation errors on submit', function () {
        $this->bridge('/register')
            ->click('[data-testid="submit-button"]')
            ->wait(0.3)
            ->assertVisible('[data-testid="name-error"]')
            ->assertVisible('[data-testid="email-error"]')
            ->assertVisible('[data-testid="password-error"]');
    });

    test('clears errors when valid input is provided', function () {
        $this->bridge('/register')
            ->fill('[data-testid="email-input"]', 'invalid')
            ->click('[data-testid="name-input"]')
            ->wait(0.3)
            ->assertVisible('[data-testid="email-error"]')
            // Fix the email
            ->fill('[data-testid="email-input"]', 'valid@example.com')
            ->click('[data-testid="name-input"]')
            ->wait(0.3)
            ->assertNotVisible('[data-testid="email-error"]');
    });

    test('successful form submission', function () {
        $this->bridge('/register')
            ->fill('[data-testid="name-input"]', 'John Doe')
            ->fill('[data-testid="email-input"]', 'john@example.com')
            ->fill('[data-testid="password-input"]', 'securepassword123')
            ->fill('[data-testid="password-confirm-input"]', 'securepassword123')
            ->click('[data-testid="submit-button"]')
            ->wait(2)
            ->assertPathContains('/dashboard')
            ->assertSee('Welcome, John Doe');
    });
});
```

## Testing React Hooks Behavior

```php
<?php

describe('React Hooks Behavior', function () {
    test('counter increments correctly', function () {
        $this->bridge('/counter')
            ->assertSeeIn('[data-testid="count-display"]', '0')
            ->click('[data-testid="increment-button"]')
            ->wait(0.1)
            ->assertSeeIn('[data-testid="count-display"]', '1')
            ->click('[data-testid="increment-button"]')
            ->wait(0.1)
            ->assertSeeIn('[data-testid="count-display"]', '2');
    });

    test('search with debounce', function () {
        $this->bridge('/search')
            ->fill('[data-testid="search-input"]', 'react')
            // Wait for debounce (usually 300-500ms)
            ->wait(1)
            ->assertVisible('[data-testid="search-results"]')
            ->assertSee('React');
    });

    test('infinite scroll loads more items', function () {
        $this->bridge('/infinite-list')
            ->assertVisible('[data-testid="list-item-1"]')
            ->assertVisible('[data-testid="list-item-10"]')
            // Scroll to bottom would trigger load more
            ->wait(1)
            // After scroll, more items should load
            ->assertVisible('[data-testid="list-item-11"]');
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
    ->serve('npm run dev', cwd: '../frontend');

pest()->extends(TestCase::class)->in('Browser');
```

Then simply run:

```bash
cd backend
./vendor/bin/pest tests/Browser/ReactAppTest.php
```

### Manual Approach

If you prefer to start servers manually:

```bash
# Terminal 1: Start React
cd frontend && npm run dev

# Terminal 2: Run tests
cd backend && ./vendor/bin/pest tests/Browser/ReactAppTest.php
```

### Debug Mode

```bash
./vendor/bin/pest tests/Browser --headed
```

## Tips for React Testing

1. **Use `data-testid` consistently** - React's virtual DOM makes other selectors unreliable
2. **Wait for state updates** - React batches updates, add small waits after interactions
3. **Test loading states** - React apps often show loading indicators
4. **Consider useEffect timing** - Effects run after render, wait for them
5. **Test error boundaries** - Ensure errors are handled gracefully
6. **Mock API calls in backend** - Use Laravel factories for predictable test data
