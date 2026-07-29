<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\JobPost;
use App\Models\JobCategory;
use App\Support\MediaLookup;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobs = JobPost::query()
            ->when($request->filled('post_type'), fn ($q) => $q->where('post_type', $request->input('post_type')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('title', 'like', $term)
                    ->orWhere('company', 'like', $term)
                    ->orWhere('location', 'like', $term);
            }))
            ->when($request->filled('location'), fn ($q) => $q->where('location', 'like', '%'.$request->input('location').'%'))
            ->when($request->filled('employment_type'), fn ($q) => $q->where('employment_type', $request->input('employment_type')))
            ->when($request->filled('experience_level'), fn ($q) => $q->where('experience_level', $request->input('experience_level')))
            ->when($request->filled('salary_min'), fn ($q) => $q->where('salary_max', '>=', (float) $request->input('salary_min')))
            ->when($request->filled('salary_max'), fn ($q) => $q->where('salary_min', '<=', (float) $request->input('salary_max')))
            ->when($request->input('status') !== 'all', fn ($q) => $q->where('status', 'open'))
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));
        $map = MediaLookup::primaryUrlMap('job_post', array_column($jobs->items(), 'id'));

        $jobs->setCollection(
            $jobs->getCollection()->map(function (JobPost $job) use ($map, $request) {
                $job->image_url = $map[$job->id] ?? null;
                $job->is_owner = (int) $job->posted_by === (int) $request->user()->id;
                $job->category_name = $job->category_id ? JobCategory::query()->find($job->category_id)?->name : null;

                return $job;
            })
        );

        return response()->json($jobs);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $job = JobPost::query()->findOrFail($id);
        $job->increment('views');
        $job->image_url = MediaLookup::primaryUrlMap('job_post', [$job->id])[$job->id] ?? null;
        $job->is_owner = (int) $job->posted_by === (int) $request->user()->id;

        return response()->json($job);
    }

    public function categories(): JsonResponse
    {
        return response()->json(JobCategory::query()->orderBy('name')->get());
    }

    public function post(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_type' => ['nullable', 'in:hiring,seeking'],
            'category_id' => ['nullable', 'exists:job_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'negotiable' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'experience_level' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'vacancies' => ['nullable', 'integer', 'min:1', 'max:200'],
            'deadline' => ['nullable', 'date'],
            'location_type' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'company_size' => ['nullable', 'string', 'max:50'],
            'responsibilities' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'gender' => ['nullable', 'string', 'max:32'],
            'age_min' => ['nullable', 'integer', 'min:16', 'max:80'],
            'age_max' => ['nullable', 'integer', 'min:16', 'max:80'],
        ]);

        $job = JobPost::query()->create($validated + ['posted_by' => $request->user()->id]);

        return response()->json([
            'message' => 'Job posted successfully',
            'job' => $job,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $job = JobPost::query()->findOrFail($id);
        if ((int) $job->posted_by !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'post_type' => ['nullable', 'in:hiring,seeking'],
            'category_id' => ['nullable', 'exists:job_categories,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'negotiable' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'experience_level' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'vacancies' => ['nullable', 'integer', 'min:1', 'max:200'],
            'deadline' => ['nullable', 'date'],
            'location_type' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'company_size' => ['nullable', 'string', 'max:50'],
            'responsibilities' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'gender' => ['nullable', 'string', 'max:32'],
            'age_min' => ['nullable', 'integer', 'min:16', 'max:80'],
            'age_max' => ['nullable', 'integer', 'min:16', 'max:80'],
            'status' => ['nullable', 'in:open,closed'],
        ]);

        $job->update($validated);

        return response()->json([
            'message' => 'Job updated successfully',
            'job' => $job->fresh(),
        ]);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $job = JobPost::query()->findOrFail($id);
        if ((int) $job->posted_by !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $job->update(['status' => 'closed']);

        return response()->json(['message' => 'Job closed']);
    }

    public function myPosts(Request $request): JsonResponse
    {
        $jobs = JobPost::query()
            ->where('posted_by', $request->user()->id)
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $map = MediaLookup::primaryUrlMap('job_post', array_column($jobs->items(), 'id'));
        $jobs->setCollection(
            $jobs->getCollection()->map(function (JobPost $job) use ($map) {
                $job->image_url = $map[$job->id] ?? null;

                return $job;
            })
        );

        return response()->json($jobs);
    }

    public function myApplications(Request $request): JsonResponse
    {
        $apps = JobApplication::query()
            ->with('jobPost')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $apps->setCollection(
            $apps->getCollection()->map(function (JobApplication $app) {
                $app->cv_url = $app->cv_file ? MediaUrl::toUrl($app->cv_file) : null;

                return $app;
            })
        );

        return response()->json($apps);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'exists:job_posts,id'],
            'cv' => ['nullable', 'string', 'max:255'],
            'cv_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'cover_letter' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        $job = JobPost::query()->find((int) $validated['job_id']);
        if (! $job || $job->status !== 'open' || $job->post_type !== 'hiring') {
            return response()->json(['message' => 'Job is not open for application'], 422);
        }

        $cvFile = null;
        $cvOriginal = null;
        $cvMime = null;
        if ($request->hasFile('cv_file')) {
            $file = $request->file('cv_file');
            $cvFile = $file->store('uploads/job_applications/'.date('Y/m'), 'public');
            $cvOriginal = $file->getClientOriginalName();
            $cvMime = $file->getClientMimeType();
        }

        $application = JobApplication::query()->updateOrCreate(
            [
                'job_post_id' => $validated['job_id'],
                'user_id' => $request->user()->id,
            ],
            [
                'cv' => $validated['cv'] ?? null,
                'cv_file' => $cvFile,
                'cv_original_name' => $cvOriginal,
                'cv_mime' => $cvMime,
                'cover_letter' => $validated['cover_letter'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'expected_salary' => $validated['expected_salary'] ?? null,
                'note' => $validated['note'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Job application submitted',
            'application' => $application,
            'cv_url' => $cvFile ? MediaUrl::toUrl($cvFile) : null,
        ]);
    }

    public function applications(Request $request, int $id): JsonResponse
    {
        $job = JobPost::query()->findOrFail($id);
        if ((int) $job->posted_by !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $apps = JobApplication::query()
            ->with('user')
            ->where('job_post_id', $id)
            ->orderByDesc('id')
            ->get()
            ->map(function (JobApplication $app) {
                $app->cv_url = $app->cv_file ? MediaUrl::toUrl($app->cv_file) : null;

                return $app;
            });

        return response()->json($apps);
    }
}
