# CMS Backend

Headless, multi-site CMS. Laravel + a hand-built Livewire admin panel + Sanctum token auth for the frontends. Each client site is a separate frontend codebase (see `../frontend-template`, a Next.js example) that declares its own blocks and collections in code, syncs them to this CMS, and reads published content over the API. Clients log in here to edit that content.

This is the **alpha**: pages, blocks, collections and images, with the operator tooling to onboard a client by hand. Self-serve signup, a blog, navigation and global settings are not built (see [Not in the alpha](#not-in-the-alpha)).

## Stack

- Laravel 13, PHP 8.4
- PostgreSQL (Docker locally)
- [Livewire](https://livewire.laravel.com) 4, the admin panel is hand-built on it
- [Laravel Sanctum](https://laravel.com/docs/sanctum) for API tokens
- [Laravel Boost](https://laravel.com/docs/boost), dev-only AI tooling

## Local setup

1. Start Postgres (the container already exists for this project):

   ```bash
   docker start cms_backend_postgres
   # first time only, if it doesn't exist yet:
   docker run -d --name cms_backend_postgres \
     -e POSTGRES_DB=cms_backend -e POSTGRES_USER=cms_backend -e POSTGRES_PASSWORD=secret \
     -p 5432:5432 -v cms_backend_pgdata:/var/lib/postgresql postgres:18
   ```

2. Copy `.env.example` to `.env` and adjust `DB_*` if needed. Set **`APP_URL` to the address the CMS is really served on**: image URLs in the API are built from it, and the frontend must be able to load them.

3. Install, migrate, seed demo data, link public storage:

   ```bash
   composer install
   php artisan migrate
   php artisan db:seed
   php artisan storage:link
   ```

   The seeder prints an API token for the Template Site (read + sync). Put it in `../frontend-template/.env.local` as `CMS_TOKEN`.

4. Serve it (Herd serves it at `http://cms-backend.test`, or `php artisan serve`).

Seeded logins (password `password` for all):

| Email | Who |
|---|---|
| `admin@cms.test` | Platform admin: the operator area and every site |
| `web@cms.test` | Customer on three sites (owner / admin / editor) |
| `admin@template-site.test` | Customer, owner of Template Site, editor on Globex |
| `admin@globex-inc.test` | Customer, owner of Globex Inc |

## Concepts

- **Sites.** A site is a client's website. Everything a client edits (pages, blocks, collections, images) belongs to one site and is scoped to it automatically; code that reads across sites has to say so explicitly. The site's slug is the first URL segment of its admin: `/{slug}/dashboard`.
- **Users and access levels.** Users are global. An *admin* is the operator: the whole `/admin` area and every site. A *customer* only reaches the sites they belong to and can only edit content. What each level may do is in `config/acl.php`; anything not listed there is a 404. Per-site roles (owner / admin / editor) are stored but **not enforced yet**: every member can edit everything on their site.
- **Pages and blocks.** The frontend declares block types (with typed fields) and pages in its manifest. Editors reorder, hide and edit blocks; they cannot create or delete pages.
- **Collections.** Repeatable content (team members, testimonials) declared in the manifest. Editors manage them under **Collections** (a grid of all the site's collections; open one to add, edit, reorder and delete entries, draft/published, slug generated from the title). Collections have no page of their own: a block shows one through a `collection` field. The editor picks which collection on the page, from every collection on the site, and the API fills the field with that collection's published entries (the frontend decides only how they look).
- **Media.** A per-site image library (jpg, png, webp, gif, up to 5 MB, alt text required). SVG is not accepted. An `image` field stores the media id and the API returns `{url, alt, width, height}`.
- **API tokens.** Owned by a site, never by a user, and created only by an admin on the site's page. A `read` token serves content (give this to production); a `read + sync` token can also push the manifest (CI / dev). Shown once when created, revocable.
- **Revalidation.** If a site has a revalidate URL and secret, saving a page or collection entry (or changing an image) POSTs the affected cache tags to the frontend so edits appear straight away. Failures are logged and never block the save.

## Onboarding a client

As the platform admin, in the CMS:

1. **Admin > Sites > Add site.** Name it; the slug (which can't be changed later) is generated or typed.
2. **Admin > Users > Add user** for each person who will edit. Choose the site and role; leave the password blank to generate one and pass it on (it is shown once). Customers can change it themselves under Account.
3. On the site's page: **API tokens > Create token.** Make a *Read + sync* token for dev/CI and a *Read only* token for production. Copy each: it is only shown once.
4. On the same page, set the **Revalidate URL** (`https://<their-site>/api/revalidate`) and **Revalidate secret** once the frontend is deployed.

In the frontend project:

5. Set `CMS_URL`, `CMS_TOKEN` and `REVALIDATE_SECRET` (see its `.env.example`).
6. `npm run cms:sync` pushes the manifest. The CMS now shows the blocks, pages and collections, ready to edit.

The first admin on a fresh install is created with the command below.

## API (for the frontends)

All under `/api`, JSON only, `Authorization: Bearer <token>`, throttled to 120 requests a minute per token.

| Method and path | Ability | Returns |
|---|---|---|
| `GET /api/pages/{slug}` | read | `{data: {slug, title, published_at, blocks: [{type, content}]}}`. Published pages only; visible blocks only |
| `GET /api/collections/{key}` | read | `{data: [entry, ...]}` of published entries in CMS order |
| `GET /api/collections/{key}/{slug}` | read | `{data: entry}` |
| `PUT /api/block-definitions` | sync | Pushes `{blocks, collections?, pages?}`. Additive: never deletes or overwrites edited content |

An entry is `{collection, slug, title, ...its fields}`. Block fields: `text`, `textarea`, `richtext`, `image`, `link`, `boolean`, `select`, `repeater` (a list of rows; declare the row's simple fields under `fields`, e.g. `{name: 'itinerary', type: 'repeater', fields: [{name: 'title', type: 'text'}, {name: 'body', type: 'textarea'}]}`; it comes back as an array of objects, image sub-fields resolved), and (blocks only) `collection`. Revalidate tags sent to the frontend are `page:{slug}` and `collection:{key}`.

## Scheduled and manual commands

- Scheduled commands
    + None yet. The app has no scheduled commands, so no cron entry is needed, and queues are not used.
    + If one is added later, the server needs the standard Laravel entry: **\* \* \* \* \* php artisan schedule:run**

- Create Admin (cms:create-admin)
    + Creates an admin user. The way to make the first admin on a new install
    + Not scheduled; run manually
    + Prompts for anything not passed. In non-interactive mode with no password, one is generated and printed
    + Command: **php artisan cms:create-admin --name="Name" --email=you@example.com**

## Verifying

```bash
php artisan test
php artisan test --filter=SiteScopingTest   # tenancy, auth, ACL, tokens, media, collections
```

`SiteScopingTest` must pass after any change to site scoping, access levels or tokens.

## Deployment: needs confirmed

Hosting is not decided. These need confirming before the first deploy:

- Hosting target (Laravel Cloud, VPS, ...) and the PHP/Postgres versions it provides: needs confirmed
- Session and cache drivers: the project convention is the file driver for both, but `.env.example` currently says `database`, and `QUEUE_CONNECTION` is also `database` although queues are not used: needs confirmed
- Scheduler / cron: nothing to run today (see above): needs confirmed once a scheduled command exists
- Media disk (`MEDIA_DISK`): `public` locally. Production needs a publicly readable disk that survives deploys (on Laravel Cloud, its object storage), and `APP_URL` correct so image URLs resolve: needs confirmed
- Database hosting and backups (Postgres), and how often: needs confirmed
- HTTPS and trusted proxies, so URLs and secure cookies are right behind the host's load balancer: needs confirmed
- Mail: none is sent today (admins set passwords by hand): needs confirmed if forgot-password is ever added

## Not in the alpha

Page create/delete in the CMS, scheduled publishing and revisions, a blog, navigation, global site settings, SEO/OG images, hand-picking individual collection entries, relations between collections, enforcing per-site roles, forgot-password email, image resizing, SVG uploads, and customers managing their own site's users.
