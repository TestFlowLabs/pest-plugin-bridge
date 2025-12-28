# Authentication Flow Example

This example demonstrates comprehensive testing of authentication flows across frontend frameworks.

## Complete Auth Flow

A typical authentication flow includes:
1. Login
2. Access protected routes
3. Session persistence
4. Logout
5. Password reset

## Test Setup

### Laravel Backend Models

```php
<?php

// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
```

### User Factory

```php
<?php

// database/factories/UserFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
```

## Complete Authentication Tests

```php
<?php

// tests/Browser/AuthenticationTest.php

declare(strict_types=1);

use App\Models\User;

describe('Authentication', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);
    });

    describe('Login', function () {
        test('displays login form', function () {
            $this->bridge('/login')
                ->assertVisible('[data-testid="login-form"]')
                ->assertVisible('[data-testid="email-input"]')
                ->assertVisible('[data-testid="password-input"]')
                ->assertVisible('[data-testid="login-button"]')
                ->assertVisible('[data-testid="forgot-password-link"]');
        });

        test('shows error for empty credentials', function () {
            $this->bridge('/login')
                ->click('[data-testid="login-button"]')
                ->wait(0.5)
                ->assertVisible('[data-testid="email-error"]')
                ->assertVisible('[data-testid="password-error"]');
        });

        test('shows error for invalid email format', function () {
            $this->bridge('/login')
                ->fill('[data-testid="email-input"]', 'not-an-email')
                ->fill('[data-testid="password-input"]', 'password123')
                ->click('[data-testid="login-button"]')
                ->wait(0.5)
                ->assertVisible('[data-testid="email-error"]')
                ->assertSee('Please enter a valid email');
        });

        test('shows error for incorrect credentials', function () {
            $this->bridge('/login')
                ->fill('[data-testid="email-input"]', 'wrong@example.com')
                ->fill('[data-testid="password-input"]', 'wrongpassword')
                ->click('[data-testid="login-button"]')
                ->wait(2)
                ->assertVisible('[data-testid="error-message"]')
                ->assertSee('Invalid credentials');
        });

        test('successful login redirects to dashboard', function () {
            $this->bridge('/login')
                ->fill('[data-testid="email-input"]', $this->user->email)
                ->fill('[data-testid="password-input"]', 'password123')
                ->click('[data-testid="login-button"]')
                ->wait(2)
                ->assertPathContains('/dashboard')
                ->assertVisible('[data-testid="dashboard-header"]')
                ->assertSee('Welcome, John Doe');
        });

        test('remember me keeps user logged in', function () {
            $this->bridge('/login')
                ->fill('[data-testid="email-input"]', $this->user->email)
                ->fill('[data-testid="password-input"]', 'password123')
                ->check('[data-testid="remember-me-checkbox"]')
                ->click('[data-testid="login-button"]')
                ->wait(2)
                ->assertPathContains('/dashboard');

            // Visiting a protected route should still work
            $this->bridge('/profile')
                ->assertVisible('[data-testid="profile-page"]');
        });
    });

    describe('Protected Routes', function () {
        test('unauthenticated user is redirected to login', function () {
            $this->bridge('/dashboard')
                ->wait(1)
                ->assertPathContains('/login');
        });

        test('unauthenticated user cannot access profile', function () {
            $this->bridge('/profile')
                ->wait(1)
                ->assertPathContains('/login');
        });

        test('unauthenticated user cannot access settings', function () {
            $this->bridge('/settings')
                ->wait(1)
                ->assertPathContains('/login');
        });

        test('authenticated user can access protected routes', function () {
            // Login
            $this->bridge('/login')
                ->fill('[data-testid="email-input"]', $this->user->email)
                ->fill('[data-testid="password-input"]', 'password123')
                ->click('[data-testid="login-button"]')
                ->wait(2);

            // Can access dashboard
            $this->bridge('/dashboard')
                ->assertVisible('[data-testid="dashboard-page"]');

            // Can access profile
            $this->bridge('/profile')
                ->assertVisible('[data-testid="profile-page"]');

            // Can access settings
            $this->bridge('/settings')
                ->assertVisible('[data-testid="settings-page"]');
        });
    });

    describe('Logout', function () {
        beforeEach(function () {
            // Login before each logout test
            $this->bridge('/login')
                ->fill('[data-testid="email-input"]', $this->user->email)
                ->fill('[data-testid="password-input"]', 'password123')
                ->click('[data-testid="login-button"]')
                ->wait(2);
        });

        test('user can logout from dashboard', function () {
            $this->bridge('/dashboard')
                ->click('[data-testid="logout-button"]')
                ->wait(1)
                ->assertPathContains('/login');
        });

        test('user can logout from user menu', function () {
            $this->bridge('/dashboard')
                ->click('[data-testid="user-menu-trigger"]')
                ->wait(0.3)
                ->click('[data-testid="logout-menu-item"]')
                ->wait(1)
                ->assertPathContains('/login');
        });

        test('logout clears session', function () {
            $this->bridge('/dashboard')
                ->click('[data-testid="logout-button"]')
                ->wait(1)
                ->assertPathContains('/login');

            // Trying to access protected route should redirect
            $this->bridge('/dashboard')
                ->wait(1)
                ->assertPathContains('/login');
        });
    });

    describe('Registration', function () {
        test('displays registration form', function () {
            $this->bridge('/register')
                ->assertVisible('[data-testid="register-form"]')
                ->assertVisible('[data-testid="name-input"]')
                ->assertVisible('[data-testid="email-input"]')
                ->assertVisible('[data-testid="password-input"]')
                ->assertVisible('[data-testid="password-confirm-input"]')
                ->assertVisible('[data-testid="register-button"]');
        });

        test('validates password confirmation', function () {
            $this->bridge('/register')
                ->fill('[data-testid="name-input"]', 'New User')
                ->fill('[data-testid="email-input"]', 'new@example.com')
                ->fill('[data-testid="password-input"]', 'password123')
                ->fill('[data-testid="password-confirm-input"]', 'different')
                ->click('[data-testid="register-button"]')
                ->wait(0.5)
                ->assertVisible('[data-testid="password-confirm-error"]')
                ->assertSee('Passwords do not match');
        });

        test('validates email uniqueness', function () {
            $this->bridge('/register')
                ->fill('[data-testid="name-input"]', 'Another User')
                ->fill('[data-testid="email-input"]', $this->user->email) // Existing email
                ->fill('[data-testid="password-input"]', 'password123')
                ->fill('[data-testid="password-confirm-input"]', 'password123')
                ->click('[data-testid="register-button"]')
                ->wait(2)
                ->assertVisible('[data-testid="error-message"]')
                ->assertSee('email has already been taken');
        });

        test('successful registration logs user in', function () {
            $this->bridge('/register')
                ->fill('[data-testid="name-input"]', 'New User')
                ->fill('[data-testid="email-input"]', 'newuser@example.com')
                ->fill('[data-testid="password-input"]', 'password123')
                ->fill('[data-testid="password-confirm-input"]', 'password123')
                ->click('[data-testid="register-button"]')
                ->wait(2)
                ->assertPathContains('/dashboard')
                ->assertSee('Welcome, New User');
        });
    });

    describe('Password Reset', function () {
        test('displays forgot password form', function () {
            $this->bridge('/forgot-password')
                ->assertVisible('[data-testid="forgot-password-form"]')
                ->assertVisible('[data-testid="email-input"]')
                ->assertVisible('[data-testid="submit-button"]');
        });

        test('can navigate to forgot password from login', function () {
            $this->bridge('/login')
                ->click('[data-testid="forgot-password-link"]')
                ->wait(1)
                ->assertPathContains('/forgot-password')
                ->assertVisible('[data-testid="forgot-password-form"]');
        });

        test('shows success message after submitting email', function () {
            $this->bridge('/forgot-password')
                ->fill('[data-testid="email-input"]', $this->user->email)
                ->click('[data-testid="submit-button"]')
                ->wait(2)
                ->assertVisible('[data-testid="success-message"]')
                ->assertSee('Password reset link sent');
        });

        test('shows error for non-existent email', function () {
            $this->bridge('/forgot-password')
                ->fill('[data-testid="email-input"]', 'nonexistent@example.com')
                ->click('[data-testid="submit-button"]')
                ->wait(2)
                ->assertVisible('[data-testid="error-message"]');
        });
    });

    describe('Profile Management', function () {
        beforeEach(function () {
            // Login first
            $this->bridge('/login')
                ->fill('[data-testid="email-input"]', $this->user->email)
                ->fill('[data-testid="password-input"]', 'password123')
                ->click('[data-testid="login-button"]')
                ->wait(2);
        });

        test('can view profile', function () {
            $this->bridge('/profile')
                ->assertVisible('[data-testid="profile-page"]')
                ->assertValue('[data-testid="name-input"]', 'John Doe')
                ->assertValue('[data-testid="email-input"]', $this->user->email);
        });

        test('can update profile name', function () {
            $this->bridge('/profile')
                ->fill('[data-testid="name-input"]', 'Jane Doe')
                ->click('[data-testid="save-profile-button"]')
                ->wait(1)
                ->assertVisible('[data-testid="success-message"]')
                ->assertSee('Profile updated');
        });

        test('can change password', function () {
            $this->bridge('/profile')
                ->click('[data-testid="change-password-tab"]')
                ->wait(0.3)
                ->fill('[data-testid="current-password-input"]', 'password123')
                ->fill('[data-testid="new-password-input"]', 'newpassword456')
                ->fill('[data-testid="confirm-password-input"]', 'newpassword456')
                ->click('[data-testid="change-password-button"]')
                ->wait(1)
                ->assertVisible('[data-testid="success-message"]')
                ->assertSee('Password changed');
        });

        test('validates current password when changing', function () {
            $this->bridge('/profile')
                ->click('[data-testid="change-password-tab"]')
                ->wait(0.3)
                ->fill('[data-testid="current-password-input"]', 'wrongpassword')
                ->fill('[data-testid="new-password-input"]', 'newpassword456')
                ->fill('[data-testid="confirm-password-input"]', 'newpassword456')
                ->click('[data-testid="change-password-button"]')
                ->wait(1)
                ->assertVisible('[data-testid="error-message"]')
                ->assertSee('Current password is incorrect');
        });
    });
});
```

