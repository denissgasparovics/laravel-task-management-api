<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkOverdueTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_finds_overdue_uncompleted_tasks(): void
    {
        $owner = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'description' => 'Test description',
            'owner_id' => $owner->id,
        ]);

        $project->tasks()->create([
            'title' => 'Overdue task',
            'status' => 'todo',
            'priority' => 3,
            'due_date' => today()->subDay(),
        ]);

        $project->tasks()->create([
            'title' => 'Completed task',
            'status' => 'done',
            'priority' => 3,
            'due_date' => today()->subDay(),
        ]);

        $this->artisan('tasks:mark-overdue')
            ->expectsOutput('Found 1 overdue tasks.')
            ->assertExitCode(0);
    }
}
