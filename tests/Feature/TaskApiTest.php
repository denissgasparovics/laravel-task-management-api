<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_create_task(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Implement authentication',
                'description' => 'Add Sanctum authentication',
                'priority' => 1,
                'status' => 'todo',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Implement authentication')
            ->assertJsonPath('data.priority', 1);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Implement authentication',
            'status' => 'todo',
        ]);
    }

    public function test_project_owner_can_list_tasks(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $project->tasks()->create([
            'title' => 'First task',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks");

        $response->assertOk()
            ->assertJsonFragment(['title' => 'First task']);
    }

    public function test_user_cannot_create_task_in_another_users_project(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::create([
            'name' => 'Private project',
            'owner_id' => $owner->id,
        ]);

        $response = $this->actingAs($anotherUser, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Unauthorized task',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('tasks', [
            'project_id' => $project->id,
            'title' => 'Unauthorized task',
        ]);
    }

    public function test_task_creation_requires_a_title(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'priority' => 3,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_task_priority_must_be_between_one_and_five(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Invalid priority task',
                'priority' => 6,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_project_owner_can_view_a_task(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $task = $project->tasks()->create([
            'title' => 'Review API',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', 'Review API');
    }

    public function test_project_owner_can_update_a_task(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $task = $project->tasks()->create([
            'title' => 'Old title',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Updated title',
                'priority' => 2,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.priority', 2);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'priority' => 2,
        ]);
    }

    public function test_project_owner_can_delete_a_task(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $task = $project->tasks()->create([
            'title' => 'Task to delete',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_user_cannot_access_task_through_another_project(): void
    {
        $user = User::factory()->create();

        $projectA = Project::create([
            'name' => 'Project A',
            'owner_id' => $user->id,
        ]);

        $projectB = Project::create([
            'name' => 'Project B',
            'owner_id' => $user->id,
        ]);

        $task = $projectA->tasks()->create([
            'title' => 'Task in A',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$projectB->id}/tasks/{$task->id}");

        $response->assertNotFound();
    }

    public function test_tasks_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $project->tasks()->create([
            'title' => 'Task to do',
            'status' => 'todo',
        ]);

        $project->tasks()->create([
            'title' => 'Completed task',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?status=todo");

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Task to do'])
            ->assertJsonMissing(['title' => 'Completed task']);
    }

    public function test_tasks_can_be_filtered_by_priority(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $project->tasks()->create([
            'title' => 'High priority task',
            'priority' => 1,
        ]);

        $project->tasks()->create([
            'title' => 'Low priority task',
            'priority' => 5,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?priority=1");

        $response->assertOk()
            ->assertJsonFragment(['title' => 'High priority task'])
            ->assertJsonMissing(['title' => 'Low priority task']);
    }

    public function test_tasks_can_be_sorted_and_paginated(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $project->tasks()->create([
            'title' => 'Priority five',
            'priority' => 5,
        ]);

        $project->tasks()->create([
            'title' => 'Priority one',
            'priority' => 1,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(
                "/api/projects/{$project->id}/tasks?sort_by=priority&sort_direction=asc&per_page=1"
            );

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Priority one')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_completed_task_cannot_be_moved_back_to_todo(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $user->id,
        ]);

        $task = $project->tasks()->create([
            'title' => 'Completed task',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson(
                "/api/projects/{$project->id}/tasks/{$task->id}",
                ['status' => 'todo']
            );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'done',
        ]);
    }

    public function test_non_member_cannot_be_assigned_to_project_task(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $project = Project::create([
            'name' => 'Test project',
            'owner_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Restricted task',
                'assignee_id' => $outsider->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['assignee_id']);

        $this->assertDatabaseMissing('tasks', [
            'project_id' => $project->id,
            'title' => 'Restricted task',
        ]);
    }

    public function test_project_member_can_be_assigned_to_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::create([
            'name' => 'Team project',
            'owner_id' => $owner->id,
        ]);

        $project->members()->attach($member->id);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Team task',
                'assignee_id' => $member->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.assignee.id', $member->id);
    }

    public function test_project_member_can_read_tasks_but_cannot_create_or_update_them(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::create([
            'name' => 'Team project',
            'owner_id' => $owner->id,
        ]);
        $project->members()->attach($member->id);
        $task = $project->tasks()->create([
            'title' => 'Existing task',
        ]);

        $this->actingAs($member, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks")
            ->assertOk()
            ->assertJsonFragment(['title' => 'Existing task']);

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Unauthorized task',
            ])
            ->assertForbidden();

        $this->actingAs($member, 'sanctum')
            ->patchJson("/api/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Changed by member',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Existing task',
        ]);
        $this->assertDatabaseMissing('tasks', [
            'project_id' => $project->id,
            'title' => 'Unauthorized task',
        ]);
    }
}
