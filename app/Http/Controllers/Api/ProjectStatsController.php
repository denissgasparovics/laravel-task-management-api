<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;

class ProjectStatsController extends Controller
{
    public function __invoke(Project $project)
    {
        Gate::authorize('view', $project);

        $tasks = $project->tasks();

        return response()->json([
            'project_id' => $project->id,
            'total' => (clone $tasks)->count(),
            'todo' => (clone $tasks)->where('status', 'todo')->count(),
            'in_progress' => (clone $tasks)->where('status', 'in_progress')->count(),
            'done' => (clone $tasks)->where('status', 'done')->count(),
            'overdue' => (clone $tasks)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->where('status', '!=', 'done')
                ->count(),
        ]);
    }
}
