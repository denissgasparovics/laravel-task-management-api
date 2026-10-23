<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectMemberController extends Controller
{
    public function store(Project $project, User $user): JsonResponse
    {
        Gate::authorize('update', $project);

        abort_if($project->owner_id === $user->id, 422, 'The project owner is already a project member.');

        $project->members()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'project_id' => $project->id,
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function destroy(Project $project, User $user): Response
    {
        Gate::authorize('update', $project);

        abort_if($project->owner_id === $user->id, 422, 'The project owner cannot be removed.');

        $project->members()->detach($user->id);

        return response()->noContent();
    }
}
