<?php

namespace Database\Factories;

use App\Models\CollectionDefinition;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionDefinition>
 */
class CollectionDefinitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(1);

        return [
            'site_id' => Site::factory(),
            'key' => $key,
            'label' => ucfirst($key),
            'version' => 1,
            'schema' => [
                ['name' => 'role', 'type' => 'text'],
            ],
        ];
    }
}
