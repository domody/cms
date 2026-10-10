<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates two tenants with one user each, so tenant switching and
     * tenant scoping can be verified locally out of the box.
     */
    public function run(): void
    {
        $template = Tenant::factory()->create([
            'name' => 'Template Site',
            'slug' => 'template-site',
        ]);

        $globex = Tenant::factory()->create([
            'name' => 'Globex Inc',
            'slug' => 'globex-inc',
        ]);

        User::factory()->create([
            'tenant_id' => $template->id,
            'name' => 'Template Admin',
            'email' => 'admin@template-site.test',
        ]);

        User::factory()->create([
            'tenant_id' => $globex->id,
            'name' => 'Globex Admin',
            'email' => 'admin@globex-inc.test',
        ]);
    }
}
