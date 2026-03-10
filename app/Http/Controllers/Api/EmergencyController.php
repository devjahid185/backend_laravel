<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;

class EmergencyController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = EmergencyContact::query()->orderBy('category')->orderBy('name')->get();
        $map = MediaLookup::primaryUrlMap('emergency', $rows->pluck('id')->all());

        return response()->json(
            $rows->map(function (EmergencyContact $contact) use ($map) {
                $contact->image_url = $map[$contact->id] ?? null;

                return $contact;
            })
        );
    }
}
