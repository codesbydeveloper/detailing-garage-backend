<?php

namespace App\Http\Controllers\Api\ActivityLogs;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Support\Query;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'user_id' => ['nullable', 'integer'],
            'role' => ['nullable', 'string', 'max:30'],
            'action' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'max:100'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'entity_id' => ['nullable', 'integer'],
        ]);

        $query = ActivityLog::query()->with('user:id,name,email,role');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['role'])) {
            $query->whereHas('user', fn ($user) => $user->where('role', $filters['role']));
        }

        foreach (['action', 'module', 'entity_type', 'entity_id', 'status'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if ($like = Query::like($filters['search'] ?? null)) {
            $query->where(function ($inner) use ($like) {
                $inner->where('description', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhere('module', 'like', $like);
            });
        }

        Query::whereDateRange($query, 'created_at', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        Query::sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null, ['id', 'created_at', 'action', 'module'], 'created_at');

        return $this->paginated($query->paginate(Query::perPage($filters['per_page'] ?? null)), ActivityLogResource::class);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->load('user:id,name,email,role');

        return $this->success(new ActivityLogResource($activityLog), 'Activity log loaded');
    }
}
