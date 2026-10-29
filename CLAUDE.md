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


# Backend 

## Business Logic (Controllers & Models)

- **Logic Location:**
    - **Controllers** are the primary home for business logic.
    - **Do not** use Action classes or Service classes.
    - **Reusable Logic:** If logic must be reused across different parts of the application, encapsulate it as a **public method in the Model**.

- **Controller Structure:**
    - Keep Controllers focused. Do not overload a single controller with unrelated actions.
    - **Standard Public Methods:**
        - `index` - DataTables-driven listing endpoint (when needed). Use the `DataTables` helper.
        - `list` - Simple non-paginated listing endpoint for dropdowns/constant models (no DataTables).
        - `show` - Retrieves and returns a single resource by its ID.
        - `store` - Handle creation (delegates to `processPost`).
        - `update` - Handle updates (delegates to `processPost`).
        - `destroy` - Handle deletion.
    - **Standard Private Methods:**
        - `processPost(Request $request, ?Model $model = null)` - Centralizes `store/update` validation + persistence when applicable.
        - `prepareRequest()` - (Optional) Modifies/sanitizes request data before validation (e.g., formatting dates, toggling checkboxes).

## Validation

- **No Form Requests:** Do not create separate Form Request classes (e.g., `StoreUserRequest`).
- **Inline Validation:** Use `$request->validate([...])` inside controllers.
    - For `store/update`, prefer delegating to `processPost()`.
    - For other endpoints (e.g., `changePassword`, `findByUsername`), validate directly in the public method.

## General Workflow

- **Queues:** We don't use queues. For background processing, create Artisan commands and schedule them via cron on the server.
- **Sessions:** Use the file session driver.
- **Cache:** Use the file cache driver.

## Routing

- **Style:** Use `Route::prefix(...)->controller(...)->group(...)` route blocks.
- **HTTP verbs:** Updates and custom actions may use `POST` (see existing routes).
- **Binding:** Prefer implicit route model binding for `{model}` params when convenient.

**Example:**

```php
Route::prefix('users')->controller(UserController::class)->group(function() {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::post('/{user}', 'update');
    Route::delete('/{user}', 'destroy');
});
```


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

## Access levels (ACL, fail-closed)

Read before adding a route or controller.

