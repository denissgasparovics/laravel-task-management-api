<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCommentTest extends TestCase
{
    use RefreshDatabase;

    private function createProjectAndTask(User $owner): array
    {
        $project = Project::create([
            'name' => 'Test project',
            'description' => null,
            'owner_id' => $owner->id,
        ]);

        $task = $project->tasks()->create([
            'title' => 'Test task',
            'status' => 'todo',
            'priority' => 3,
        ]);

        return [$project, $task];
    }

    public function test_project_owner_can_add_comment_to_task(): void
    {
        $owner = User::factory()->create();
        [$project, $task] = $this->createProjectAndTask($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson(
                "/api/projects/{$project->id}/tasks/{$task->id}/comments",
                ['body' => 'This is a test comment.']
            )
            ->assertCreated()
            ->assertJsonPath('data.body', 'This is a test comment.')
            ->assertJsonPath('data.author.id', $owner->id);

        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'body' => 'This is a test comment.',
        ]);
    }

    public function test_project_owner_can_list_task_comments(): void
    {
        $owner = User::factory()->create();
        [$project, $task] = $this->createProjectAndTask($owner);

        $task->comments()->create([
            'user_id' => $owner->id,
            'body' => 'Existing comment',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson(
                "/api/projects/{$project->id}/tasks/{$task->id}/comments"
            )
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Existing comment')
            ->assertJsonPath('data.0.author.id', $owner->id);
    }

    public function test_comment_body_is_required(): void
    {
        $owner = User::factory()->create();
        [$project, $task] = $this->createProjectAndTask($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson(
                "/api/projects/{$project->id}/tasks/{$task->id}/comments",
                ['body' => '']
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_user_cannot_add_comment_to_another_users_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$project, $task] = $this->createProjectAndTask($owner);

        $this->actingAs($otherUser, 'sanctum')
            ->postJson(
                "/api/projects/{$project->id}/tasks/{$task->id}/comments",
                ['body' => 'Unauthorized comment']
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('comments', [
            'task_id' => $task->id,
            'body' => 'Unauthorized comment',
        ]);
    }
}
