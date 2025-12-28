<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Browser Integration Tests
|--------------------------------------------------------------------------
|
| These tests verify the plugin works correctly with a real external frontend
| (Nuxt) and Laravel API backend. The BridgeTrait provides the bridge() method.
|
| Configuration is in tests/Pest.php:
| - TestCase::class bootstraps Laravel (required for pest-plugin-browser)
| - BridgeTrait::class provides the bridge() method
| - Bridge::setDefault() configures the frontend URL
| - ->serve() automatically starts the Nuxt dev server
|
| To run: ./vendor/bin/pest tests/Browser
|
*/

test('home page shows login link when not authenticated', function (): void {
    $this->bridge('/')
        ->assertSee('Welcome')
        ->assertSee('Please login to continue')
        ->assertVisible('[data-testid="login-link"]');
});

test('user can navigate to login page', function (): void {
    $this->bridge('/')
        ->click('[data-testid="login-link"]')
        ->wait(1)
        ->assertPathContains('/login')
        ->assertVisible('[data-testid="login-form"]');
});

test('login page shows form elements', function (): void {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->assertVisible('[data-testid="password-input"]')
        ->assertVisible('[data-testid="login-button"]');
});

test('user can login with valid credentials', function (): void {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->fill('[data-testid="email-input"]', 'test@example.com')
        ->wait(0.3)
        ->fill('[data-testid="password-input"]', 'password')
        ->wait(0.3)
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertPathContains('/dashboard')
        ->assertVisible('[data-testid="dashboard-page"]')
        ->assertSee('Test User');
});

test('user sees error with invalid credentials', function (): void {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->fill('[data-testid="email-input"]', 'wrong@example.com')
        ->wait(0.3)
        ->fill('[data-testid="password-input"]', 'wrongpassword')
        ->wait(0.3)
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertVisible('[data-testid="login-error"]');
});

test('authenticated user can access dashboard', function (): void {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->fill('[data-testid="email-input"]', 'test@example.com')
        ->wait(0.3)
        ->fill('[data-testid="password-input"]', 'password')
        ->wait(0.3)
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertVisible('[data-testid="user-name"]')
        ->assertSee('Test User')
        ->assertVisible('[data-testid="user-email"]')
        ->assertSee('test@example.com');
});

test('user can logout from dashboard', function (): void {
    $this->bridge('/login')
        ->assertVisible('[data-testid="email-input"]')
        ->fill('[data-testid="email-input"]', 'test@example.com')
        ->wait(0.3)
        ->fill('[data-testid="password-input"]', 'password')
        ->wait(0.3)
        ->click('[data-testid="login-button"]')
        ->wait(2)
        ->assertVisible('[data-testid="logout-button"]')
        ->click('[data-testid="logout-button"]')
        ->wait(2)
        ->assertPathContains('/login');
});

test('unauthenticated user is redirected from dashboard', function (): void {
    $this->bridge('/dashboard')
        ->wait(2)
        ->assertPathContains('/login');
});
