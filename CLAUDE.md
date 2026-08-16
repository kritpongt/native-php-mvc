# native-php-mvc

Native PHP MVC — the entire core is hand-written (router, DI, request/response, migration runner).
Long-term project intended as the foundation for multiple future projects.

**Priorities: security > speed > maintainability**

## Stack

- PHP 8.5+ — `declare(strict_types=1)` in every file, full type hints on every parameter and return
- Allowed libraries: **Twig** (view), **PHPUnit** (test), **HTMX** (frontend), **Tailwind CSS** (styling) — adding any other dependency requires discussion first
- HTMX must be self-hosted in `public/assets/` (no CDN, to keep CSP strict)
- Tailwind via **Node build pipeline** (npm) — dev-time only, never a runtime dependency: compile `resources/css/tailwind.css` → `public/assets/tailwind.css` as a static file and commit it (production needs no node)
- Node supply chain: commit `package-lock.json` and install with `npm ci`, git-ignore `node_modules/`; adding npm packages/Tailwind plugins requires discussion first, same as any other dependency
- Database: **switchable between MySQL/MariaDB and PostgreSQL** via per-project config

## Structure

```
public/              # the only docroot the web server sees — all other code lives outside the web root
├── index.php        # front controller
└── assets/          # tailwind.css (built by tailwind — committed), htmx.min.js
resources/
└── css/tailwind.css # tailwind source → built to public/assets/tailwind.css
core/                # hand-written framework — separate from app code so child projects can upgrade core without touching app/
│                    # Router, Request, Response, Database, Session(+Interface), Csrf, Container, Env, migration runner
└── Console/         # console-only core helpers (MigrationMaker, Prompt)
app/
├── Controllers/     # one subfolder per zone (Backoffice/, …) — namespace mirrors the folder
├── Models/          # Entity (data) separated from Repository (talks to the DB)
├── Services/        # business logic
├── Middlewares/     # StartSession, VerifyCsrf, SecurityHeaders, Auth, Guest, Permission
├── Views/           # Twig — layouts/, pages/, partials/ (partial = fragment for HTMX)
├── Console/         # console commands (CreateUser, SyncRbac) — wired up in bin/console
└── Routes/          # one file per zone: web.php, backoffice.php
bin/
└── console          # console entry point: migrate, migrate:rollback, makefile:migration, user:create, rbac:sync
config/              # reads values from env only — no secrets in files
migrations/          # PHP migration classes, run by the hand-written migration runner
storage/             # the app's single writable root (git-ignored entirely)
├── cache/twig/
└── logs/
tests/
├── Unit/            # no DB — mirrors app/ structure
└── Integration/     # repositories + controllers against a real DB
.env                 # secrets — never committed (.env.example is committed)
```

- PSR-4: `Core\` → `core/`, `App\` → `app/`, `Tests\` → `tests/`
- Tests mirror source: `app/Services/AuthService.php` → `tests/Unit/Services/AuthServiceTest.php`

## Core conventions (class design & DI)

- **Coding Style**:
  - Indent using **Tabs**, not spaces.
  - Brace placement: Classes and Methods use the next line (Allman style, e.g., `class Foo \n {`). Control structures use the same line with no space before the brace (e.g., `if(...){`).
- **Types**: Use `strict_types=1` in every file. Use **PHPDoc** heavily for arrays and generics (e.g. `/** @var array<int, User> */`) — no external static analysis tools (like PHPStan) are required, so PHPDoc is our source of truth.
- Classes are `final` by default — opening one up is a deliberate decision, per this table:
  - **core classes that touch PHP globals/IO** (e.g. `Session` → `SessionInterface`): extract an interface; consumers type-hint the interface, bootstrap binds interface → implementation in the container
  - **repositories**: plain non-final classes so unit tests can mock them directly — no interface ceremony
  - **services**: stay `final` — they are the unit under test, never mocked
  - **`Config`**: stays `final` and is never mocked — tests construct a real `Config` pointed at fixture values
- Container: concrete classes are autowired by reflection; interfaces (and anything needing setup) are bound explicitly in the entry points — bindings live nowhere else

## Security conventions (top priority — never violate)

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

## Storage conventions

- `storage/` is the app's **single writable root** — the web user may write only here, everything else is read-only; always outside the web root
- Twig cache is executable PHP — `storage/` permissions must be as narrow as possible, never world-writable (writable = RCE)
- `.gitignore` the entire `storage/` from the first commit (keep the skeleton with `.gitkeep`)
- Logs are written one file per day (`app-YYYY-MM-DD.log`) with cleanup of old files — do not rely on OS logrotate
- **Never log sensitive data** (passwords, tokens, session ids) under any circumstances
- Cache/log paths are read from config with `storage/` as the default — overridable via env (supports Docker/atomic deploys without code changes)

## Database portability (MySQL ↔ PostgreSQL)

- Identifiers (tables/columns) are lowercase `snake_case` only — so nothing ever needs quoting (backticks vs double quotes differ between drivers)
- Plural table names (`users`, `login_attempts`), FKs named `<singular>_id`
- Repository SQL must be ANSI SQL that runs on both drivers — no driver-specific syntax (e.g. `ON DUPLICATE KEY UPDATE`, `RETURNING`)
- Where a difference is unavoidable, wrap it in a single method on `Core\Database` (e.g. `lastInsertId()`) — repositories must never know which driver is in use
- Migrations are PHP classes with `up()`/`down()` — where DDL differs (AUTO_INCREMENT vs IDENTITY), branch on `$db->driver()` inside the migration
- Every table has `created_at` and `updated_at`, set by the app — no DB triggers/defaults, which differ between drivers

## MVC conventions

- Thin controllers: receive Request → call Service → return Response — no business logic, never touch PDO directly
- Constructor injection only — no globals, no static state, no service locator (`$_ENV` may be read only inside `config/`)
- Controller methods follow REST naming: `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`
- Naming in `app/Models/`: entities are **singular** (`User`, `Role`) — one instance = one row; repositories are `<Entity>Repo`, also singular (`UserRepo`, `LoginAttemptRepo`) — only table names stay plural
- All routes are declared in `app/Routes/` — one file per zone (`web.php`, `backoffice.php`); this directory is the security audit surface: reading it must answer which endpoints are public / require login / are rate-limited
- Route handlers are `[Controller::class, 'method']` only — closures are forbidden
- Middleware binds at the route/group level | middleware that must run on every request (SecurityHeaders, Session, Csrf) is a global pipeline in bootstrap — never opt-in per route
- **API Response Format**: If returning JSON, standardize the wrapper: `{"success": true, "data": {...}}` or `{"success": false, "error": "Message"}`.

## HTMX conventions

- Controllers check the `HX-Request` header: if present, render the partial; otherwise render the full page — every page must work without JS (progressive enhancement)
- Markup is written once in `app/Views/partials/` and full pages `{% include %}` it
- Twig templates are named `snake_case.html.twig` | pages mirror controllers: `UserController::index` → `app/Views/pages/users/index.html.twig`

## Testing conventions

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
