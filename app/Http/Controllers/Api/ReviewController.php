<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Hospital;
use App\Models\Review;
use App\Models\User;
use App\Models\Worker;
use App\Models\CarRental;
use App\Models\CourierOffice;
use App\Models\Teacher;
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

    public function hospitalReviews(int $hospitalId): JsonResponse
    {
        $reviews = Review::query()
            ->where('type', 'hospital')
            ->where('target_id', $hospitalId)
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

    public function carRentalReviews(int $rentalId): JsonResponse
    {
        $reviews = Review::query()
            ->where('type', 'car_rental')
            ->where('target_id', $rentalId)
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

    public function courierReviews(int $officeId): JsonResponse
    {
        $reviews = Review::query()
            ->where('type', 'courier')
            ->where('target_id', $officeId)
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

    public function teacherReviews(int $teacherId): JsonResponse
    {
        $reviews = Review::query()
            ->where('type', 'teacher')
            ->where('target_id', $teacherId)
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

    public function rateHospital(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'exists:hospitals,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $hospitalOwnerId = Hospital::query()
            ->where('id', $validated['target_id'])
            ->value('user_id');
        if ($hospitalOwnerId === $request->user()->id) {
            return response()->json(['message' => 'নিজের হাসপাতাল প্রোফাইলে রেটিং দেওয়া যাবে না'], 403);
        }

        $review = Review::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'type' => 'hospital',
                'target_id' => $validated['target_id'],
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        $avgRating = Review::query()
            ->where('type', 'hospital')
            ->where('target_id', $validated['target_id'])
            ->avg('rating');

        Hospital::query()
            ->where('id', $validated['target_id'])
            ->update(['rating' => round((float) $avgRating, 2)]);

        return response()->json([
            'message' => 'রেটিং জমা হয়েছে',
            'review' => $review,
            'average_rating' => round((float) $avgRating, 2),
        ]);
    }

    public function rateCarRental(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'exists:car_rentals,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $ownerId = CarRental::query()
            ->where('id', $validated['target_id'])
            ->value('user_id');
        if ($ownerId === $request->user()->id) {
            return response()->json(['message' => 'নিজের গাড়িতে রেটিং দেওয়া যাবে না'], 403);
        }

        $review = Review::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'type' => 'car_rental',
                'target_id' => $validated['target_id'],
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        $avgRating = Review::query()
            ->where('type', 'car_rental')
            ->where('target_id', $validated['target_id'])
            ->avg('rating');

        CarRental::query()
            ->where('id', $validated['target_id'])
            ->update(['rating' => round((float) $avgRating, 2)]);

        return response()->json([
            'message' => 'রেটিং জমা হয়েছে',
            'review' => $review,
            'average_rating' => round((float) $avgRating, 2),
        ]);
    }

    public function rateCourier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'exists:courier_offices,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $ownerId = CourierOffice::query()
            ->where('id', $validated['target_id'])
            ->value('user_id');
        if ($ownerId && $ownerId === $request->user()->id) {
            return response()->json(['message' => 'নিজের অফিসে রেটিং দেওয়া যাবে না'], 403);
        }

        $review = Review::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'type' => 'courier',
                'target_id' => $validated['target_id'],
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        $avgRating = Review::query()
            ->where('type', 'courier')
            ->where('target_id', $validated['target_id'])
            ->avg('rating');

        CourierOffice::query()
            ->where('id', $validated['target_id'])
            ->update(['rating' => round((float) $avgRating, 2)]);

        return response()->json([
            'message' => 'রেটিং জমা হয়েছে',
            'review' => $review,
            'average_rating' => round((float) $avgRating, 2),
        ]);
    }
    public function rateTeacher(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'exists:teachers,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $ownerId = Teacher::query()
            ->where('id', $validated['target_id'])
            ->value('user_id');
        if ($ownerId && $ownerId === $request->user()->id) {
            return response()->json(['message' => 'à¦¨à¦¿à¦œà§‡à¦° à¦Ÿà¦¿à¦‰à¦Ÿà¦° à¦ªà§à¦°à§‹à¦«à¦¾à¦‡à¦²à§‡ à¦°à§‡à¦Ÿà¦¿à¦‚ à¦¦à§‡à¦“à§Ÿà¦¾ à¦¯à¦¾à¦¬à§‡ à¦¨à¦¾'], 403);
        }

        $review = Review::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'type' => 'teacher',
                'target_id' => $validated['target_id'],
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        $avgRating = Review::query()
            ->where('type', 'teacher')
            ->where('target_id', $validated['target_id'])
            ->avg('rating');

        Teacher::query()
            ->where('id', $validated['target_id'])
            ->update(['rating' => round((float) $avgRating, 2)]);

        return response()->json([
            'message' => 'à¦°à§‡à¦Ÿà¦¿à¦‚ à¦œà¦®à¦¾ à¦¹à§Ÿà§‡à¦›à§‡',
            'review' => $review,
            'average_rating' => round((float) $avgRating, 2),
        ]);
    }
}
