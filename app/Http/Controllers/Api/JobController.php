<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = JobPost::query()->latest()->paginate(20);
        $map = MediaLookup::primaryUrlMap('job_post', array_column($jobs->items(), 'id'));

        $jobs->setCollection(
            $jobs->getCollection()->map(function (JobPost $job) use ($map) {
                $job->image_url = $map[$job->id] ?? null;

                return $job;
            })
        );

        return response()->json($jobs);
    }

    public function post(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
        ]);

        $job = JobPost::query()->create($validated + ['posted_by' => $request->user()->id]);

        return response()->json([
            'message' => 'Job posted successfully',
            'job' => $job,
        ], 201);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'exists:job_posts,id'],
            'cv' => ['nullable', 'string', 'max:255'],
            'cover_letter' => ['nullable', 'string'],
        ]);

        $application = JobApplication::query()->updateOrCreate(
            [
                'job_post_id' => $validated['job_id'],
                'user_id' => $request->user()->id,
            ],
            [
                'cv' => $validated['cv'] ?? null,
                'cover_letter' => $validated['cover_letter'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Job application submitted',
            'application' => $application,
        ]);
    }
}
