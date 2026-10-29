<?php

namespace Database\Seeders;

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
        }
    }
}
