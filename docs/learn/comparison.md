# Comparison with Alternatives

## Quick Overview

| Feature | Pest Bridge | Pest Browser | Laravel Dusk | Cypress | Playwright |
|---------|-------------|--------------|--------------|---------|------------|
| **Primary Target** | Laravel API + External SPA | Laravel-served apps | Laravel-served apps | Any frontend | Any frontend |
| **Test Language** | PHP | PHP | PHP | JavaScript | JS/TS/Python |
| **Laravel Factories** | ✅ Yes | ✅ Yes | ✅ Yes | ❌ No | ❌ No |
| **External Frontend** | ✅ Native `bridge()` | ⚠️ Manual URL | ❌ No | ✅ Yes | ✅ Yes |

## Server Management

| Feature | Pest Bridge | Pest Browser | Laravel Dusk | Cypress | Playwright |
|---------|-------------|--------------|--------------|---------|------------|
| **Laravel API Start** | ✅ Auto (in-process) | ✅ Auto (in-process) | ❌ Manual `artisan serve` | ❌ Manual | ❌ Manual |
| **Frontend Start** | ✅ Auto `serve()` | ❌ Manual | ❌ N/A | ❌ Manual | ❌ Manual |
| **API URL Injection** | ✅ Auto (Vite, Nuxt, Next, CRA) | ❌ Manual config | ❌ N/A | ❌ Manual config | ❌ Manual config |
| **Multi-Frontend** | ✅ Named + child | ❌ No | ❌ No | ❌ No | ❌ No |

## Browser Support

| Feature | Pest Bridge | Pest Browser | Laravel Dusk | Cypress | Playwright |
|---------|-------------|--------------|--------------|---------|------------|
| **Chrome** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Firefox** | ✅ | ✅ | ❌ | ✅ | ✅ |
| **Safari/WebKit** | ✅ | ✅ | ❌ | ⚠️ Experimental | ✅ |

## HTTP Mocking Capabilities

| Mock Type | Pest Bridge | Pest Browser | Laravel Dusk | Cypress | Playwright |
|-----------|-------------|--------------|--------------|---------|------------|
| **Backend → External API** | ✅ `Bridge::fake()` | ✅ `Http::fake()` | ✅ `Http::fake()` | ❌ No PHP access | ❌ No PHP access |
| **Browser → Any API** | ✅ `mockBrowser()` | ❌ No | ❌ No | ✅ `cy.intercept()` | ✅ `page.route()` |

::: info Why Both Matter
**Backend mocking**: Your Laravel code calls Stripe → mock the Stripe response
**Browser mocking**: Your frontend JS calls a weather API → mock that response
:::

## Testing Features

| Feature | Pest Bridge | Pest Browser | Laravel Dusk | Cypress | Playwright |
|---------|-------------|--------------|--------------|---------|------------|
| **Visual Regression** | ✅ Built-in | ✅ Built-in | ❌ No | ⚠️ Plugin | ✅ Built-in |
| **Accessibility** | ✅ Built-in | ✅ Built-in | ❌ No | ⚠️ Plugin | ⚠️ Plugin |

## Debugging Experience

| Feature | Pest Bridge | Pest Browser | Laravel Dusk | Cypress | Playwright |
|---------|-------------|--------------|--------------|---------|------------|
| **Pause & Inspect** | ✅ `debug()` | ✅ `debug()` | ❌ No | ✅ Yes | ✅ Yes |
| **Screenshots** | ✅ Manual + on failure | ✅ Manual + on failure | ✅ Manual | ✅ Auto | ✅ Auto |
| **Headed Mode** | ✅ `--headed` | ✅ `--headed` | ✅ Yes | ✅ Default | ✅ `--headed` |
| **DOM Time-Travel** | ❌ | ❌ | ❌ | ✅ Click any step | ❌ |
| **Test Recording** | ❌ | ❌ | ❌ | ❌ | ✅ Trace Viewer |

## Learning Curve

| Tool | Difficulty | Best For |
|------|------------|----------|
| **Pest Bridge** | Low | Pest/Laravel developers |
| **Pest Browser** | Low | Pest/Laravel developers |
| **Laravel Dusk** | Low | Laravel developers |
| **Cypress** | Medium | JavaScript developers |
| **Playwright** | Medium | JavaScript/Python developers |

## Decision Guide

**Choose Pest Bridge Plugin if you:**
- Have a Laravel API with a separate frontend (Vue, React, Nuxt, Next.js)
- Want to write tests in PHP using familiar Pest syntax
- Need access to Laravel factories, seeders, and database
- Want automatic frontend server management with `->serve()`
- Need to mock both backend AND browser HTTP calls

**Choose Pest Browser (without Bridge) if:**
- Your frontend is served by Laravel (Blade, Livewire, Inertia)
- You don't need external frontend URL management

**Choose Cypress/Playwright directly if:**
- You prefer writing tests in JavaScript
- Time-travel debugging is critical for your workflow
- You don't need Laravel database factories in tests
