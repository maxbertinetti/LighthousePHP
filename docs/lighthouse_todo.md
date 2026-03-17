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

## Phase 5 — Migrations
- SQL file parser
- CLI migrate up/down
- Migration tracking

## Phase 6 — Authentication
- Session auth
- Login/logout
- CSRF protection
- Token auth

## Phase 7 — Testing Framework
- Test runner
- Assertions
- HTTP test utilities

## Phase 8 — Frontend
- default.css (PicoCSS fork)
- Hamburger menu
- default.js

## Phase 9 — CLI
- Project generator
- Command parsing
- Serve command
- Migrate command
- Test command

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
