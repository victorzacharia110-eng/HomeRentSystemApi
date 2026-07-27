<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\CreatesTestData;
use App\Models\User;

class AdminTest extends TestCase
{
    use CreatesTestData;

    public function test_super_admin_can_fetch_landlords(): void
    {
        $admin = $this->createSuperAdmin();
        $landlord = $this->createLandlord(['email' => 'll@example.com']);
        $this->authenticateAs($admin);

        $response = $this->getJson('/api/admin/landlords');

        $response->assertOk()
            ->assertJsonStructure(['landlords']);
    }

    public function test_super_admin_can_fetch_tenants(): void
    {
        $admin = $this->createSuperAdmin();
        $tenant = $this->createUser(['email' => 'ten@example.com']);
        $this->authenticateAs($admin);

        $response = $this->getJson('/api/admin/tenants');

        $response->assertOk()
            ->assertJsonStructure(['tenants']);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->getJson('/api/admin/landlords');

        $response->assertStatus(403);
    }

    public function test_landlord_cannot_access_admin_routes(): void
    {
        $landlord = $this->createLandlord();
        $this->authenticateAs($landlord);

        $response = $this->getJson('/api/admin/tenants');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_admin_routes(): void
    {
        $response = $this->getJson('/api/admin/landlords');

        $response->assertStatus(401);
    }

    public function test_super_admin_can_reset_user_password(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createUser(['email' => 'resetme@example.com']);
        $this->authenticateAs($admin);

        $response = $this->patchJson("/api/admin/user/{$user->id}/reset-password", [
            'new_password' => 'newpass123',
            'new_password_confirmation' => 'newpass123',
        ]);

        $response->assertOk()
            ->assertJson(['message' => "Password reset successfully for {$user->email}"]);

        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpass123', $user->password));
    }

    public function test_reset_password_validates_min_length(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createUser();
        $this->authenticateAs($admin);

        $response = $this->patchJson("/api/admin/user/{$user->id}/reset-password", [
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_super_admin_can_toggle_landlord_status(): void
    {
        $admin = $this->createSuperAdmin();
        $landlord = $this->createLandlord(['email' => 'toggle@example.com', 'is_active' => true]);
        $this->authenticateAs($admin);

        $response = $this->patchJson("/api/admin/landlord/{$landlord->id}/toggle-status");

        $response->assertOk()
            ->assertJson(['message' => 'Landlord deactivated successfully']);

        $this->assertDatabaseHas('users', ['id' => $landlord->id, 'is_active' => false]);
    }

    public function test_toggle_landlord_rejects_non_landlord(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createUser(['email' => 'notll@example.com']);
        $this->authenticateAs($admin);

        $response = $this->patchJson("/api/admin/landlord/{$user->id}/toggle-status");

        $response->assertStatus(400)
            ->assertJson(['message' => 'User is not a landlord']);
    }

    public function test_super_admin_can_toggle_user_status(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createUser(['email' => 'toggleme@example.com', 'is_active' => true]);
        $this->authenticateAs($admin);

        $response = $this->patchJson("/api/admin/user/{$user->id}/toggle-status");

        $response->assertOk()
            ->assertJson(['message' => 'User deactivated successfully']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    public function test_toggle_user_can_reactivate(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createUser(['email' => 'reactivate@example.com', 'is_active' => false]);
        $this->authenticateAs($admin);

        $response = $this->patchJson("/api/admin/user/{$user->id}/toggle-status");

        $response->assertOk()
            ->assertJson(['message' => 'User activated successfully']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => true]);
    }

    public function test_super_admin_search_landlords(): void
    {
        $admin = $this->createSuperAdmin();
        $this->createLandlord(['email' => 'searchable@example.com', 'last_name' => 'SearchableLL']);
        $this->createLandlord(['email' => 'other@example.com', 'last_name' => 'OtherLL']);
        $this->authenticateAs($admin);

        $response = $this->getJson('/api/admin/landlords?search=Searchable');

        $response->assertOk();
        $landlords = $response->json('landlords.data');
        $this->assertCount(1, $landlords);
        $this->assertEquals('SearchableLL', $landlords[0]['last_name']);
    }

    public function test_super_admin_search_tenants(): void
    {
        $admin = $this->createSuperAdmin();
        $this->createUser(['email' => 'findme@example.com', 'last_name' => 'FindMe']);
        $this->createUser(['email' => 'hidden@example.com', 'last_name' => 'Hidden']);
        $this->authenticateAs($admin);

        $response = $this->getJson('/api/admin/tenants?search=FindMe');

        $response->assertOk();
        $tenants = $response->json('tenants.data');
        $this->assertCount(1, $tenants);
    }

    public function test_non_super_admin_field_rejects_access(): void
    {
        $admin = $this->createUser([
            'email' => 'fakeadmin@example.com',
            'is_landlord' => true,
            'is_super_admin' => false,
        ]);
        $this->authenticateAs($admin);

        $response = $this->getJson('/api/admin/landlords');

        $response->assertStatus(403);
    }
}
