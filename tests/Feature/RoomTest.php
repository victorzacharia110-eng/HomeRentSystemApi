<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\CreatesTestData;
use App\Models\Room;

class RoomTest extends TestCase
{
    use CreatesTestData;

    public function test_fetch_rooms_returns_paginated_list(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        Room::create([
            'room_number' => '101',
            'type' => 'Single',
            'status' => 'Available',
            'room_price' => 100000,
        ]);

        $response = $this->getJson('/api/room/fetch');

        $response->assertOk()
            ->assertJsonStructure([
                'rooms',
                'totalRooms',
                'roomsAvailableCount',
                'roomsOccupiedCount',
                'roomsMaintananceCount',
            ]);
    }

    public function test_fetch_rooms_includes_counts(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        Room::create(['room_number' => 'A1', 'type' => 'Single', 'status' => 'Available', 'room_price' => 100000]);
        Room::create(['room_number' => 'A2', 'type' => 'Single', 'status' => 'Available', 'room_price' => 100000]);
        Room::create(['room_number' => 'B1', 'type' => 'Double', 'status' => 'Occupied', 'room_price' => 200000]);

        $response = $this->getJson('/api/room/fetch');

        $response->assertOk()
            ->assertJsonPath('totalRooms', 3)
            ->assertJsonPath('roomsAvailableCount', 2)
            ->assertJsonPath('roomsOccupiedCount', 1);
    }

    public function test_fetch_rooms_supports_search(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        Room::create(['room_number' => 'VIP-100', 'type' => 'Suite', 'status' => 'Available', 'room_price' => 500000]);
        Room::create(['room_number' => 'STD-200', 'type' => 'Single', 'status' => 'Available', 'room_price' => 100000]);

        $response = $this->getJson('/api/room/fetch?search=VIP');

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data['rooms']['data']);
    }

    public function test_user_can_create_room(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->postJson('/api/room/create', [
            'room_number' => 'NEW-1',
            'type' => 'Studio',
            'status' => 'Available',
            'room_price' => 250000,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['room' => ['id', 'room_number', 'type', 'status']]);

        $this->assertDatabaseHas('rooms', ['room_number' => 'NEW-1']);
    }

    public function test_create_room_validates_required_fields(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->postJson('/api/room/create', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['room_number', 'type', 'status']);
    }

    public function test_user_can_update_room(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $room = $this->createRoom(['room_number' => 'OLD-1']);

        $response = $this->patchJson("/api/room/update/{$room->id}", [
            'room_number' => 'NEW-1',
            'type' => 'Double',
            'status' => 'Available',
            'room_price' => 300000,
        ]);

        $response->assertOk()
            ->assertJsonPath('room.room_number', 'NEW-1');

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'room_number' => 'NEW-1']);
    }

    public function test_user_can_update_room_status(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $room = $this->createRoom(['status' => 'Available']);

        $response = $this->patchJson("/api/room/update/status/{$room->id}", [
            'status' => 'Occupied',
        ]);

        $response->assertOk()
            ->assertJsonPath('room.status', 'Occupied');

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'Occupied']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'room_id' => $room->id]);
    }

    public function test_update_room_status_validates_status_values(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $room = $this->createRoom();

        $response = $this->patchJson("/api/room/update/status/{$room->id}", [
            'status' => 'InvalidStatus',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_user_can_delete_room(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $room = $this->createRoom();

        $response = $this->deleteJson("/api/room/delete/{$room->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_fetch_room_show(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $room = $this->createRoom();

        $response = $this->getJson("/api/room/show/{$room->id}");

        $response->assertOk()
            ->assertJsonPath('room.id', $room->id);
    }

    public function test_unauthenticated_user_cannot_fetch_rooms(): void
    {
        $response = $this->getJson('/api/room/fetch');

        $response->assertStatus(401);
    }
}