- Every user has a site-wide `users.access_level` (`App\Models\AccessLevel`): **admin** (the CMS operator: whole-CMS area, any site) or **customer** (only the sites they belong to). Default is `customer`; the column is not mass-assignable.
- `config/acl.php` maps access levels → roles → controller actions (`[Controller::class, 'method']`). `App\Http\Middleware\EnforceAcl` wraps every authenticated route and returns **404** for any action no role of the user's level lists. Closure and `Route::view` routes have no controller, so they are always denied: every authenticated route needs a controller.
- **Adding a page/route:** add the controller action to a role in `config/acl.php` (and to the right level), or it is a 404 for everyone. `User::canAccess($controller, $method)` is the check; `MenuComposer` uses it so users only see links they can reach.
- Whole-CMS routes live under `/admin/...` (controllers in `App\Http\Controllers\Admin\`, registered before the `{site}` group). Site routes stay `/{site-slug}/...` (controllers in `App\Http\Controllers\Site\`). `admin` is a reserved slug.
- Access level (which areas) is separate from `SiteRole` (what you are within one site). SiteRole is stored but not yet enforced.

## Constant classes (BackEnd enums)
- No Eloquent models for business entities.
- Use small constant classes mirroring BackEnd enums and provide helpers like `getClass()` for badge styling.

```php
class UserAccessLevel
{
    /**
     * Administrator (BackEnd constant).
     *
     * @var int
     */
    const ADMINISTRATOR = 1;

    /**
     * Officer (BackEnd constant).
     *
     * @var int
     */
    const OFFICER = 2;

    /** 
     * Get badge class for access level.
     */
    public static function getClass(int $id): string
    {
        return match($id) {
            self::ADMINISTRATOR => 'bg-primary',
            self::OFFICER => 'bg-info',
            default => 'bg-secondary'
        };
    }
}
```

# Frontend

## Blade Layouts

- Auth pages (login, password reset, etc.) use `@extends('layouts.auth')`.
- Every other page uses `@extends('layouts.admin')` with params and sections:

```blade
@extends('layouts.admin', [
    'title' => 'Items',
    'pageTitle' => 'Items',
    'pageDescription' => 'Manage items',
])

@section('pageActions')
<a href="{{ route('items.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Add Item
</a>
@endsection

@section('content')
<div class="row row-deck row-cards">
    <div class="col-12">
        <div class="card">...</div>
    </div>
</div>
@endsection

@push('scripts')
<script>$(document).ready(function() { ... });</script>
@endpush
```


## Sites (fail-closed)

Read before touching auth, `User`, routing, or adding models.

- A **site** is a client's website. Users are global and belong to sites many-to-many via the `site_user` pivot, with a per-site `role` (`App\Models\SiteRole`). `User` is **not** site-scoped.
- The site area always runs "inside" a site: `/{site-slug}/dashboard`, `/{site-slug}/pages`, … `App\Http\Middleware\SetCurrentSite` checks the user can enter the site via `User::accessibleSites()` (members only for customers; every site for admins; else 404) and sets `App\Models\Scopes\CurrentSite`. `/` sends admins to `/admin/dashboard` and customers to their first site.
- API tokens are owned by a site, not a user (issued with `Site::issueToken()`, only from the admin site page; `personal_access_tokens.site_id` mirrors the owner so deleting a site removes them). They carry abilities: `read` serves content, `sync` also allows `PUT /api/block-definitions`. `SetCurrentSiteFromToken` sets the current site from the token; a browser session or any non-site token gets 403. Users have no tokens.
- Every site-owned model uses `App\Models\Concerns\BelongsToSite`, which registers `SiteScope` as a global scope reading `CurrentSite`.
- **No current site → `NoCurrentSiteException`, not everything and not a silent empty set.** Console/jobs/seeders that genuinely need cross-site reads must say so with `withoutGlobalScope(SiteScope::class)` or set `CurrentSite`. `SiteScope` never touches `auth()`, so there are no pre-auth bypasses to maintain.
- Site slugs are the first URL segment; `Site::RESERVED_SLUGS` blocks ones that would shadow a top-level route.

**Adding a site-owned model:**

```php
class Page extends Model
{
    use \App\Models\Concerns\BelongsToSite;
}
```

Don't hand-write `site_id` scoping — the trait handles creating/updating enforcement and query scoping.

**Verify:** `php artisan test --filter=SiteScopingTest`

# Testing & Other

## Testing

- No default testing — don't write tests unless explicitly asked.
- Exception: any change to the tenancy/auth/ACL logic above (site scoping, access levels, the admin site bypass) must extend and pass `SiteScopingTest`. That's a correctness requirement, not optional coverage.

## View-specific JS

- JS for a given view lives in `public/js/<path.filename>.js`, mirroring the view's dotted path (e.g. `resources/views/layouts/admin.blade.php` → `public/js/layout.admin.js`).
- Reference it at the bottom of the view in a `@push('scripts')` block.

## Linking table rows

- Make a whole row open a page with `<tr data-href="{{ route('...') }}" class="cursor-pointer hover:bg-muted">`.
- The click handler lives in the view's own JS file (e.g. `public/js/pages.index.js`): it listens on `tr[data-href]` and navigates to `row.dataset.href`. Copy it for a new table.
- Clicks on an `<a>` inside the row are skipped, so keep a normal link on the name cell for keyboard and middle-click use.

## Local Dev

Postgres needs to be running manually:

```bash
docker start cms_backend_postgres   # if it isn't already running
```
