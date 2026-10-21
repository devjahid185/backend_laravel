<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NotificationImageService
{
    private const MAX_FCM_BYTES = 900 * 1024;

    public function store(UploadedFile $file): string
    {
        $directory = 'notifications/'.now()->format('Y/m');

        if (class_exists(\Imagick::class)) {
            try {
                return $this->storeOptimized($file, $directory);
            } catch (\Throwable $e) {
                Log::warning('Notification image optimization failed.', [
                    'error' => $e->getMessage(),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        if (($file->getSize() ?: 0) > self::MAX_FCM_BYTES) {
            throw ValidationException::withMessages([
                'image' => ['Image processing failed. Please use an image smaller than 900 KB.'],
            ]);
        }

        return $file->store($directory, 'public');
    }

    private function storeOptimized(UploadedFile $file, string $directory): string
    {
        $image = new \Imagick($file->getRealPath());
        if ($image->getNumberImages() > 1) {
            $image->setIteratorIndex(0);
        }
        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        } elseif (method_exists($image, 'autoOrientImage')) {
            $image->autoOrientImage();
        }

        $image->thumbnailImage(1600, 1600, true, false);
        $image->setImageBackgroundColor(new \ImagickPixel('white'));
        if ($image->getImageAlphaChannel()) {
            $image = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
        }
        $image->setImageFormat('jpeg');
        $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
        $image->stripImage();

        $blob = '';
        foreach ([82, 74, 66, 58, 50, 44] as $quality) {
            $image->setImageCompressionQuality($quality);
            $blob = $image->getImagesBlob();
            if (strlen($blob) <= self::MAX_FCM_BYTES) {
                break;
            }
        }

        while (strlen($blob) > self::MAX_FCM_BYTES && max($image->getImageWidth(), $image->getImageHeight()) > 720) {
            $image->resizeImage(
                max(1, (int) round($image->getImageWidth() * 0.82)),
                max(1, (int) round($image->getImageHeight() * 0.82)),
                \Imagick::FILTER_LANCZOS,
                1,
            );
            $blob = $image->getImagesBlob();
        }

        $image->clear();
        $image->destroy();

        if ($blob === '' || strlen($blob) > self::MAX_FCM_BYTES) {
            throw new \RuntimeException('Unable to reduce notification image below the FCM size limit.');
        }

        $path = $directory.'/'.Str::uuid().'.jpg';
        Storage::disk('public')->put($path, $blob, ['visibility' => 'public']);

        return $path;
    }
}
