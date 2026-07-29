<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarRental;
use App\Models\CarRentalBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarRentalBookingController extends Controller
{
    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'car_rental_id' => ['required', 'exists:car_rentals,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'pickup_location' => ['nullable', 'string', 'max:255'],
            'dropoff_location' => ['nullable', 'string', 'max:255'],
            'need_driver' => ['nullable', 'boolean'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $booking = CarRentalBooking::query()->create($validated + [
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Booking created',
            'booking' => $booking,
        ], 201);
    }

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = CarRentalBooking::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        return response()->json($bookings);
    }

    public function ownerBookings(Request $request, int $rentalId): JsonResponse
    {
        $rental = CarRental::query()->findOrFail($rentalId);
        if ((int) $rental->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $bookings = CarRentalBooking::query()
            ->where('car_rental_id', $rentalId)
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        return response()->json($bookings);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $booking = CarRentalBooking::query()->findOrFail($id);
        $rental = CarRental::query()->find($booking->car_rental_id);
        if (! $rental || (int) $rental->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed'],
        ]);

        $booking->update(['status' => $validated['status']]);

        return response()->json(['message' => 'Status updated']);
    }
}
