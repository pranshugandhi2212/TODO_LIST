<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_config_returns_the_public_client_id(): void
    {
        config()->set('services.google.client_id', 'frontend-google-client-id');

        $this->getJson('/api/auth/google/config')
            ->assertOk()
            ->assertJsonPath('client_id', 'frontend-google-client-id')
            ->assertJsonPath('configured', true);
    }

    public function test_google_login_creates_a_unique_user_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/auth/google', [
            'name' => 'Alice Example',
            'email' => 'alice@example.com',
            'google_id' => 'google-user-123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.email', 'alice@example.com')
            ->assertJsonPath('user.google_id', 'google-user-123');

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
            'google_id' => 'google-user-123',
        ]);
    }

    public function test_google_login_reuses_the_same_user_for_the_same_google_account(): void
    {
        $firstResponse = $this->postJson('/api/auth/google', [
            'name' => 'Alice Example',
            'email' => 'alice@example.com',
            'google_id' => 'google-user-123',
        ]);

        $existingUserId = $firstResponse->json('user.id');

        $secondResponse = $this->postJson('/api/auth/google', [
            'name' => 'Alice Updated',
            'email' => 'alice@example.com',
            'google_id' => 'google-user-123',
        ]);

        $secondResponse
            ->assertOk()
            ->assertJsonPath('user.id', $existingUserId)
            ->assertJsonPath('user.name', 'Alice Updated');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_login_rejects_a_second_google_account_for_the_same_email(): void
    {
        User::factory()->create([
            'email' => 'alice@example.com',
            'google_id' => 'google-user-123',
        ]);

        $this->postJson('/api/auth/google', [
            'name' => 'Mallory',
            'email' => 'alice@example.com',
            'google_id' => 'google-user-456',
        ])->assertStatus(409);
    }

    public function test_google_login_accepts_google_credential_payload_and_stores_user(): void
    {
        config()->set('services.google.client_id', 'frontend-google-client-id');

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-user-999',
                'email' => 'credential@example.com',
                'email_verified' => 'true',
                'name' => 'Credential User',
                'picture' => 'https://example.com/avatar.png',
                'aud' => 'frontend-google-client-id',
                'iss' => 'https://accounts.google.com',
            ], 200),
        ]);

        $response = $this->postJson('/api/auth/google', [
            'credential' => 'mock-google-jwt',
            'clientId' => 'frontend-google-client-id',
            'intent' => 'register',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.email', 'credential@example.com')
            ->assertJsonPath('user.google_id', 'google-user-999')
            ->assertJsonPath('user.avatar', 'https://example.com/avatar.png');

        $this->assertDatabaseHas('users', [
            'email' => 'credential@example.com',
            'google_id' => 'google-user-999',
            'name' => 'Credential User',
        ]);

        Http::assertSentCount(1);
    }

    public function test_google_login_rejects_credential_with_wrong_client_id(): void
    {
        config()->set('services.google.client_id', 'expected-google-client-id');

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-user-999',
                'email' => 'credential@example.com',
                'email_verified' => 'true',
                'name' => 'Credential User',
                'aud' => 'another-client-id',
                'iss' => 'https://accounts.google.com',
            ], 200),
        ]);

        $this->postJson('/api/auth/google', [
            'credential' => 'mock-google-jwt',
            'clientId' => 'another-client-id',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Google account verification failed. Please use a valid Google account.');
    }
}
