<?php

namespace Database\Factories;

use App\Models\CollectionEntry;
use App\Models\PageStatus;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionEntry>
 */
class CollectionEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'site_id' => Site::factory(),
            'collection_key' => 'team',
            'slug' => str($title)->slug()->toString(),
            'title' => ucfirst($title),
            'status' => PageStatus::DRAFT,
            'position' => 0,
            'data' => [],
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => PageStatus::PUBLISHED]);
    }
}
