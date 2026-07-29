<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminModuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $modules = AdminModule::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'group_name', 'route']);

        return response()->json([
            'modules' => $modules,
        ]);
    }
}
