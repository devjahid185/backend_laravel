<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function bkash(Request $request): JsonResponse
    {
        return $this->createPayment($request, 'bkash');
    }

    public function nagad(Request $request): JsonResponse
    {
        return $this->createPayment($request, 'nagad');
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'exists:payments,transaction_id'],
            'status' => ['required', 'in:success,failed'],
        ]);

        $payment = Payment::query()->where('transaction_id', $validated['transaction_id'])->firstOrFail();

        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized payment access'], 403);
        }

        $payment->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Payment verified',
            'payment' => $payment->fresh(),
        ]);
    }

    private function createPayment(Request $request, string $method): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $payment = Payment::query()->create([
            'user_id' => $request->user()->id,
            'amount' => $validated['amount'],
            'method' => $method,
            'transaction_id' => strtoupper($method).'-'.Str::upper(Str::random(12)),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => ucfirst($method).' payment initiated',
            'payment' => $payment,
        ], 201);
    }
}
