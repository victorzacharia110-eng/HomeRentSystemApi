<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\CreatesTestData;
use App\Models\User;
use App\Models\Room;

class UserTest extends TestCase
{
    use CreatesTestData;

    public function test_tenant_can_fetch_own_profile(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->getJson('/api/user/fetch');

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_landlord_can_fetch_users_list(): void
    {
        $landlord = $this->createLandlord();
        $tenant = $this->createUser(['email' => 'tenant@example.com']);
        $this->authenticateAs($landlord);

        $response = $this->getJson('/api/user/fetch');

        $response->assertOk()
            ->assertJsonStructure(['user', 'users']);
    }

    public function test_unauthenticated_user_cannot_fetch(): void
    {
        $response = $this->getJson('/api/user/fetch');

        $response->assertStatus(401);
    }

    public function test_user_can_update_profile(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->patchJson("/api/user/update/{$user->id}", [
            'last_name' => 'UpdatedName',
            'email' => 'updated@example.com',
            'phone_number' => '0799999999',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.last_name', 'UpdatedName')
            ->assertJsonPath('user.email', 'updated@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'last_name' => 'UpdatedName',
        ]);
    }

    public function test_user_update_validates_email_unique(): void
    {
        $user1 = $this->createUser(['email' => 'user1@example.com']);
        $user2 = $this->createUser(['email' => 'user2@example.com']);
        $this->authenticateAs($user1);

        $response = $this->patchJson("/api/user/update/{$user1->id}", [
            'email' => 'user2@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_update_allows_same_email(): void
    {
        $user = $this->createUser(['email' => 'same@example.com']);
        $this->authenticateAs($user);

        $response = $this->patchJson("/api/user/update/{$user->id}", [
            'email' => 'same@example.com',
            'last_name' => 'Changed',
        ]);

        $response->assertOk();
    }

    public function test_user_update_returns_404_for_nonexistent_user(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->patchJson('/api/user/update/99999', [
            'last_name' => 'Ghost',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_can_update_phone_number(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->patchJson("/api/user/update/phone/{$user->id}", [
            'phone_number' => '0788888888',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.phone_number', '0788888888');
    }

    public function test_user_can_be_deleted(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->deleteJson("/api/user/delete/{$user->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_delete_nonexistent_user_returns_404(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->deleteJson('/api/user/delete/99999');

        $response->assertStatus(404);
    }

    public function test_user_show_returns_user(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->getJson("/api/user/show/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }
}
