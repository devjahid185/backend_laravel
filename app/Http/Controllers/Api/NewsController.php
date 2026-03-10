<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Support\MediaLookup;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    public function index(): JsonResponse
    {
        $news = News::query()->latest()->paginate(20);
        $map = MediaLookup::primaryUrlMap('news', array_column($news->items(), 'id'));

        $news->setCollection(
            $news->getCollection()->map(function (News $row) use ($map) {
                $row->image_url = $map[$row->id] ?? MediaUrl::toUrl($row->image);

                return $row;
            })
        );

        return response()->json($news);
    }

    public function show(int $id): JsonResponse
    {
        $news = News::query()->find($id);

        if (! $news) {
            return response()->json(['message' => 'News not found'], 404);
        }

        $news->image_url = MediaLookup::primaryUrlMap('news', [$news->id])[$news->id] ?? MediaUrl::toUrl($news->image);

        return response()->json($news);
    }
}
