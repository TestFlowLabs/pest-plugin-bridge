# CI/CD Integration

Running browser tests with pest-plugin-bridge in CI/CD is the primary use case for this plugin. This section provides modular documentation for GitHub Actions.

## How Do I Combine Two Separate Repositories?

::: tip The Key Question
If your Laravel API and frontend live in separate repositories, you're probably wondering: **"How do I bring them together in CI?"**
:::

The answer is simple: **GitHub Actions can checkout multiple repositories in a single workflow.**

```yaml
steps:
  # 1. Checkout your API repo into ./backend
  - uses: actions/checkout@v4
    with:
      path: backend

  # 2. Checkout your frontend repo into ./frontend
  - uses: actions/checkout@v4
    with:
      repository: your-org/frontend-repo
      path: frontend
```

After this, your directory structure in CI looks like:

```
$GITHUB_WORKSPACE/
+-- backend/                # Your Laravel API
|   +-- app/
|   +-- tests/Browser/
|   +-- composer.json
+-- frontend/               # Your frontend repo
    +-- src/
    +-- package.json
```

Then configure Bridge to use the frontend:

```php
// backend/tests/Pest.php
Bridge::setDefault('http://localhost:3000')
    ->serve('npm run dev', cwd: '../frontend');
```

**That's it.** Both projects are now in the same runner, and pest-plugin-bridge handles starting/stopping servers automatically.

For complete details including private repos and branch synchronization, see [Multi-Repository](./multi-repo).

---

## How This Section Works

Each page is a **standalone module** you can combine:

| Page | What It Adds |
|------|--------------|
| [Basic Setup](./basic-setup) | Minimal working workflow (monorepo, no database) |
| [Multi-Repository](./multi-repo) | Separate frontend repository checkout |
| [Manual Triggers](./manual-trigger) | Run tests manually with branch/group selection |
| [SQLite Database](./sqlite) | File-based SQLite configuration |
| [MySQL Database](./mysql) | MySQL service or external connection |
| [Caching](./caching) | Speed up CI with dependency caching |
| [Debugging](./debugging) | Screenshots, artifacts, troubleshooting |
| [Advanced](./advanced) | Matrix builds, parallel tests, timeouts |

**Start with [Basic Setup](./basic-setup)**, then add modules as needed.

## CI Execution Flow

<CIFlowDiagram />

## Prerequisites

Before setting up CI/CD, ensure you have:

- A Laravel API project with pest-plugin-bridge installed
- A frontend project (Nuxt, React, Vue, etc.) in the same repo or separate repo
- Basic familiarity with GitHub Actions

## Repository Structures

### Monorepo

```
my-app/
+-- backend/                    # Laravel API
|   +-- app/
|   +-- tests/
|   |   +-- Browser/
|   +-- composer.json
+-- frontend/                   # Frontend app
|   +-- src/
|   +-- package.json
+-- .github/
    +-- workflows/
        +-- browser-tests.yml
```

### Multi-Repository

```
your-organization/
+-- api/                        # Laravel API repository
|   +-- tests/Browser/          # Browser tests live here
|   +-- .github/workflows/      # CI runs from API repo
|
+-- frontend/                   # Separate frontend repository
    +-- src/
    +-- package.json
```

## Quick Start

1. **Start with [Basic Setup](./basic-setup)** - Get a minimal workflow running
2. **Add [Multi-Repository](./multi-repo)** if you have separate repos
3. **Add [Manual Triggers](./manual-trigger)** for on-demand testing with branch selection
4. **Add [SQLite](./sqlite) or [MySQL](./mysql)** for database tests
5. **Add [Caching](./caching)** to speed up builds
6. **Add [Debugging](./debugging)** for failure artifacts
