<?php

declare(strict_types=1);

namespace Caasidev\LaravelGallery\Tests;

use Caasidev\LaravelGallery\GalleryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [GalleryServiceProvider::class];
    }
}
