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

    public function test_user_can_see_owned_and_member_projects_but_not_unrelated_projects(): void
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

    public function test_owner_can_add_and_remove_project_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::create(['name' => 'Team project', 'owner_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/projects/{$project->id}/members/{$member->id}")
            ->assertCreated()
            ->assertJsonPath('member.id', $member->id);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);

        $this->actingAs($member, 'sanctum')
            ->getJson("/api/projects/{$project->id}")
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_non_owner_cannot_manage_project_members(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $newMember = User::factory()->create();
        $project = Project::create(['name' => 'Private project', 'owner_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/projects/{$project->id}/members/{$newMember->id}")
            ->assertForbidden();

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}/members/{$newMember->id}")
            ->assertForbidden();
    }

    public function test_members_can_see_project_in_their_project_list(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::create(['name' => 'Shared project', 'owner_id' => $owner->id]);
        $project->members()->attach($member->id);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/projects')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Shared project']);
    }
}
