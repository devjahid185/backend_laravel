<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;

class NoticeController extends Controller
{
    public function index(): JsonResponse
    {
        $notices = Notice::query()->latest()->paginate(20);
        $map = MediaLookup::primaryUrlMap('notice', array_column($notices->items(), 'id'));

        $notices->setCollection(
            $notices->getCollection()->map(function (Notice $notice) use ($map) {
                $notice->image_url = $map[$notice->id] ?? null;

                return $notice;
            })
        );

        return response()->json($notices);
    }
}
