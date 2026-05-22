<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkspacePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspacePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_workspace_preference_routes(): void
    {
        $this->getJson('/api/workspace/preferences')->assertUnauthorized();
        $this->putJson('/api/workspace/preferences', [
            'settings' => [
                'theme' => 'light',
            ],
        ])->assertUnauthorized();
    }

    public function test_authenticated_users_only_receive_their_own_workspace_preferences(): void
    {
        $owner = User::factory()->create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Other User',
            'email' => 'other@example.com',
        ]);

        WorkspacePreference::query()->create([
            'user_id' => $owner->id,
            'profile' => [
                'companyName' => 'Owner Company',
            ],
            'settings' => [
                'compactMode' => true,
                'theme' => 'light',
            ],
        ]);

        WorkspacePreference::query()->create([
            'user_id' => $otherUser->id,
            'profile' => [
                'companyName' => 'Other Company',
            ],
            'settings' => [
                'compactMode' => false,
                'theme' => 'dark',
            ],
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/workspace/preferences')
            ->assertOk()
            ->assertJsonPath('data.user.id', $owner->id)
            ->assertJsonPath('data.profile.companyName', 'Owner Company')
            ->assertJsonPath('data.settings.compactMode', true)
            ->assertJsonMissing([
                'companyName' => 'Other Company',
            ]);
    }

    public function test_workspace_preferences_are_saved_for_the_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Original User',
            'email' => 'original@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/workspace/preferences', [
            'profile' => [
                'name' => 'Updated User',
                'email' => 'updated@example.com',
                'companyName' => 'Yono Labs',
                'jobTitle' => 'Team Lead',
                'avatar' => 'data:image/png;base64,abc123',
            ],
            'settings' => [
                'theme' => 'light',
                'compactMode' => true,
                'dailyDigestTime' => '09:30',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.profile.companyName', 'Yono Labs')
            ->assertJsonPath('data.settings.theme', 'light')
            ->assertJsonPath('data.settings.compactMode', true);

        $preference = WorkspacePreference::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($preference);
        $this->assertSame('Yono Labs', $preference->profile['companyName']);
        $this->assertSame('Team Lead', $preference->profile['jobTitle']);
        $this->assertSame('data:image/png;base64,abc123', $preference->profile['avatar']);
        $this->assertSame('light', $preference->settings['theme']);
        $this->assertTrue($preference->settings['compactMode']);
        $this->assertSame('09:30', $preference->settings['dailyDigestTime']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User',
            'email' => 'updated@example.com',
        ]);
    }
}
