<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\SitePlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'plan' => SitePlan::STARTER,
        ];
    }

    /**
     * Put the site on the given plan (a SitePlan value).
     */
    public function plan(string $plan): static
    {
        return $this->state(fn (array $attributes) => ['plan' => $plan]);
    }
}
