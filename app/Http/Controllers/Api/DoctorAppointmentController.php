<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorAppointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorAppointmentController extends Controller
{
    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'patient_name' => ['required', 'string', 'max:120'],
            'patient_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'patient_gender' => ['nullable', 'string', 'max:30'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['nullable', 'string', 'max:20'],
            'problem' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $appointment = DoctorAppointment::query()->create($validated + [
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Appointment booked',
            'appointment' => $appointment,
        ], 201);
    }

    public function myAppointments(Request $request): JsonResponse
    {
        $appointments = DoctorAppointment::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $doctorIds = $appointments->pluck('doctor_id')->unique()->filter()->all();
        $doctorMap = Doctor::query()
            ->whereIn('id', $doctorIds)
            ->get()
            ->keyBy('id');

        $appointments->setCollection(
            $appointments->getCollection()->map(function (DoctorAppointment $appointment) use ($doctorMap) {
                $doctor = $doctorMap->get($appointment->doctor_id);
                $appointment->doctor_name = $doctor?->name;
                $appointment->doctor_hospital = $doctor?->hospital;
                $appointment->doctor_phone = $doctor?->phone;

                return $appointment;
            })
        );

        return response()->json($appointments);
    }

    public function doctorAppointments(Request $request, int $doctorId): JsonResponse
    {
        $doctor = Doctor::query()->findOrFail($doctorId);
        if ((int) $doctor->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $appointments = DoctorAppointment::query()
            ->where('doctor_id', $doctorId)
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        return response()->json($appointments);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $appointment = DoctorAppointment::query()->findOrFail($id);
        $doctor = Doctor::query()->find($appointment->doctor_id);
        if (! $doctor || (int) $doctor->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed'],
        ]);

        $appointment->update(['status' => $validated['status']]);

        return response()->json(['message' => 'Status updated']);
    }
}
