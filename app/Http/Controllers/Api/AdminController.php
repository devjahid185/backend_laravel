<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceItem;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'users' => User::query()->count(),
            'workers' => Worker::query()->count(),
            'pending_workers' => Worker::query()->where('status', 'pending')->count(),
            'marketplace_items' => MarketplaceItem::query()->count(),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(User::query()->latest()->paginate(50));
    }

    public function blockUser(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'is_blocked' => ['required', 'boolean'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);
        $user->update(['is_blocked' => $validated['is_blocked']]);

        return response()->json(['message' => 'User block status updated']);
    }

    public function approveWorker(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'worker_id' => ['required', 'exists:workers,id'],
            'status' => ['required', 'in:approved,rejected,pending'],
        ]);

        $worker = Worker::query()->findOrFail($validated['worker_id']);
        $worker->update(['status' => $validated['status']]);

        return response()->json(['message' => 'Worker status updated']);
    }

    public function deleteAd(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'item_id' => ['required', 'exists:marketplace_items,id'],
        ]);

        MarketplaceItem::query()->where('id', $validated['item_id'])->delete();

        return response()->json(['message' => 'Marketplace ad deleted']);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()->role === 'admin';
    }
}
