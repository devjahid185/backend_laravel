<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class MediaUrl
{
    public static function toUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $request = request();
        if ($request) {
            $base = rtrim($request->getSchemeAndHttpHost(), '/');
            return $base.'/storage/'.$path;
        }

        return Storage::disk('public')->url($path);
    }
}
