<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Services\JobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends ApiController
{
    public function index(Request $request, JobService $jobs): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'payment_status' => ['nullable', 'string', 'max:30'],
            'job_status' => ['nullable', 'string', 'max:30'],
            'work_type_id' => ['nullable', 'integer'],
        ]);

        return $this->paginated($jobs->paginate($filters), JobResource::class);
    }

    public function store(StoreJobRequest $request, JobService $jobs): JsonResponse
    {
        $job = $jobs->create($request->validated(), $request->user())->load('workType');

        return $this->success(new JobResource($job), 'Job created', 201);
    }

    public function show(Job $job): JsonResponse
    {
        $job->load(['workType', 'payments', 'lead']);

        return $this->success(new JobResource($job), 'Job loaded');
    }

    public function update(UpdateJobRequest $request, Job $job, JobService $jobs): JsonResponse
    {
        $job = $jobs->update($job, $request->validated(), $request->user());

        return $this->success(new JobResource($job), 'Job updated');
    }

    public function destroy(Request $request, Job $job, JobService $jobs): JsonResponse
    {
        $jobs->delete($job, $request->user());

        return $this->success(null, 'Job deleted');
    }
}
