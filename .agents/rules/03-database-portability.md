---
description: Database conventions, migrations, SQL rules
trigger: model_decision
---

# Database Portability (MySQL ↔ PostgreSQL)

- Database: **switchable between MySQL/MariaDB and PostgreSQL** via per-project config
- Identifiers (tables/columns) are lowercase `snake_case` only — so nothing ever needs quoting (backticks vs double quotes differ between drivers)
- Plural table names (`users`, `login_attempts`), FKs named `<singular>_id`
- Repository SQL must be ANSI SQL that runs on both drivers — no driver-specific syntax (e.g. `ON DUPLICATE KEY UPDATE`, `RETURNING`)
- Where a difference is unavoidable, wrap it in a single method on `Core\Database` (e.g. `lastInsertId()`) — repositories must never know which driver is in use
- Migrations are PHP classes with `up()`/`down()` — where DDL differs (AUTO_INCREMENT vs IDENTITY), branch on `$db->driver()` inside the migration
- Every table has `created_at` and `updated_at`, set by the app — no DB triggers/defaults, which differ between drivers
