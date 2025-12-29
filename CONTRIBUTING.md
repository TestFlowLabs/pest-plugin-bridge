# Contributing to Pest Bridge Plugin

Thank you for considering contributing to Pest Bridge Plugin! This document outlines the process for contributing to this project.

## Development Setup

### Requirements

- PHP 8.3+
- Node.js (LTS)
- Composer

### Installation

```bash
# Clone the repository
git clone https://github.com/TestFlowLabs/pest-plugin-bridge.git
cd pest-plugin-bridge

# Install PHP dependencies
composer install

# Install Node.js dependencies (for Playwright)
npm ci

# Install Playwright browsers
npx playwright install --with-deps chromium
```

### Running Tests

```bash
# Run all checks (rector, pint, phpstan, tests)
composer test

# Run individual checks
composer test:pint      # Code style
composer test:phpstan   # Static analysis
composer test:unit      # Unit tests with coverage
composer test:types     # Type coverage

# Run specific test file
./vendor/bin/pest tests/Unit/BridgeTest.php

# Run browser tests
./vendor/bin/pest tests/Browser
```

### Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code styling. Before submitting a PR:

```bash
# Check code style
composer test:pint

# Fix code style automatically
composer pint
```

## Pull Request Process

### Before Submitting

1. **Create an issue first** - Discuss the change you want to make
2. **Fork the repository** - Work on your own fork
3. **Create a feature branch** - `git checkout -b feature/your-feature`
4. **Write tests** - All new features should have tests
5. **Run the test suite** - Ensure all tests pass with `composer test`
6. **Update documentation** - If your change affects the public API

### PR Guidelines

- **One feature per PR** - Keep PRs focused and small
- **Descriptive title** - Use conventional commit format: `feat:`, `fix:`, `docs:`, `refactor:`
- **Link to issue** - Reference the issue number in your PR description
- **Add tests** - New features require tests, bug fixes should include regression tests
- **Update docs** - Document any public API changes in `docs/`

### Commit Messages

We use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add support for Angular frontend detection
fix: resolve timeout issue with slow frontend servers
docs: add troubleshooting guide for CORS errors
refactor: simplify FrontendServer process management
test: add edge case tests for URL encoding
chore: update dependencies
```

## Development Guidelines

### Code Structure

```
src/
├── Autoload.php           # Pest plugin registration
├── Bridge.php             # Configuration registry (static API)
├── BridgeTrait.php        # Test trait with bridge() method
├── BrowserMockStore.php   # In-memory store for browser mocks
├── FrontendDefinition.php # Builder for frontend configuration
├── FrontendManager.php    # Static class managing all servers
├── FrontendServer.php     # Individual server process management
├── ServerMarker.php       # Marker-based server identification
└── Laravel/
    └── BridgeHttpFakeMiddleware.php  # Cross-process HTTP faking
```

### Adding New Features

1. **Discuss first** - Open an issue to discuss the feature
2. **Keep it simple** - The plugin should remain lightweight
3. **Follow existing patterns** - Look at how similar features are implemented
4. **Add tests** - Unit tests in `tests/Unit/`, browser tests in `tests/Browser/`
5. **Document** - Update relevant docs in `docs/`

### Testing Philosophy

- **Unit tests** - Test individual classes in isolation
- **Feature tests** - Test integration between components
- **Browser tests** - Test actual browser behavior with real frontends

## Documentation

Documentation lives in `docs/` and uses [VitePress](https://vitepress.dev/).

### Running Documentation Locally

```bash
# Start dev server
npm run docs:dev

# Build for production
npm run docs:build
```

### Documentation Structure

- `docs/getting-started/` - Installation and quick start
- `docs/guide/` - Core concepts and configuration
- `docs/ci-cd/` - CI/CD integration guides
- `docs/examples/` - Framework-specific examples

## Reporting Issues

### Bug Reports

Please include:

1. **PHP version** - `php -v`
2. **Package versions** - `composer show testflowlabs/pest-plugin-bridge`
3. **Minimal reproduction** - Smallest code that reproduces the issue
4. **Expected behavior** - What you expected to happen
5. **Actual behavior** - What actually happened
6. **Error messages** - Full stack traces if applicable

### Feature Requests

Please include:

1. **Use case** - What problem are you trying to solve?
2. **Proposed solution** - How do you envision it working?
3. **Alternatives considered** - Other approaches you've thought about

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](https://www.contributor-covenant.org/version/2/1/code_of_conduct/). Please be respectful and inclusive in all interactions.

## Questions?

- Open a [GitHub Discussion](https://github.com/TestFlowLabs/pest-plugin-bridge/discussions)
- Check existing [issues](https://github.com/TestFlowLabs/pest-plugin-bridge/issues)

Thank you for contributing!
