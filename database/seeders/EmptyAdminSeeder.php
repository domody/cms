<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmptyAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed only the platform admin (admin@cms.test, password "password"), so
     * the admin area's "no sites" empty states can be seen. Run it on a fresh
     * scratch database, never on one holding real data:
     * `php artisan migrate:fresh --seeder=EmptyAdminSeeder`.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@cms.test',
        ]);
    }
}
