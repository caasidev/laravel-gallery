<?php

declare(strict_types=1);

namespace Caasidev\LaravelGallery\Database\Factories;

use Caasidev\LaravelGallery\Models\Gallery;
use Caasidev\LaravelGallery\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;

    public function definition(): array
    {
        return [
            'gallery_id' => Gallery::factory(),
            'path' => 'galleries/'.$this->faker->uuid().'.jpg',
            'disk' => 'public',
            'alt_text' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
