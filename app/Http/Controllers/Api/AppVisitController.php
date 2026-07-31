<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVisitLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppVisitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source' => ['nullable', 'string', 'max:40'],
            'path' => ['nullable', 'string', 'max:120'],
            'session_key' => ['nullable', 'string', 'max:120'],
        ]);

        AppVisitLog::query()->create([
            'user_id' => $request->user()?->id,
            'source' => $data['source'] ?? 'flutter',
            'path' => $data['path'] ?? 'home',
            'session_key' => $data['session_key'] ?? Str::uuid()->toString(),
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'visited_at' => now(),
        ]);

        return response()->json(['message' => 'Visit tracked']);
    }
}
