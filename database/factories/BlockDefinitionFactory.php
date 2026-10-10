<?php

namespace Database\Factories;

use App\Models\BlockDefinition;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockDefinition>
 */
class BlockDefinitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(1);

        return [
            'tenant_id' => Tenant::factory(),
            'key' => $key,
            'label' => ucfirst($key),
            'version' => 1,
            'schema' => [
                ['name' => 'headline', 'type' => 'text', 'required' => true],
            ],
        ];
    }
}
