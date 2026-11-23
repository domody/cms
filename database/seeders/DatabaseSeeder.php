<?php

namespace Database\Seeders;

use App\Models\BlockDefinition;
use App\Models\CollectionDefinition;
use App\Models\MediaFolder;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Site;
use App\Models\SitePlan;
use App\Models\SiteRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates two sites. Each has its own admin, and the Template admin is
     * also an editor on Globex, so site switching and site scoping can be
     * verified locally out of the box. Also seeds a platform admin
     * (admin@cms.test) and a customer (web@cms.test) with a different site
     * role on each of three sites, plus owner of the Template Site with an API
     * token for its frontend. Sites are spread across the plans.
     * Every password is "password".
     */
    public function run(): void
    {
        $template = Site::factory()->plan(SitePlan::BESPOKE)->create([
            'name' => 'Template Site',
            'slug' => 'template-site',
        ]);

        $globex = Site::factory()->plan(SitePlan::STANDARD)->create([
            'name' => 'Globex Inc',
            'slug' => 'globex-inc',
        ]);

        User::factory()
            ->memberOf($template, SiteRole::OWNER)
            ->create([
                'name' => 'Template Admin',
                'email' => 'admin@template-site.test',
            ])
            ->sites()
            ->attach($globex->id, ['role' => SiteRole::EDITOR]);

        User::factory()
            ->memberOf($globex, SiteRole::OWNER)
            ->create([
                'name' => 'Globex Admin',
                'email' => 'admin@globex-inc.test',
            ]);

        // Platform admin: no site memberships, can enter every site.
        User::factory()->admin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@cms.test',
        ]);

        // Customer with a different site role on each of their three sites.
        $customer = User::factory()->create([
            'name' => 'Web Customer',
            'email' => 'web@cms.test',
        ]);

        // Also owner of the Template Site.
        $customer->sites()->attach($template->id, ['role' => SiteRole::OWNER]);

        // The API token its frontend uses (CMS_TOKEN in
        // frontend-template/.env.local), owned by the site. The plain text is
        // only available now, so it is printed.
        $this->command?->info(
            'Template Site token: '.$template->issueToken('frontend-template', ['read', 'sync'])->plainTextToken
        );

        foreach ([
            ['Acme Bakery', 'acme-bakery', SiteRole::OWNER, SitePlan::STARTER],
            ['Birch Studio', 'birch-studio', SiteRole::ADMIN, SitePlan::STANDARD],
            ['Cedar Dental', 'cedar-dental', SiteRole::EDITOR, SitePlan::GROWTH],
        ] as [$name, $slug, $role, $plan]) {
            $site = Site::factory()->plan($plan)->create(['name' => $name, 'slug' => $slug]);

            $customer->sites()->attach($site->id, ['role' => $role]);

            match ($slug) {
                'birch-studio' => $this->seedBirchStudio($site),
                'cedar-dental' => $this->seedCedarDental($site),
                default => null, // Acme Bakery stays a brand-new, empty site.
            };
        }

        // A site with no members and no token (admin views of site.show and
        // users.index), and a customer who belongs to no site.
        Site::factory()->plan(SitePlan::STARTER)->create(['name' => 'Dunmore Garage', 'slug' => 'dunmore-garage']);

        User::factory()->create(['name' => 'Orphan Customer', 'email' => 'orphan@cms.test']);
    }

    /**
     * Birch Studio (customer is an admin): a media library holding only an
     * empty folder, so media.index shows "No images here yet".
     */
    private function seedBirchStudio(Site $site): void
    {
        MediaFolder::create(['site_id' => $site->id, 'name' => 'Photoshoots']);
    }

    /**
     * Cedar Dental (customer is an editor): pages and collections that exist
     * but hold nothing, for the empty states inside them. No media is seeded,
     * so the image picker's library is empty too.
     */
    private function seedCedarDental(Site $site): void
    {
        BlockDefinition::factory()->create([
            'site_id' => $site->id,
            'key' => 'hero',
            'label' => 'Hero',
            'schema' => [
                ['name' => 'headline', 'type' => 'text', 'required' => true],
                ['name' => 'image', 'type' => 'image'],
            ],
        ]);

        BlockDefinition::factory()->create([
            'site_id' => $site->id,
            'key' => 'gallery',
            'label' => 'Gallery',
            'schema' => [
                ['name' => 'photos', 'type' => 'repeater', 'label' => 'Photos', 'fields' => [['name' => 'photo', 'type' => 'image']]],
            ],
        ]);

        $home = Page::factory()->published()->create(['site_id' => $site->id, 'slug' => 'home', 'title' => 'Home']);
        PageBlock::factory()->create([
            'site_id' => $site->id,
            'page_id' => $home->id,
            'content' => ['headline' => 'Gentle care for the whole family'],
        ]);

        // A page with no blocks: pages.show / the page editor's block list.
        Page::factory()->create(['site_id' => $site->id, 'slug' => 'about', 'title' => 'About']);

        // A block whose repeater has no rows: the repeater's empty state.
        $gallery = Page::factory()->create(['site_id' => $site->id, 'slug' => 'gallery', 'title' => 'Gallery']);
        PageBlock::factory()->create([
            'site_id' => $site->id,
            'page_id' => $gallery->id,
            'block_key' => 'gallery',
            'content' => ['photos' => []],
        ]);

        // A collection with no entries: collections.show's empty state.
        CollectionDefinition::factory()->create([
            'site_id' => $site->id,
            'key' => 'team',
            'label' => 'Team',
            'schema' => [
                ['name' => 'role', 'type' => 'text'],
                ['name' => 'photo', 'type' => 'image'],
            ],
        ]);
    }
}
