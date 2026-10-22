<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_update_and_delete_project(): void
    {
        $owner = User::factory()->create();

        $project = Project::create([
            'name' => 'My project',
            'description' => 'Project description',
            'owner_id' => $owner->id,
        ]);

        $this->assertTrue($owner->can('view', $project));
        $this->assertTrue($owner->can('update', $project));
        $this->assertTrue($owner->can('delete', $project));
    }

    public function test_another_user_cannot_view_update_or_delete_project(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $project = Project::create([
            'name' => 'Private project',
            'description' => null,
            'owner_id' => $owner->id,
        ]);

        $this->assertFalse($anotherUser->can('view', $project));
        $this->assertFalse($anotherUser->can('update', $project));
        $this->assertFalse($anotherUser->can('delete', $project));
    }
}
