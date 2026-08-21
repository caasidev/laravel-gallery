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

it('returns galleries', function () {
    /** @var TestCase $this */
    Gallery::factory()->count(2)->create();
    Gallery::factory()->create(['user_id' => 2]);

    $this->getJson('/api/v1/galleries')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'image_url',
                ],
            ],
        ]);
});

it('creates a gallery', function () {
    /** @var TestCase $this */
    $this->postJson('/api/v1/galleries', [
        'name' => 'Summer Photos',
        'description' => 'A simple gallery',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Summer Photos')
        ->assertJsonPath('data.slug', 'summer-photos');

    expect(Gallery::count())->toBe(1)
        ->and(Gallery::firstOrFail()->user_id)->toBe(1);
});

it('deletes a gallery', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $this->deleteJson("/api/v1/galleries/{$gallery->id}")
        ->assertNoContent();

    expect(Gallery::find($gallery->id))->toBeNull();
});

it('shows a gallery', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create([
        'name' => 'Road Trip',
        'slug' => 'road-trip',
        'description' => 'Photos from the road',
    ]);

    $this->getJson("/api/v1/galleries/{$gallery->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $gallery->id)
        ->assertJsonPath('data.name', 'Road Trip')
        ->assertJsonPath('data.slug', 'road-trip')
        ->assertJsonPath('data.description', 'Photos from the road');
});

it('does not show another users gallery', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create(['user_id' => 2]);

    $this->getJson("/api/v1/galleries/{$gallery->id}")
        ->assertNotFound();
});

it('updates a gallery', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create([
        'name' => 'Old Name',
        'slug' => 'old-name',
        'description' => 'Old description',
    ]);

    $this->putJson("/api/v1/galleries/{$gallery->id}", [
        'name' => 'Updated Gallery',
        'description' => 'Updated description',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $gallery->id)
        ->assertJsonPath('data.name', 'Updated Gallery')
        ->assertJsonPath('data.slug', 'updated-gallery')
        ->assertJsonPath('data.description', 'Updated description');

    $gallery->refresh();

    expect($gallery->name)->toBe('Updated Gallery')
        ->and($gallery->slug)->toBe('updated-gallery')
        ->and($gallery->description)->toBe('Updated description');
});

it('creates a gallery with an uploaded image', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $image = UploadedFile::fake()->image('cover.jpg');

    $response = $this->post('/api/v1/galleries', [
        'name' => 'Summer Photos',
        'description' => 'A simple gallery',
        'image' => $image,
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Summer Photos')
        ->assertJsonPath('data.slug', 'summer-photos');

    $gallery = Gallery::query()->firstOrFail();

    expect($gallery->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($gallery->image_path);
    $response->assertJsonPath('data.image_url', Storage::disk('public')->url($gallery->image_path));
});

it('updates a gallery with an uploaded image', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    Storage::disk('public')->put('galleries/existing.jpg', 'existing');

    $gallery = Gallery::factory()->create([
        'name' => 'Old Name',
        'slug' => 'old-name',
        'description' => 'Old description',
        'image_path' => 'galleries/existing.jpg',
    ]);

    $newImage = UploadedFile::fake()->image('updated.jpg');

    $response = $this->put("/api/v1/galleries/{$gallery->id}", [
        'name' => 'Updated Gallery',
        'description' => 'Updated description',
        'image' => $newImage,
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $gallery->id)
        ->assertJsonPath('data.name', 'Updated Gallery')
        ->assertJsonPath('data.slug', 'updated-gallery')
        ->assertJsonPath('data.description', 'Updated description');

    $gallery->refresh();

    expect($gallery->image_path)->not->toBe('galleries/existing.jpg');
    Storage::disk('public')->assertMissing('galleries/existing.jpg');
    Storage::disk('public')->assertExists($gallery->image_path);
    $response->assertJsonPath('data.image_url', Storage::disk('public')->url($gallery->image_path));
});

it('uploads multiple images to a gallery', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $files = [
        UploadedFile::fake()->image('one.jpg'),
        UploadedFile::fake()->image('two.jpg'),
    ];

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [
        'images' => $files,
    ])
        ->assertCreated()
        ->assertJsonCount(2, 'data');

    expect($gallery->fresh()->images)->toHaveCount(2);

    foreach (GalleryImage::all() as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

it('shows gallery images on the gallery response', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $gallery->images()->create([
        'path' => 'galleries/test-image.jpg',
        'disk' => 'public',
        'sort_order' => 1,
    ]);

    $this->getJson("/api/v1/galleries/{$gallery->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.images')
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'description',
                'images' => [
                    '*' => [
                        'id',
                        'path',
                        'url',
                        'alt_text',
                        'sort_order',
                    ],
                ],
            ],
        ]);
});

it('deletes a gallery image', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $file = UploadedFile::fake()->image('one.jpg');
    $path = $file->store('galleries', 'public');

    assert(is_string($path));

    /** @var GalleryImage $image */
    $image = $gallery->images()->create([
        'path' => $path,
        'disk' => 'public',
        'sort_order' => 1,
    ]);

    $this->deleteJson("/api/v1/galleries/{$gallery->id}/images/{$image->id}")
        ->assertNoContent();

    expect(GalleryImage::find($image->id))->toBeNull();

    Storage::disk('public')->assertMissing($path);
});

it('deletes an image file from disk when an image is deleted', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $path = UploadedFile::fake()
        ->image('photo-one.jpg')
        ->store('galleries', 'public');

    assert(is_string($path));

    /** @var GalleryImage $image */
    $image = $gallery->images()->create([
        'path' => $path,
        'disk' => 'public',
        'sort_order' => 1,
    ]);

    Storage::disk('public')->assertExists($path);

    $this->deleteJson("/api/v1/galleries/{$gallery->id}/images/{$image->id}")
        ->assertNoContent();

    expect(GalleryImage::find($image->id))->toBeNull();

    Storage::disk('public')->assertMissing($path);
});

it('deletes all image files from disk when a gallery is deleted', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $coverImage = UploadedFile::fake()->image('cover.jpg');
    $coverPath = $coverImage->store('galleries', 'public');
    assert(is_string($coverPath));

    $gallery = Gallery::factory()->create(['image_path' => $coverPath]);

    $firstPath = UploadedFile::fake()
        ->image('photo-one.jpg')
        ->store('galleries', 'public');

    $secondPath = UploadedFile::fake()
        ->image('photo-two.jpg')
        ->store('galleries', 'public');

    assert(is_string($firstPath) && is_string($secondPath));

    $gallery->images()->createMany([
        [
            'path' => $firstPath,
            'disk' => 'public',
            'sort_order' => 1,
        ],
        [
            'path' => $secondPath,
            'disk' => 'public',
            'sort_order' => 2,
        ],
    ]);

    Storage::disk('public')->assertExists($coverPath);
    Storage::disk('public')->assertExists($firstPath);
    Storage::disk('public')->assertExists($secondPath);

    $this->deleteJson("/api/v1/galleries/{$gallery->id}")
        ->assertNoContent();

    expect(Gallery::find($gallery->id))->toBeNull()
        ->and(GalleryImage::count())->toBe(0);

    Storage::disk('public')->assertMissing($coverPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertMissing($secondPath);
});
