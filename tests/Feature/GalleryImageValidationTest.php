<?php

declare(strict_types=1);

use Caasidev\LaravelGallery\Models\Gallery;
use Caasidev\LaravelGallery\Models\GalleryImage;
use Caasidev\LaravelGallery\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser());
});

it('requires images to upload', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['images']);
});

it('rejects non image files', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [
        'images' => [UploadedFile::fake()->create('document.pdf', 100)],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['images.0']);
});

it('returns a 404 when deleting an image from the wrong gallery', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();
    $otherGallery = Gallery::factory()->create();

    $image = GalleryImage::factory()->for($otherGallery)->create();

    $this->deleteJson("/api/v1/galleries/{$gallery->id}/images/{$image->id}")
        ->assertNotFound();
});

it('orders uploaded images by sort order', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    GalleryImage::factory()->for($gallery)->create(['sort_order' => 10]);

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [
        'images' => [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg'),
        ],
    ])
        ->assertCreated();

    $orders = $gallery->fresh()->images->pluck('sort_order')->toArray();

    expect($orders)->toBe([10, 11, 12]);
});
