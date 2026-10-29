<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageBlock>
 */
class PageBlockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'site_id' => fn (array $attributes) => Page::withoutGlobalScopes()->find($attributes['page_id'])->site_id,
            'block_key' => 'hero',
            'position' => 0,
            'is_visible' => true,
            'content' => ['headline' => fake()->sentence(3)],
            'schema_version' => 1,
        ];
    }
}
