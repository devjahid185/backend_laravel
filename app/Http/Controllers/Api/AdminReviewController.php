<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Review::query();

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        $reviews = $query->orderByDesc('id')->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        return response()->json($reviews);
    }

    public function destroy(int $id): JsonResponse
    {
        $review = Review::query()->findOrFail($id);
        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }
}
