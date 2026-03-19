# Lighthouse — Product Requirements Document (PRD)

## 1. Overview
Lighthouse is a procedural PHP framework with zero OOP and no Composer dependency. It uses file-based routing and is designed to deliver optimal performance, security, and SEO out of the box, targeting 100/100 Lighthouse scores under correct usage.

## 2. Goals
- File-based routing (no route registration)
- Zero runtime dependencies
- Built-in performance optimizations
- Automatic security and SEO best practices
- Simple developer experience

## 3. Non-Goals
- No OOP patterns
- No ORM
- No dependency injection container
- No Composer usage

## 4. Architecture

### 4.0 Configuration
Lighthouse uses native PHP INI configuration files instead of `.env` as the primary configuration format.

Configuration files:
- `config/config.ini.example` committed in the repository as the configuration contract
- `config/config.development.ini` for local development
- `config/config.testing.ini` for test runs
- `config/config.staging.ini` for staging builds
- `config/config.production.ini` for production builds

Rules:
- `config.ini.example` contains all expected sections and keys with fake but realistic values
- optional project integrations add their own sections (for example `[stripe]`)
- `config.ini.example` is documentation and template only, never a runtime fallback
- `config.ini.example` is a strict contract: environment files must contain the exact same sections and keys
- missing environment config must fail fast with a clear error
- extra keys are not allowed
- runtime configuration is selected by environment, not by manually copying files

Environment selection:
- `lighthousephp serve` and `lighthouse serve` use `config/config.development.ini`
- `lighthousephp test` and `lighthouse test` use `config/config.testing.ini`
- CI/CD generates `config/config.staging.ini` and `config/config.production.ini` dynamically before building the FrankenPHP executable

Mail behavior:
- development uses the configured SMTP server, with Mailpit as the intended local default
- testing uses a file outbox for deterministic mail assertions

FrankenPHP deployment model:
- staging and production builds are environment-specific artifacts
- sensitive values are injected during CI build time
- the final executable contains the resolved configuration for its target environment
- deployment ships the built artifact, not a separate runtime config workflow

### 4.1 Routing
Each URL maps directly to a PHP file in `/pages`.

Examples:
- /about → /pages/about.php
- /blog/post → /pages/blog/post.php

Each file handles HTTP methods internally.

### 4.2 Entry Point
`/public/index.php` handles:
- URL normalization
- File resolution
- Cache check
- Execution

## 5. Project Structure

/view
  /layouts
  /partials
/pages
/core
/db
/migrations
/tests
/cache
/public
  /assets
/config
/lighthousephp
/lighthouse
/install.sh
/remove.sh

## 6. Layout System

Supports multiple layouts and reusable partials.

Function:
```
lh_layout($data = [], $layout = 'main');
lh_partial($name, $data = []);
```

Structure:
/view/layouts/main.php  
/view/layouts/admin.php
/view/partials/header.php
/view/partials/footer.php  

## 7. HTTP Engine

Automatically handles:
- Security headers (CSP, HSTS, etc.)
- Cache-Control
- ETag
- Compression (via FrankenPHP)

## 8. Cache System

### File-based caching
- /about → /cache/about.html

### API
```
lh_cache_enable($ttl);
lh_cache_disable();
lh_cache_tag('users');
lh_cache_invalidate('users');
```

### Strategy
- TTL-based
- Tag-based invalidation
- Metadata stored in JSON

## 9. Authentication

### Session-based
- Secure cookies
- CSRF protection
- Database-backed user sessions
- Registration
- Login/logout
- Password reset request and reset flow
- Account/profile update UI
- Password change flow

### Token-based
- Bearer tokens for APIs

### Database tables
- `users`
- `password_reset_tokens`

## 10. Database Layer

- PDO-based
- SQL-first approach
- Migration system using raw SQL
- Paired `*.up.sql` / `*.down.sql` migration files
- Migration tracking table

## 11. Frontend

### default.css
- Based on PicoCSS
- Includes responsive hamburger menu

### default.js
- Minimal HTMX-like behavior

## 12. CLI

Commands:
- version
- update-available
- self-update
- uninstall
- new
- serve
- migrate
- test
- lighthouse-check
- preflight

CLI layers:
- `lighthousephp` is the project-local CLI
- `lighthouse` is the global CLI/installer-facing command
- the global `lighthouse` command delegates project commands to the nearest Lighthouse project
- Lighthouse is installable globally from the GitHub repository via `curl | sh`
- GitHub release tags are the intended canonical version source for installer/update flows
- installed versions are derived from release tags or install metadata, not from a committed `VERSION` file

Behavior:
- `serve` resolves the application in `development` mode
- `test` resolves the application in `testing` mode
- the CLI sets the environment context and the application bootstrap loads the matching INI file
- the CLI does not use `config.ini.example` as runtime configuration
- configuration is loaded once and read via `lh_config('section.key')`
- global install metadata is persisted for update and removal workflows

## 13. Testing

- Unit
- Integration
- HTTP tests
- Lightweight built-in test runner
- Assertion helpers
- HTTP request simulation utilities

## 14. Performance Strategy
- Zero dependencies
- File caching
- Optimized headers
- No render-blocking assets

## 15. Constraints
Lighthouse score depends on content quality; framework enforces best practices but cannot guarantee 100 in all cases.
