<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function bookService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'worker_id' => ['required', 'exists:workers,id'],
            'service_date' => ['required', 'date'],
            'service_time' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $booking = ServiceBooking::query()->create($validated + [
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Service booked successfully',
            'booking' => $booking,
        ], 201);
    }

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = DB::table('service_bookings')
            ->join('workers', 'workers.id', '=', 'service_bookings.worker_id')
            ->join('users', 'users.id', '=', 'workers.user_id')
            ->select(
                'service_bookings.*',
                'users.name as worker_name',
                'users.phone as worker_phone'
            )
            ->where('service_bookings.user_id', $request->user()->id)
            ->orderByDesc('service_bookings.id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        return response()->json($bookings);
    }

    public function cancelBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'exists:service_bookings,id'],
        ]);

        $booking = ServiceBooking::query()
            ->where('id', $validated['booking_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            return response()->json(['message' => 'Booking cannot be cancelled'], 422);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Booking cancelled successfully']);
    }
}
