<?php

declare(strict_types=1);

namespace Caasidev\LaravelGallery\Http\Controllers;

use Caasidev\LaravelGallery\Http\Requests\StoreGalleryImagesRequest;
use Caasidev\LaravelGallery\Http\Resources\GalleryImageResource;
use Caasidev\LaravelGallery\Models\Gallery;
use Caasidev\LaravelGallery\Models\GalleryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GalleryImageController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function store(StoreGalleryImagesRequest $request, Gallery $gallery): JsonResponse
    {
        Gate::authorize('create', [GalleryImage::class, $gallery]);

        $storedImages = [];
        $writtenFiles = [];
        $disk = (string) config('gallery.disk', 'public');
        $path = (string) config('gallery.path', 'galleries');

        try {
            DB::transaction(function () use (
                $request,
                $gallery,
                $disk,
                $path,
                &$storedImages,
                &$writtenFiles
            ): void {
                $startOrder = (int) $gallery->images()
                    ->lockForUpdate()
                    ->max('sort_order');

                foreach ($request->file('images') as $index => $image) {
                    $filePath = $image->store($path, $disk);

                    if ($filePath === false) {
                        throw new RuntimeException('Failed to store gallery image.');
                    }

                    $writtenFiles[] = $filePath;

                    $storedImages[] = $gallery->images()->create([
                        'path' => $filePath,
                        'disk' => $disk,
                        'sort_order' => $startOrder + $index + 1,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            foreach ($writtenFiles as $filePath) {
                Storage::disk($disk)->delete($filePath);
            }
            throw $e;
        }

        return GalleryImageResource::collection(collect($storedImages))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Gallery $gallery, GalleryImage $image): Response
    {
        abort_unless($image->gallery_id === $gallery->id, 404);

        Gate::authorize('delete', $image);

        $image->delete();

        return response()->noContent();
    }
}