## Role-Based Access Control

```php
<?php

// tests/Browser/RoleBasedAccessTest.php

describe('Role-Based Access Control', function () {
    test('regular user cannot access admin panel', function () {
        $user = User::factory()->create();

        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $user->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        $this->bridge('/admin')
            ->wait(1)
            ->assertPathContains('/dashboard') // Redirected
            ->assertDontSee('Admin Panel');
    });

    test('admin user can access admin panel', function () {
        $admin = User::factory()->admin()->create();

        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $admin->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        $this->bridge('/admin')
            ->assertPathContains('/admin')
            ->assertVisible('[data-testid="admin-panel"]')
            ->assertSee('Admin Panel');
    });

    test('admin sees admin menu items', function () {
        $admin = User::factory()->admin()->create();

        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $admin->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        $this->bridge('/dashboard')
            ->assertVisible('[data-testid="admin-menu-item"]')
            ->assertVisible('[data-testid="users-menu-item"]')
            ->assertVisible('[data-testid="settings-menu-item"]');
    });

    test('regular user does not see admin menu items', function () {
        $user = User::factory()->create();

        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $user->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        $this->bridge('/dashboard')
            ->assertNotVisible('[data-testid="admin-menu-item"]')
            ->assertNotVisible('[data-testid="users-menu-item"]');
    });
});
```

