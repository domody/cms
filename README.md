# CMS Backend

Headless, multi-tenant CMS backend. Laravel + a fully custom Livewire admin panel + token-based API auth (Sanctum), serving content to separate React/Inertia frontend codebases. This is the **project skeleton only** — auth and tenancy are wired up; no admin UI, content models, or public API endpoints exist yet.

## Stack

- Laravel 13, PHP 8.4
- PostgreSQL (via Docker locally)
- [Livewire](https://livewire.laravel.com) — the admin panel is hand-built on Livewire, no admin framework on top
- [Laravel Sanctum](https://laravel.com/docs/sanctum) — API token auth for external frontends
- [Laravel Boost](https://laravel.com/docs/boost) — AI agent tooling (dev-only)

## Local setup

1. Start a Postgres container (already created for this project as `cms_backend_postgres`):

   ```bash
   docker start cms_backend_postgres
   # first time only, if the container doesn't exist yet:
   docker run -d --name cms_backend_postgres \
     -e POSTGRES_DB=cms_backend -e POSTGRES_USER=cms_backend -e POSTGRES_PASSWORD=secret \
     -p 5432:5432 -v cms_backend_pgdata:/var/lib/postgresql postgres:18
   ```

2. Copy `.env.example` to `.env` if you haven't (already done for this checkout) and adjust `DB_*` if your Postgres credentials differ. Defaults match the container above:

   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=cms_backend
   DB_USERNAME=cms_backend
   DB_PASSWORD=secret
   ```

3. Install dependencies, migrate, and seed demo data:

   ```bash
   composer install
   php artisan migrate
   php artisan db:seed
   ```

4. Serve the app:

   ```bash
   php artisan serve
   ```

## What's set up, and how to see it in action

### 1. Multi-tenancy (`Tenant` model + structural `tenant_id` scoping)

- `app/Models/Tenant.php` — a tenant (client company). Table `tenants` (`id`, `name`, `slug`).
- `app/Models/Concerns/BelongsToTenant.php` — trait for any tenant-owned model. It:
  - registers `App\Models\Scopes\TenantScope` as a **global scope**, so every query against the model is automatically filtered to `auth()->user()->tenant_id`. No query anywhere can accidentally skip this — it's not opt-in per query.
  - on `creating`, always sets `tenant_id` to the authenticated user's own tenant when one exists — mass-assigning a different `tenant_id` is silently overridden, not honored.
  - on `updating`, throws if something tries to change `tenant_id` to a different tenant than the authenticated user's.
- `app/Models/Scopes/TenantScope.php` — the global scope. **No authenticated user → fails closed.** Queries return zero rows, not "everything." This applies unconditionally, including to `User` itself — there's no model-level exception (console/seeders/jobs that need to read across tenants use `Model::withoutGlobalScope(TenantScope::class)` explicitly, same as any other tenant-owned model).
- `User` uses `BelongsToTenant` (a user belongs to one tenant, via `users.tenant_id`).

#### Login, password reset, and Sanctum still have to find a user pre-auth — without exempting `User` from the scope

Fail-closed-with-no-exceptions creates one real problem: login and password reset need to find a `User` row by email *before* any tenant is known, and Sanctum needs to find the owning `User` for a token the same way. A plain scoped query can't do that — it would fail closed on the very query that's supposed to establish who's logged in.

Instead of exempting the model, two narrow bypasses exist for exactly those lookups, and nothing else:

- `app/Auth/TenantUnscopedEloquentUserProvider.php` — the `users` auth provider (`config/auth.php`, driven by `AppServiceProvider::boot()`'s `Auth::provider(...)` registration). Identical to Laravel's default `EloquentUserProvider`, except it overrides the one method (`newModelQuery()`) that `retrieveById()`, `retrieveByCredentials()`, and `retrieveByToken()` all call, adding `->withoutGlobalScope(TenantScope::class)`. This covers login, password reset, and remember-me — and *only* those, since nothing else in the app goes through this provider.
- `app/Models/PersonalAccessToken.php` — registered via `Sanctum::usePersonalAccessTokenModel()`. Overrides `tokenable()` (the `morphTo` Sanctum uses to resolve "whose token is this") the same way, so token authentication can find its owner before the tenant is known.

Everything else — a route, a job, a stray `User::where(...)` run without an authenticated actor — gets no bypass and fails closed like any other tenant-owned model. `TenantScope::apply()` itself calls `auth()->user()` directly (not the non-resolving `auth()->hasUser()`): that's safe *because* of the bypasses above — resolving the guard's user, when not yet cached, queries `User` through the unscoped provider, which never re-enters this scope, so there's nothing left to recurse into. Before these bypasses existed, that same line was the exact cause of an infinite loop (`SessionGuard::user()` → `retrieveById()` → scope calls `auth()->user()` → `SessionGuard::user()` again, forever) — `tests/Feature/TenantScopingTest.php`'s session-recursion test is mutation-tested against this: reintroducing the old check-without-bypass makes it fail with a memory-exhaustion crash, confirming the test actually catches the bug it guards against.

`TenantScopingTest` also proves, end to end, that: an unauthenticated direct query against `User` fails closed; login still finds a user across tenants via the unscoped provider; a real session cookie through the full HTTP stack (not `actingAs()`, which sets the guard's user directly and would never catch a recursion regression) reaches an authenticated route; and a live Sanctum token can only ever see its own tenant's data, never another's.

**To use on a future model** (e.g. when you build a `Page` model later):

```php
class Page extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
}
```

That's it — `Page::all()`, `Page::where(...)`, etc. are now automatically tenant-scoped, and `Page::create([...])` auto-fills `tenant_id`.

**How to verify it:**

```bash
php artisan test --filter=TenantScopingTest
```

Or interactively:

```bash
php artisan tinker
>>> $template = \App\Models\Tenant::where('slug', 'template-site')->first();
>>> // $template->users would itself be fail-closed here: nobody is authenticated yet.
>>> $templateUser = \App\Models\User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('tenant_id', $template->id)->first();
>>> auth()->login($templateUser);
>>> \App\Models\User::count();            // 1 — only the Template Site's user, Globex's is invisible
```

### 2. Admin panel

Seeded demo users still exist for when the panel's login is built (password `password` for both, from the factory default):

| Tenant | Email | Slug |
|---|---|---|
| Template Site | `admin@template-site.test` | `template-site` |
| Globex Inc | `admin@globex-inc.test` | `globex-inc` |

### 3. API token auth (Sanctum)

- `laravel/sanctum` installed via `php artisan install:api`, which also created `routes/api.php` and registered `api:` routing in `bootstrap/app.php` (currently empty — no endpoints defined per scope).
- `User` uses `Laravel\Sanctum\HasApiTokens`.

**Example usage** (once real endpoints exist, a frontend site would):

```php
// Issue a token (e.g. from a login endpoint you'll build later)
$token = $user->createToken('react-frontend')->plainTextToken;
```

```
GET /api/whatever
Authorization: Bearer <token>
```

Any route using the `auth:sanctum` middleware will resolve `auth()->user()`, which means the `TenantScope` above applies automatically to API requests too — a token from the Template Site's user can never see Globex's data.

### 4. Laravel Boost (dev tooling)

Installed per this repo's original setup instructions (now superseded by `AGENTS.md`, which Boost generated with app-specific conventions). Dev-only dependency, not part of the runtime.

## TODO / Open decisions

Not yet settled, and deliberately not written into `CLAUDE.md` until they are:

- **Architecture style** — Controller-only (no Service/Action classes, no Form Requests, inline `processPost()`-style validation) vs idiomatic Laravel (Form Requests, Actions where they earn their keep). Decide once the first real controllers are built.
- **API contract** — route prefix (`/api` vs root-mounted) and JSON error shapes (validation/abort/500) for `routes/api.php`. Decide once the first public API endpoints are built.
- **Admin panel conventions** — Livewire component structure, layout, and auth/session wiring for the custom panel, once it's built.
- **Sanctum API auth notes** — how token auth interacts with tenancy scoping in practice, once real API endpoints exist (ties into the `PersonalAccessToken` bypass already documented above).
