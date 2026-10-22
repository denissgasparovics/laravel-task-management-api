<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use App\Services\TaskService;

class TaskController extends Controller
{

    public function index(
        \Illuminate\Http\Request $request,
        Project $project
    ): \Illuminate\Http\Resources\Json\AnonymousResourceCollection {
        \Illuminate\Support\Facades\Gate::authorize('view', $project);

        $query = $project->tasks()->with('assignee');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->integer('priority'));
        }

        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->integer('assignee_id'));
        }

        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->input('due_date'));
        }

        $allowedSortFields = [
            'created_at',
            'due_date',
            'priority',
            'title',
            'status',
        ];

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        if (! in_array($sortBy, $allowedSortFields, true)) {
            $sortBy = 'created_at';
        }

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $tasks = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        return \App\Http\Resources\TaskResource::collection($tasks);
    }

    public function store(
        StoreTaskRequest $request,
        Project $project,
        TaskService $taskService
    ): TaskResource {
        $data = $request->validated();

        if (array_key_exists('assignee_id', $data)) {
            $taskService->validateAssignee(
                $project,
                $data['assignee_id'] === null
                    ? null
                    : (int) $data['assignee_id']
            );
        }

        $task = $taskService->create($project, $data);

        return new TaskResource($task);
    }

    public function show(Project $project, Task $task): TaskResource
    {
        Gate::authorize('view', $project);

        abort_unless($task->project_id === $project->id, 404);

        return new TaskResource($task->load('assignee'));
    }

    public function update(
        UpdateTaskRequest $request,
        Project $project,
        Task $task,
        TaskService $taskService
    ): TaskResource {
        $data = $request->validated();

        if (array_key_exists('assignee_id', $data)) {
            $taskService->validateAssignee(
                $project,
                $data['assignee_id'] === null
                    ? null
                    : (int) $data['assignee_id']
            );
        }

        $updatedTask = $taskService->update($task, $data);

        return new TaskResource($updatedTask->load('assignee'));
    }

    public function destroy(
        Project $project,
        Task $task
    ): Response {
        Gate::authorize('update', $project);

        abort_unless($task->project_id === $project->id, 404);

        $task->delete();

        return response()->noContent();
    }
}
