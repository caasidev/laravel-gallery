<?php

declare(strict_types=1);

use Caasidev\LaravelGallery\Http\Resources\GalleryImageResource;
use Caasidev\LaravelGallery\Models\Gallery;
use Caasidev\LaravelGallery\Models\GalleryImage;
use Caasidev\LaravelGallery\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

it('transforms a gallery image', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $image = GalleryImage::factory()->for($gallery)->create([
        'path' => 'galleries/photo.jpg',
        'disk' => 'public',
        'alt_text' => 'A photo',
        'sort_order' => 5,
    ]);

    /** @var array<string, mixed> $resource */
    $resource = (new GalleryImageResource($image))->toArray(new Request);

    expect($resource['id'])->toBe($image->id)
        ->and($resource['path'])->toBe('galleries/photo.jpg')
        ->and($resource['url'])->toBe(Storage::disk('public')->url('galleries/photo.jpg'))
        ->and($resource['alt_text'])->toBe('A photo')
        ->and($resource['sort_order'])->toBe(5);
});