## Two-Factor Authentication

```php
<?php

// tests/Browser/TwoFactorAuthTest.php

describe('Two-Factor Authentication', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('test-secret'),
        ]);
    });

    test('shows 2FA form after password verification', function () {
        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $this->user->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2)
            ->assertPathContains('/two-factor')
            ->assertVisible('[data-testid="two-factor-form"]')
            ->assertVisible('[data-testid="code-input"]');
    });

    test('invalid 2FA code shows error', function () {
        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $this->user->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        $this->bridge('/two-factor')
            ->fill('[data-testid="code-input"]', '000000')
            ->click('[data-testid="verify-button"]')
            ->wait(1)
            ->assertVisible('[data-testid="error-message"]')
            ->assertSee('Invalid code');
    });

    test('can use recovery code', function () {
        $this->bridge('/login')
            ->fill('[data-testid="email-input"]', $this->user->email)
            ->fill('[data-testid="password-input"]', 'password')
            ->click('[data-testid="login-button"]')
            ->wait(2);

        $this->bridge('/two-factor')
            ->click('[data-testid="use-recovery-code-link"]')
            ->wait(0.3)
            ->assertVisible('[data-testid="recovery-code-input"]');
    });
});
```

## Best Practices

1. **Use factories** - Create users with specific states using factories
2. **Test all paths** - Login, logout, registration, password reset, profile updates
3. **Test error states** - Invalid credentials, validation errors, rate limiting
4. **Test authorization** - Role-based access, protected routes
5. **Test session** - Session persistence, session expiry
6. **Use helper methods** - Extract common login logic into helper functions

### Login Helper Example

```php
// tests/Pest.php

function loginAs(User $user): void
{
    test()->bridge('/login')
        ->fill('[data-testid="email-input"]', $user->email)
        ->fill('[data-testid="password-input"]', 'password')
        ->click('[data-testid="login-button"]')
        ->wait(2);
}

// Usage in tests
test('authenticated user can view profile', function () {
    $user = User::factory()->create();

    loginAs($user);

    $this->bridge('/profile')
        ->assertVisible('[data-testid="profile-page"]');
});
```
