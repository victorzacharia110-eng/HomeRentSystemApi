<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\CreatesTestData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use CreatesTestData;

    public function test_health_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => '🚀 API is working!',
            ]);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/user/create', [
            'last_name' => 'NewUser',
            'phone_number' => '0712345678',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'last_name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_user_registration_validates_required_fields(): void
    {
        $response = $this->postJson('/api/user/create', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name', 'phone_number', 'email', 'password']);
    }

    public function test_user_registration_rejects_duplicate_email(): void
    {
        $this->createUser(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/user/create', [
            'last_name' => 'Dupe',
            'phone_number' => '0712345678',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createUser([
            'email' => 'login@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/user/auth', [
            'email' => 'login@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'email'],
                'token',
            ])
            ->assertJson(['message' => 'Login successful!']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->createUser([
            'email' => 'login@example.com',
            'password' => 'correct_password',
        ]);

        $response = $this->postJson('/api/user/auth', [
            'email' => 'login@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_rejects_nonexistent_email(): void
    {
        $response = $this->postJson('/api/user/auth', [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/user/auth', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_replaces_old_tokens(): void
    {
        $user = $this->createUser([
            'email' => 'token@example.com',
            'password' => 'secret1234',
        ]);

        $token1 = $user->createToken('old-token')->plainTextToken;

        $response = $this->postJson('/api/user/auth', [
            'email' => 'token@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => explode('|', $token1)[0]]);

        $newToken = $response->json('token');
        $this->assertDatabaseHas('personal_access_tokens', ['id' => explode('|', $newToken)[0]]);
    }

    public function test_user_can_logout(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->postJson('/api/user/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Logged out successfully']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/user/logout');

        $response->assertStatus(401);
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        $this->createUser(['email' => 'forgot@example.com']);

        $response = $this->postJson('/api/user/forgot-password', [
            'email' => 'forgot@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Reset link sent to your email.']);
    }

    public function test_forgot_password_validates_email(): void
    {
        $response = $this->postJson('/api/user/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
