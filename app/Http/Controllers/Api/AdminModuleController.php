<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminModuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user();
        $modules = AdminModule::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'group_name', 'route'])
            ->filter(fn (AdminModule $module): bool => ! $admin instanceof Admin || $admin->canAccessAdminModule($module->slug, 'view'))
            ->values();

        return response()->json([
            'modules' => $modules,
        ]);
    }
}
