---
description: Core conventions, Coding Style, Types, and MVC guidelines
trigger: always_on
---

# Core & MVC Conventions

## Coding Style & Types
- **Coding Style**:
  - Indent using **Tabs**, not spaces.
  - Brace placement: Classes and Methods use the next line (Allman style, e.g., `class Foo \n {`). Control structures use the same line with no space before the brace (e.g., `if(...){`).
- **Types**: Use `strict_types=1` in every file. Use **PHPDoc** heavily for arrays and generics (e.g. `/** @var array<int, User> */`) — no external static analysis tools (like PHPStan) are required, so PHPDoc is our source of truth.

## Core Conventions (Class Design & DI)
- Classes are `final` by default — opening one up is a deliberate decision, per this table:
  - **core classes that touch PHP globals/IO** (e.g. `Session` → `SessionInterface`): extract an interface; consumers type-hint the interface, bootstrap binds interface → implementation in the container
  - **repositories**: plain non-final classes so unit tests can mock them directly — no interface ceremony
  - **services**: stay `final` — they are the unit under test, never mocked
  - **`Config`**: stays `final` and is never mocked — tests construct a real `Config` pointed at fixture values
- Container: concrete classes are autowired by reflection; interfaces (and anything needing setup) are bound explicitly in the entry points — bindings live nowhere else

## MVC Conventions
- Thin controllers: receive Request → call Service → return Response — no business logic, never touch PDO directly
- Constructor injection only — no globals, no static state, no service locator (`$_ENV` may be read only inside `config/`)
- Controller methods follow REST naming: `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`
- Naming in `app/Models/`: entities are **singular** (`User`, `Role`) — one instance = one row; repositories are `<Entity>Repo`, also singular (`UserRepo`, `LoginAttemptRepo`) — only table names stay plural
- All routes are declared in `app/Routes/` — one file per zone (`web.php`, `backoffice.php`); this directory is the security audit surface: reading it must answer which endpoints are public / require login / are rate-limited
- Route handlers are `[Controller::class, 'method']` only — closures are forbidden
- Middleware binds at the route/group level | middleware that must run on every request (SecurityHeaders, Session, Csrf) is a global pipeline in bootstrap — never opt-in per route
- **API Response Format**: If returning JSON, standardize the wrapper: `{"success": true, "data": {...}}` or `{"success": false, "error": "Message"}`.
