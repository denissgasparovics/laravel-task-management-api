<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/projects', [
                'name' => 'My project',
                'description' => 'Project description',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'My project')
            ->assertJsonPath('data.owner_id', $user->id);

        $this->assertDatabaseHas('projects', [
            'name' => 'My project',
            'owner_id' => $user->id,
        ]);
    }

    public function test_user_can_only_see_their_own_projects(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $ownProject = Project::create([
            'name' => 'Own project',
            'owner_id' => $user->id,
        ]);

        Project::create([
            'name' => 'Other project',
            'owner_id' => $anotherUser->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects');

        $response
            ->assertOk()
            ->assertJsonFragment(['name' => 'Own project'])
            ->assertJsonMissing(['name' => 'Other project']);
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::create([
            'name' => 'Private project',
            'owner_id' => $owner->id,
        ]);

        $response = $this->actingAs($anotherUser, 'sanctum')
            ->getJson("/api/projects/{$project->id}");

        $response->assertForbidden();
    }

    public function test_user_cannot_update_or_delete_another_users_project(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::create([
            'name' => 'Original name',
            'owner_id' => $owner->id,
        ]);

        $this->actingAs($anotherUser, 'sanctum')
            ->patchJson("/api/projects/{$project->id}", [
                'name' => 'Changed name',
            ])
            ->assertForbidden();

        $this->actingAs($anotherUser, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Original name',
            'owner_id' => $owner->id,
        ]);
    }
}
