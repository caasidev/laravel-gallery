<?php

declare(strict_types=1);

use Caasidev\LaravelGallery\Http\Requests\StoreGalleryImagesRequest;

it('is always authorized', function () {
    $request = new StoreGalleryImagesRequest;

    expect($request->authorize())->toBeTrue();
});

it('requires at least one image', function () {
    $request = new StoreGalleryImagesRequest;

    $rules = $request->rules();

    expect($rules['images'])->toContain('required', 'array', 'min:1')
        ->and($rules['images.*'])->toContain('image', 'mimes:jpg,jpeg,png,webp', 'max:5120');
});
