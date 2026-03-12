<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Faq::query()->where('is_active', true);

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $faqs = $query
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($faqs);
    }
}