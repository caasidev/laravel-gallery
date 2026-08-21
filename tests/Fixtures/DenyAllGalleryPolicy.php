<?php

declare(strict_types=1);

namespace Caasidev\LaravelGallery\Tests\Fixtures;

use Caasidev\LaravelGallery\Models\Gallery;
use Illuminate\Contracts\Auth\Authenticatable;

class DenyAllGalleryPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return false;
    }

    public function view(?Authenticatable $user, Gallery $gallery): bool
    {
        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return false;
    }

    public function update(?Authenticatable $user, Gallery $gallery): bool
    {
        return false;
    }

    public function delete(?Authenticatable $user, Gallery $gallery): bool
    {
        return false;
    }
}
