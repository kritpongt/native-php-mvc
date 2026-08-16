---
description: Security guidelines, Validation, and Storage conventions
trigger: always_on
---

# Security & Storage Conventions

**Priorities: security > speed > maintainability**

## Security Conventions (top priority — never violate)
- **SQL**: PDO prepared statements only — never concatenate strings into a query under any circumstances; `ATTR_EMULATE_PREPARES = false`, `ERRMODE_EXCEPTION`
- **XSS**: Twig autoescape always on — `|raw` is allowed only for system-generated values, and every use must carry a comment explaining why
- **CSRF**: token verified by middleware on every state-changing method (POST/PUT/PATCH/DELETE) — HTMX sends it via `hx-headers` from a meta tag in the layout
- **Session**: `httponly`, `secure`, `samesite=Lax`, cookies only (never accept a session id from the URL), `session_regenerate_id()` after every login/logout
- **Password**: `password_hash()` with `PASSWORD_ARGON2ID` only
- **Headers**: central middleware sends CSP, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: same-origin` on every response
- **Validation**: validate at the entry boundary (controller) — inner layers assume data is already valid and do not re-validate
- **Errors**: never show stack traces in production — log to `storage/logs/` and render a generic error page
- **Auth**: login must be rate-limited (failure count per email+IP stored in the DB) — this lives in `AuthService`, not a middleware, because it needs the email and pairs with the timing defense below; use `hash_equals()` when comparing tokens
- **Login timing**: every attempt pays exactly one `password_verify()` — unknown email verifies against a dummy Argon2id hash, so response time never reveals whether an account exists
- **Remember-me**: cookie is `selector:validator` — DB stores only the validator's hash; single-use: every cookie login burns the row and issues a fresh token; valid selector + wrong validator = stolen token → delete every token for that user

## Storage Conventions
- `storage/` is the app's **single writable root** — the web user may write only here, everything else is read-only; always outside the web root
- Twig cache is executable PHP — `storage/` permissions must be as narrow as possible, never world-writable (writable = RCE)
- `.gitignore` the entire `storage/` from the first commit (keep the skeleton with `.gitkeep`)
- Logs are written one file per day (`app-YYYY-MM-DD.log`) with cleanup of old files — do not rely on OS logrotate
- **Never log sensitive data** (passwords, tokens, session ids) under any circumstances
- Cache/log paths are read from config with `storage/` as the default — overridable via env (supports Docker/atomic deploys without code changes)
