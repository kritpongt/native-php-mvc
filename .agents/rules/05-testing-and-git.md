---
description: Testing rules, PHPUnit conventions, and Git version control workflows
trigger: model_decision
---

# Testing & Git Conventions

## Testing Conventions
- `tests/Unit/` — all services and core classes, no DB, repositories mocked
- `tests/Integration/` — repositories/migrations against a real DB, using the driver set in phpunit.xml env
- SQL portability is proven by running the integration suite on both MySQL and PostgreSQL (as a CI matrix once CI exists)
- Security-critical services (auth, tokens, csrf, rate limiting) must have unit tests in the same commit as the feature
- Bug fixes require a reproducing test before the fix

## Version Control (Git)
- Use **Conventional Commits** for commit messages (e.g., `feat: ...`, `fix: ...`, `refactor: ...`).

## Commands (after scaffolding)
- `composer test` — run the full PHPUnit suite
- `composer test:unit` / `composer test:integration`
- `php bin/console migrate` / `migrate:rollback` — hand-written migration runner
- `php -S localhost:8000 -t public` — dev server
- `npm run dev` — tailwind watch mode | `npm run build` — minify, then commit the output
