<?php

declare(strict_types=1);

namespace Caasidev\LaravelGallery\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GalleryImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'path' => $this->resource->path,
            'url' => Storage::disk($this->resource->disk ?: 'public')->url($this->resource->path),
            'alt_text' => $this->resource->alt_text,
            'sort_order' => $this->resource->sort_order,
        ];
    }
}
