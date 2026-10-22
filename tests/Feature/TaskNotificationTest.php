<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignee_is_notified_when_assigned_to_task(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::create([
            'name' => 'Team project',
            'owner_id' => $owner->id,
        ]);

        $project->members()->attach($member->id);

        $task = $project->tasks()->create([
            'title' => 'Implement notifications',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson(
                "/api/projects/{$project->id}/tasks/{$task->id}",
                ['assignee_id' => $member->id]
            )
            ->assertOk();

        Notification::assertSentTo(
            $member,
            TaskAssignedNotification::class
        );
    }
}
