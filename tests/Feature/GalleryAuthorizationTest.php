<?php

declare(strict_types=1);

use Caasidev\LaravelGallery\Models\Gallery;
use Caasidev\LaravelGallery\Models\GalleryImage;
use Caasidev\LaravelGallery\Tests\Fixtures\DenyAllGalleryImagePolicy;
use Caasidev\LaravelGallery\Tests\Fixtures\DenyAllGalleryPolicy;
use Caasidev\LaravelGallery\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser());
    Gate::policy(Gallery::class, DenyAllGalleryPolicy::class);
    Gate::policy(GalleryImage::class, DenyAllGalleryImagePolicy::class);
});

it('denies listing galleries when the viewAny gate fails', function () {
    /** @var TestCase $this */
    $this->getJson('/api/v1/galleries')
        ->assertForbidden();
});

it('denies showing a gallery when the view gate fails', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $this->getJson("/api/v1/galleries/{$gallery->id}")
        ->assertForbidden();
});

it('denies creating a gallery when the create gate fails', function () {
    /** @var TestCase $this */
    $this->postJson('/api/v1/galleries', [
        'name' => 'Summer Photos',
    ])
        ->assertForbidden();
});

it('denies updating a gallery when the update gate fails', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $this->putJson("/api/v1/galleries/{$gallery->id}", [
        'name' => 'Updated Gallery',
    ])
        ->assertForbidden();
});

it('denies deleting a gallery when the delete gate fails', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $this->deleteJson("/api/v1/galleries/{$gallery->id}")
        ->assertForbidden();
});

it('denies uploading images when the create gate fails', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [
        'images' => [UploadedFile::fake()->image('one.jpg')],
    ])
        ->assertForbidden();
});

it('denies deleting an image when the delete gate fails', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    /** @var GalleryImage $image */
    $image = GalleryImage::factory()->for($gallery)->create();

    $this->deleteJson("/api/v1/galleries/{$gallery->id}/images/{$image->id}")
        ->assertForbidden();
});
