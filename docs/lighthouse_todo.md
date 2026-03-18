# Lighthouse — Implementation TODO List

## Phase 1 — Core
- Create project structure
- Implement index.php dispatcher
- URL to file resolver
- 404 handler

## Phase 2 — HTTP Engine
- Response wrapper
- Header manager
- ETag system
- Cache-Control logic

## Phase 3 — Template System
- Implement lh_layout()
- Implement lh_partial()
- Multi-layout support
- Layout and partial rendering

## Phase 4 — Database Layer
- PDO wrapper
- Query helpers
- Transactions

## Phase 5 — Frontend
- default.css (PicoCSS fork)
- Hamburger menu
- default.js

## Phase 5a — Configuration & Development Mode
- Adopt native INI files as the primary configuration format
- Add `config/config.ini.example` as the committed configuration contract
- Define required base sections such as `[database]` and `[mail]`
- Define rules for project-specific sections such as `[stripe]`
- Ensure `config.ini.example` is never used as a runtime fallback
- Enforce `config.ini.example` as a strict contract with no extra keys allowed
- Introduce environment detection / `APP_ENV`
- Map `lighthousephp serve` to `config/config.development.ini`
- Map `lighthousephp test` to `config/config.testing.ini`
- Make bootstrap fail fast when the environment config file is missing
- Expose configuration through `lh_config('section.key')`
- Load configuration once during bootstrap
- Add development mode defaults
- Disable HTTP caching in development
- Disable ETag / 304 responses in development
- Define dev/testing/staging/production behavior for headers and debugging
- Define GitHub Actions generation of `config/config.staging.ini` and `config/config.production.ini`
- Define build-time injection of staging/production config into FrankenPHP executables
- Document local development workflow with FrankenPHP

## Phase 6 — Authentication
- Session auth
- Login/logout
- CSRF protection
- Token auth

## Phase 7 — Testing Framework
- Test runner
- Assertions
- HTTP test utilities

## Phase 8 — CLI
- Project generator
- Command parsing
- Serve command
- Migrate command
- Test command

## Phase 9 — Migrations
- SQL file parser
- CLI migrate up/down
- Migration tracking

## Phase 10 — Cache System
- File cache engine
- TTL support
- Tag system
- Invalidation API

## Phase 11 — Lighthouse Checker
- Audit integration
- Report output

## Phase 12 — Preflight
- Environment checks
- Security validation
- Production readiness

## Phase 13 — Hardening
- CSP tuning
- Performance validation
- Edge cases handling
