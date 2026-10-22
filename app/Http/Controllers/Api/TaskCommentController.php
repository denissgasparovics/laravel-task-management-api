<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskCommentController extends Controller
{
    public function index(Request $request, Project $project, Task $task)
    {
        Gate::authorize('view', $project);

        abort_unless($task->project_id === $project->id, 404);

        $comments = $task->comments()
            ->with('user')
            ->orderBy('created_at')
            ->paginate(20);

        return CommentResource::collection($comments);
    }

    public function store(
        StoreCommentRequest $request,
        Project $project,
        Task $task
    ) {
        abort_unless($task->project_id === $project->id, 404);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return new CommentResource($comment->load('user'));
    }
}
