<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageStatus;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'site_id' => Site::factory(),
            'slug' => str($title)->slug()->toString(),
            'title' => ucfirst($title),
            'status' => PageStatus::DRAFT,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }
}
