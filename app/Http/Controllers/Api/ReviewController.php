<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Review;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function workerReviews(int $workerId): JsonResponse
    {
        $reviews = Review::query()
            ->where('type', 'worker')
            ->where('target_id', $workerId)
            ->join('users', 'users.id', '=', 'reviews.user_id')
            ->orderByDesc('reviews.id')
            ->get([
                'reviews.id',
                'reviews.rating',
                'reviews.comment',
                'reviews.created_at',
                'users.name as user_name',
            ]);

        return response()->json($reviews);
    }

    public function businessReviews(int $businessId): JsonResponse
    {
        $reviews = Review::query()
            ->where('type', 'business')
            ->where('target_id', $businessId)
            ->join('users', 'users.id', '=', 'reviews.user_id')
            ->orderByDesc('reviews.id')
            ->get([
                'reviews.id',
                'reviews.rating',
                'reviews.comment',
                'reviews.created_at',
                'users.name as user_name',
            ]);

        return response()->json($reviews);
    }

    public function rateWorker(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'exists:workers,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $workerOwnerId = Worker::query()
            ->where('id', $validated['target_id'])
            ->value('user_id');
        if ($workerOwnerId === $request->user()->id) {
            return response()->json(['message' => 'নিজের কর্মী প্রোফাইলে রেটিং দেওয়া যাবে না'], 403);
        }

        $review = Review::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'type' => 'worker',
                'target_id' => $validated['target_id'],
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        $avgRating = Review::query()
            ->where('type', 'worker')
            ->where('target_id', $validated['target_id'])
            ->avg('rating');

        $worker = Worker::query()->find($validated['target_id']);
        if ($worker) {
            User::query()->where('id', $worker->user_id)->update([
                'rating' => round((float) $avgRating, 2),
            ]);
        }

        return response()->json([
            'message' => '????? ??? ?????',
            'review' => $review,
            'average_rating' => round((float) $avgRating, 2),
        ]);
    }

    public function rateBusiness(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'exists:businesses,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $businessOwnerId = Business::query()
            ->where('id', $validated['target_id'])
            ->value('user_id');
        if ($businessOwnerId === $request->user()->id) {
            return response()->json(['message' => 'নিজের ব্যবসায় রেটিং দেওয়া যাবে না'], 403);
        }

        $review = Review::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'type' => 'business',
                'target_id' => $validated['target_id'],
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        $avgRating = Review::query()
            ->where('type', 'business')
            ->where('target_id', $validated['target_id'])
            ->avg('rating');

        Business::query()
            ->where('id', $validated['target_id'])
            ->update(['rating' => round((float) $avgRating, 2)]);

        return response()->json([
            'message' => '????? ??? ?????',
            'review' => $review,
            'average_rating' => round((float) $avgRating, 2),
        ]);
    }
}
