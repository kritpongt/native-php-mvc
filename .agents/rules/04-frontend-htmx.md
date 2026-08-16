---
description: Frontend conventions including Twig, HTMX, and Tailwind CSS
trigger: model_decision
---

# Frontend Conventions (HTMX, Twig, Tailwind)

## Stack Rules
- Allowed libraries: **Twig** (view), **HTMX** (frontend), **Tailwind CSS** (styling)
- HTMX must be self-hosted in `public/assets/` (no CDN, to keep CSP strict)
- Tailwind via **Node build pipeline** (npm) — dev-time only, never a runtime dependency: compile `resources/css/tailwind.css` → `public/assets/tailwind.css` as a static file and commit it (production needs no node)
- Node supply chain: commit `package-lock.json` and install with `npm ci`, git-ignore `node_modules/`; adding npm packages/Tailwind plugins requires discussion first

## HTMX & Twig Conventions
- Controllers check the `HX-Request` header: if present, render the partial; otherwise render the full page — every page must work without JS (progressive enhancement)
- Markup is written once in `app/Views/partials/` and full pages `{% include %}` it
- Twig templates are named `snake_case.html.twig` | pages mirror controllers: `UserController::index` → `app/Views/pages/users/index.html.twig`
