<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_stats_returns_correct_task_counts(): void
    {
        $owner = User::factory()->create();

        $project = Project::create([
            'name' => 'Statistics project',
            'description' => 'Test project',
            'owner_id' => $owner->id,
        ]);

        $project->tasks()->createMany([
            [
                'title' => 'Todo task',
                'status' => 'todo',
                'priority' => 3,
                'due_date' => today()->addDay(),
            ],
            [
                'title' => 'In progress task',
                'status' => 'in_progress',
                'priority' => 2,
                'due_date' => today()->subDay(),
            ],
            [
                'title' => 'Completed task',
                'status' => 'done',
                'priority' => 1,
                'due_date' => today()->subDay(),
            ],
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/projects/{$project->id}/stats")
            ->assertOk()
            ->assertJson([
                'project_id' => $project->id,
                'total' => 3,
                'todo' => 1,
                'in_progress' => 1,
                'done' => 1,
                'overdue' => 1,
            ]);
    }

    public function test_user_cannot_view_another_users_project_stats(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $project = Project::create([
            'name' => 'Private project',
            'description' => null,
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/projects/{$project->id}/stats")
            ->assertForbidden();
    }
}
