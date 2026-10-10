@AGENTS.md

# CMS Backend

## Hard Constraints

**Git:**
- Do not run, suggest, or simulate git commands.
- All code changes via file diffs only — human commits.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 13, PHP 8.4 |
| Database | PostgreSQL (Docker locally) |
| Admin | Custom Livewire panel |
| API Auth | Laravel Sanctum (token-based) |
| Dev tooling | Laravel Boost (MCP, dev-only) |

Headless CMS serving content to separate frontend codebases via the Sanctum-authenticated API. Full walkthrough of what's built and how to verify it: `README.md`.

## Multi-tenancy (fail-closed)

Read before touching auth, `User`, or adding models.

- Every tenant-owned model uses `App\Models\Concerns\BelongsToTenant`, which registers `TenantScope` as a global scope.
- **No authenticated user → zero rows, not everything.** Applies unconditionally, including to `User` — no model-level exception.

**Pre-auth bypasses** (the only two — don't add more):

| File | Purpose |
|---|---|
| `app/Auth/TenantUnscopedEloquentUserProvider.php` | `users` auth provider — login, password reset, remember-me |
| `app/Models/PersonalAccessToken.php` | Sanctum's `tokenable()` resolution |

If a new pre-auth lookup is ever genuinely needed, extend `tests/Feature/TenantScopingTest.php` first — it's mutation-tested against this exact class of regression (session-recursion bug details in README's tenancy section).

**Adding a tenant-owned model:**

```php
class Page extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
}
```

Don't hand-write `tenant_id` scoping — the trait handles creating/updating enforcement and query scoping.

**Verify:** `php artisan test --filter=TenantScopingTest`

## Testing

- No default testing — don't write tests unless explicitly asked.
- Exception: any change to the tenancy/auth bypass logic above must extend and pass `TenantScopingTest`. That's a correctness requirement, not optional coverage.

## Migrations

New migrations: `YYYY_MM_DD_NNNNNN_<entity-or-feature>.php`, not Laravel's default `create_x_table` naming. Group related tables under one migration when they belong to the same entity/feature.

Existing migrations (`0001_01_01_...`) predate this convention — leave them as-is.

```php
public function up(): void
{
    Schema::create('pages', fn (Blueprint $table) => /* ... */ null);
    Schema::create('page_revisions', fn (Blueprint $table) => /* ... */ null);
}
```

## Blade Layouts

- Auth pages (login, password reset, etc.) use `@extends('layouts.auth')`.
- Every other page uses `@extends('layouts.admin')`.

## View-specific JS

- JS for a given view lives in `public/js/<path.filename>.js`, mirroring the view's dotted path (e.g. `resources/views/layouts/admin.blade.php` → `public/js/layout.admin.js`).
- Reference it at the bottom of the view in a `@push('scripts')` block.

## Local Dev

Postgres needs to be running manually:

```bash
docker start cms_backend_postgres   # if it isn't already running
```
