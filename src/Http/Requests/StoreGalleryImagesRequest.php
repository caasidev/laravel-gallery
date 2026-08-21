<?php

declare(strict_types=1);

namespace Caasidev\LaravelGallery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGalleryImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
