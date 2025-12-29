# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### Core API
- `Bridge::add(url, name?)` for registering frontend URLs (default or named)
- `bridge()` method for navigating to external frontend URLs in tests
- Automatic 30-second timeout for Vite cold-start handling

#### Server Management
- `->serve(command, cwd)` for automatic frontend server lifecycle
- `->readyWhen(pattern)` for custom server ready detection
- `->warmup(milliseconds)` for additional startup delay
- Marker-based server identification for safe server reuse
- `->trustExistingServer()` escape hatch for manual server starts

#### Environment Variables
- Automatic environment variable injection for API URLs
- Support for Vite, Nuxt, Next.js, Create React App, and Angular
- `->env(vars)` for custom environment variables with path suffixes
- `->envFile(path)` for Vite mode-specific .env file support

#### HTTP Mocking
- `Bridge::fake(patterns)` for backend HTTP mocking (Laravel)
- `Bridge::mockBrowser(patterns)` for frontend HTTP mocking (fetch/XHR)
- `BridgeHttpFakeMiddleware` for cross-process HTTP faking

#### Multi-Frontend Support
- Named frontends via `Bridge::add(url, 'name')`
- `->child(path, name)` for child frontends sharing same server
- Full URL building with `Bridge::buildUrl(path, name?)`

#### Documentation
- Comprehensive VitePress documentation site
- Getting started guides
- CI/CD integration examples
- Troubleshooting and debugging guides

<!--
## [1.0.0] - YYYY-MM-DD

### Added
- Feature description

### Changed
- Change description

### Deprecated
- Deprecation notice

### Removed
- Removal description

### Fixed
- Bug fix description

### Security
- Security fix description
-->
