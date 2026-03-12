<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UpdatePost;

class UpdatesController extends Controller
{
    public function index()
    {
        $posts = UpdatePost::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->get();

        return view('updates', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug)
    {
        $post = UpdatePost::query()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('updates_show', [
            'post' => $post,
        ]);
    }
}