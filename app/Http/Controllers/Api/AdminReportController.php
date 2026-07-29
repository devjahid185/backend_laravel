<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Report::query();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $reports = $query->orderByDesc('id')->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        return response()->json($reports);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $report = Report::query()->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'reviewed', 'resolved', 'rejected'])],
        ]);

        $report->update($validated);

        return response()->json([
            'message' => 'Report updated.',
            'report' => $report->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $report = Report::query()->findOrFail($id);
        $report->delete();

        return response()->json(['message' => 'Report deleted.']);
    }
}
