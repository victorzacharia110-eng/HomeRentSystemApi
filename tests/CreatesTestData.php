<?php

namespace Tests;

use App\Models\User;
use App\Models\Room;
use App\Models\Payment;
use App\Models\RoomSelection;
use App\Models\LatePaymentReason;
use App\Models\RoomPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

trait CreatesTestData
{
    use RefreshDatabase;

    protected function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'last_name' => 'TestUser',
            'phone_number' => '0712345678',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_landlord' => false,
            'is_super_admin' => false,
            'is_active' => true,
        ], $overrides));
    }

    protected function createLandlord(array $overrides = []): User
    {
        return $this->createUser(array_merge([
            'last_name' => 'Landlord',
            'email' => 'landlord@example.com',
            'is_landlord' => true,
        ], $overrides));
    }

    protected function createSuperAdmin(array $overrides = []): User
    {
        return $this->createUser(array_merge([
            'last_name' => 'SuperAdmin',
            'email' => 'admin@example.com',
            'is_super_admin' => true,
            'is_landlord' => true,
        ], $overrides));
    }

    protected function createRoom(array $overrides = []): Room
    {
        return Room::create(array_merge([
            'room_number' => 'A1',
            'type' => 'Single',
            'status' => 'Available',
            'room_price' => 150000.00,
        ], $overrides));
    }

    protected function createPayment(User $user, Room $room, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'month' => now()->month,
            'year' => now()->year,
            'amount' => $room->room_price,
            'status' => 'pending',
            'due_date' => now()->addDays(7)->toDateString(),
            'payment_method' => 'manual',
            'confirmed_by_landlord' => false,
            'room_selected' => false,
        ], $overrides));
    }

    protected function authenticateAs(User $user): void
    {
        Sanctum::actingAs($user);
    }
}
