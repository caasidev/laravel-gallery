<?php

declare(strict_types=1);

namespace Caasidev\LaravelGallery\Tests\Fixtures;

use Caasidev\LaravelGallery\Models\Gallery;
use Caasidev\LaravelGallery\Models\GalleryImage;
use Illuminate\Contracts\Auth\Authenticatable;

class DenyAllGalleryImagePolicy
{
    public function create(?Authenticatable $user, Gallery $gallery): bool
    {
        return false;
    }

    public function delete(?Authenticatable $user, GalleryImage $image): bool
    {
        return false;
    }
}
