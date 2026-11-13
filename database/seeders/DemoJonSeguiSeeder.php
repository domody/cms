<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\SitePlan;
use App\Models\SiteRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoJonSeguiSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the Jon Segui site, its owner (jon@cms.test, password "password")
     * and an API token for its frontend. Run on its own:
     * `php artisan db:seed --class=DemoJonSeguiSeeder`.
     *
     * Safe to re-run: the site and user are only created if missing, but each
     * run issues (and prints) a new token. Block and collection definitions
     * and the declared pages come from the frontend's `npm run cms:sync`, not
     * from here.
     */
    public function run(): void
    {
        $site = Site::firstWhere('slug', 'jon-segui')
            ?? Site::factory()->plan(SitePlan::GROWTH)->create(['name' => 'Jon Segui', 'slug' => 'jon-segui']);

        $user = User::firstWhere('email', 'jon@cms.test')
            ?? User::factory()->create(['name' => 'Jon Segui', 'email' => 'jon@cms.test']);

        $user->sites()->syncWithoutDetaching([$site->id => ['role' => SiteRole::OWNER]]);

        // The plain text is only available now, so it is printed: it is the
        // CMS_TOKEN in the frontend's .env.local.
        $this->command?->info(
            'Jon Segui token: '.$site->issueToken('frontend', ['read', 'sync'])->plainTextToken
        );
    }
}
