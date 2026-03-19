# Lighthouse — Implementation TODO List

## Phase 1 — Core
- [x] Create project structure
- [x] Implement index.php dispatcher
- [x] URL to file resolver
- [x] 404 handler

## Phase 2 — HTTP Engine
- [x] Response wrapper
- [x] Header manager
- [x] ETag system
- [x] Cache-Control logic

## Phase 3 — Template System
- [x] Implement lh_layout()
- [x] Implement lh_partial()
- [x] Multi-layout support
- [x] Layout and partial rendering

## Phase 4 — Database Layer
- [x] PDO wrapper
- [x] Query helpers
- [x] Transactions

## Phase 5 — Frontend
- [x] default.css (PicoCSS fork)
- [x] Hamburger menu
- [x] default.js

## Phase 5a — Configuration & Development Mode
- [x] Adopt native INI files as the primary configuration format
- [x] Add `config/config.ini.example` as the committed configuration contract
- [x] Define required base sections such as `[database]` and `[mail]`
- [x] Define rules for project-specific sections such as `[stripe]`
- [x] Ensure `config.ini.example` is never used as a runtime fallback
- [x] Enforce `config.ini.example` as a strict contract with no extra keys allowed
- [x] Introduce environment detection / `APP_ENV`
- [x] Map `lighthousephp serve` to `config/config.development.ini`
- [x] Map `lighthousephp test` to `config/config.testing.ini`
- [x] Make bootstrap fail fast when the environment config file is missing
- [x] Expose configuration through `lh_config('section.key')`
- [x] Load configuration once during bootstrap
- [x] Add development mode defaults
- [x] Disable HTTP caching in development
- [x] Disable ETag / 304 responses in development
- [x] Define dev/testing/staging/production behavior for headers and debugging
- [x] Define GitHub Actions generation of `config/config.staging.ini` and `config/config.production.ini`
- [x] Define build-time injection of staging/production config into FrankenPHP executables
- [x] Document local development workflow with FrankenPHP

## Phase 6 — Authentication
- [x] Session auth
- [x] Login/logout
- [x] CSRF protection
- [x] Token auth

## Phase 7 — Testing Framework
- [x] Test runner
- [x] Assertions
- [x] HTTP test utilities

## Phase 8 — CLI
- [x] Project generator
- [x] Command parsing
- [x] Serve command
- [x] Migrate command
- [x] Test command

## Phase 9 — Migrations
- [x] SQL file parser
- [x] CLI migrate up/down
- [x] Migration tracking

### Phase 10 - CLI and Github Project
- [x] Use GitHub release tags as the canonical Lighthouse version number
- [x] Remove manual `VERSION` drift by deriving installed version from the release/version source of truth
- [x] Install Lighthouse globally from the GitHub repo via `curl | sh`
- [x] Support branch/tag/version selection during install
- [x] Add `lighthouse version`
- [x] Add `lighthouse update-available`
- [x] Add `lighthouse self-update`
- [x] Add `lighthouse uninstall`
- [x] Persist install metadata for update and removal workflows
- [x] Download packaged framework bundles from GitHub releases instead of relying on a local checkout
- [x] Add release packaging for installer-compatible bundles
- [x] Add GitHub release publication workflow

## Phase 11 — Framework / App Separation
- [x] Move framework-owned application directories under `src/`
- [x] Move release and development shell tooling under `scripts/`
- [x] Keep `tests/` at the repository root for framework-only tests
- [x] Keep `docs/` outside the application runtime and install payload
- [x] Keep `lighthouse` and `lighthousephp` at the repository root
- [x] Introduce a canonical app-root resolver
- [x] Treat `src/` as the app root when the framework repository is running locally
- [x] Treat the project root as the app root in generated Lighthouse applications
- [x] Refactor runtime path resolution to use the canonical app root
- [x] Refactor CLI path resolution to use the canonical app root
- [x] Refactor HTTP entrypoints to use the canonical app root
- [x] Refactor migration/config/layout/page discovery to use the canonical app root
- [x] Ensure `lighthouse new` copies `src/*` into the generated app root as flattened directories
- [x] Ensure `lighthouse new` does not copy framework tests into generated apps, but create an empty test directory
- [x] Ensure install payloads exclude framework docs, framework tests, and release scripts
- [x] Keep generated apps autosufficient while preserving `core/` as framework-managed code
- [x] Document that `core/` is framework-owned and should not be modified in user apps
- [x] Verify framework tests continue to run correctly against `src/` as the framework app root

## Phase 12 — Cache System
- File cache engine
- TTL support
- Tag system
- Invalidation API

## Phase 13 — Lighthouse Checker
- Audit integration
- Report output

## Phase 14 — Preflight
- Environment checks
- Security validation
- Production readiness

## Phase 15 — Hardening
- CSP tuning
- Performance validation
- Edge cases handling
