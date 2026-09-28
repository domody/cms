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
        $acme = Tenant::factory()->create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $globex = Tenant::factory()->create([
            'name' => 'Globex Inc',
            'slug' => 'globex-inc',
        ]);

        User::factory()->create([
            'tenant_id' => $acme->id,
            'name' => 'Acme Admin',
            'email' => 'admin@acme-corp.test',
        ]);

        User::factory()->create([
            'tenant_id' => $globex->id,
            'name' => 'Globex Admin',
            'email' => 'admin@globex-inc.test',
        ]);
    }
}
