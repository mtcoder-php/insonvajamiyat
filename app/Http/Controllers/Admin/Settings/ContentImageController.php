<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Content\ContentImageService;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Matn muharriri ichiga rasm yuklash (yangilik va tadbir matni). JSON javob: {url, width, height}.
 */
class ContentImageController extends Controller
{
    public function store(Request $request, ContentImageService $images): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=200,min_height=100,max_width=8000,max_height=8000'],
        ], [], ['image' => __('rasm')]);

        /** @var User $user */
        $user = $request->user();
        $image = $images->store($request->file('image'), $user);

        return response()->json([
            'url' => MediaUrl::from($image->path),
            'width' => $image->width,
            'height' => $image->height,
        ], 201);
    }
}
