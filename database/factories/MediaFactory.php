<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2).'.jpg';

        return [
            'site_id' => Site::factory(),
            'disk' => config('filesystems.media_disk', 'public'),
            'path' => 'sites/0/'.$name,
            'original_name' => $name,
            'mime_type' => 'image/jpeg',
            'size' => $size = fake()->numberBetween(10_000, 500_000),
            'hash' => fake()->unique()->sha256(),
            'stored_bytes' => $size,
            'width' => 1200,
            'height' => 800,
            'alt' => fake()->sentence(3),
        ];
    }
}
