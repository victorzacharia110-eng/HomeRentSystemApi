<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\CreatesTestData;
use App\Models\Room;
use App\Models\User;
use App\Models\Payment;
use App\Models\RoomSelection;

class PaymentTest extends TestCase
{
    use CreatesTestData;

    public function test_tenant_can_fetch_own_payment(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room);

        $response = $this->getJson('/api/payment/fetch');

        $response->assertOk()
            ->assertJsonStructure(['tenant_payment', 'count_tenant_unpaid_payment']);
    }

    public function test_landlord_can_fetch_all_payments(): void
    {
        $landlord = $this->createLandlord();
        $this->authenticateAs($landlord);

        $response = $this->getJson('/api/payment/fetch');

        $response->assertOk()
            ->assertJsonStructure(['payments']);
    }

    public function test_show_payment(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room);

        $response = $this->getJson("/api/payment/show/{$payment->id}");

        $response->assertOk()
            ->assertJsonPath('payment.id', $payment->id);
    }

    public function test_show_nonexistent_payment_returns_404(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->getJson('/api/payment/show/99999');

        $response->assertStatus(404);
    }

    public function test_delete_payment(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room);

        $response = $this->deleteJson("/api/payment/delete/{$payment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_check_payment_status(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room, ['status' => 'pending']);

        $response = $this->getJson("/api/payment/status/{$payment->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('payment_id', $payment->id);
    }

    public function test_check_status_nonexistent_returns_404(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->getJson('/api/payment/status/99999');

        $response->assertStatus(404);
    }

    // --- Room Selection Flow ---

    public function test_tenant_can_select_available_room(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom(['status' => 'Available', 'room_price' => 200000]);
        $this->authenticateAs($user);

        $response = $this->postJson("/api/room/select/{$room->id}", [
            'payment_method' => 'manual',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'payment' => ['id', 'status', 'payment_method'],
                'room' => ['id', 'room_number'],
            ])
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.payment_method', 'manual');

        $this->assertDatabaseHas('room_selections', [
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_select_occupied_room(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom(['status' => 'Occupied']);
        $this->authenticateAs($user);

        $response = $this->postJson("/api/room/select/{$room->id}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'This room is already occupied']);
    }

    public function test_cannot_select_room_twice(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom(['status' => 'Available']);
        $this->authenticateAs($user);

        $this->postJson("/api/room/select/{$room->id}");

        $response = $this->postJson("/api/room/select/{$room->id}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'You already have a pending selection for this room']);
    }

    public function test_select_room_with_clickpesa_method(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom(['status' => 'Available']);
        $this->authenticateAs($user);

        $response = $this->postJson("/api/room/select/{$room->id}", [
            'payment_method' => 'clickpesa',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('payment.payment_method', 'clickpesa');
    }

    public function test_select_nonexistent_room_returns_404(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->postJson('/api/room/select/99999');

        $response->assertStatus(404);
    }

    // --- Landlord Confirm Payment ---

    public function test_landlord_can_confirm_manual_payment(): void
    {
        $landlord = $this->createLandlord();
        $tenant = $this->createUser(['email' => 'tenant@example.com']);
        $room = $this->createRoom(['status' => 'Available']);
        $this->authenticateAs($landlord);

        $payment = $this->createPayment($tenant, $room, [
            'payment_method' => 'manual',
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/payment/confirm/{$payment->id}", [
            'confirmation_message' => 'Cash received',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Payment confirmed and room assigned successfully');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'confirmed_by_landlord' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $tenant->id,
            'room_id' => $room->id,
        ]);
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'Occupied',
        ]);
    }

    public function test_tenant_cannot_confirm_payment(): void
    {
        $tenant = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($tenant);

        $payment = $this->createPayment($tenant, $room);

        $response = $this->patchJson("/api/payment/confirm/{$payment->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_cannot_confirm_already_confirmed_payment(): void
    {
        $landlord = $this->createLandlord();
        $tenant = $this->createUser(['email' => 'tenant2@example.com']);
        $room = $this->createRoom();
        $this->authenticateAs($landlord);

        $payment = $this->createPayment($tenant, $room, [
            'confirmed_by_landlord' => true,
        ]);

        $response = $this->patchJson("/api/payment/confirm/{$payment->id}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Payment already confirmed']);
    }

    public function test_confirm_nonexistent_payment_returns_404(): void
    {
        $landlord = $this->createLandlord();
        $this->authenticateAs($landlord);

        $response = $this->patchJson('/api/payment/confirm/99999');

        $response->assertStatus(404);
    }

    // --- Unconfirmed Payments ---

    public function test_landlord_can_fetch_unconfirmed_payments(): void
    {
        $landlord = $this->createLandlord();
        $tenant = $this->createUser(['email' => 'unconf@example.com']);
        $room = $this->createRoom();
        $this->authenticateAs($landlord);

        $this->createPayment($tenant, $room, [
            'payment_method' => 'manual',
            'status' => 'pending',
            'confirmed_by_landlord' => false,
        ]);

        $response = $this->getJson('/api/payment/unconfirmed');

        $response->assertOk()
            ->assertJsonStructure(['unconfirmed_payments']);

        $payments = $response->json('unconfirmed_payments');
        $this->assertGreaterThanOrEqual(1, count($payments));
    }

    public function test_unconfirmed_payments_only_returns_manual_pending(): void
    {
        $landlord = $this->createLandlord();
        $tenant = $this->createUser(['email' => 'unconf2@example.com']);
        $room = $this->createRoom();
        $this->authenticateAs($landlord);

        // Create a clickpesa payment (should NOT appear)
        $this->createPayment($tenant, $room, [
            'payment_method' => 'clickpesa',
            'status' => 'pending',
            'confirmed_by_landlord' => false,
        ]);

        // Create an already confirmed payment (should NOT appear)
        $this->createPayment($tenant, $room, [
            'payment_method' => 'manual',
            'status' => 'paid',
            'confirmed_by_landlord' => true,
        ]);

        // Create the correct type (should appear)
        $this->createPayment($tenant, $room, [
            'payment_method' => 'manual',
            'status' => 'pending',
            'confirmed_by_landlord' => false,
        ]);

        $response = $this->getJson('/api/payment/unconfirmed');

        $response->assertOk();
        $payments = $response->json('unconfirmed_payments');
        $this->assertCount(1, $payments);
    }

    // --- ClickPesa Callback ---

    public function test_clickpesa_callback_marks_paid(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();

        $payment = $this->createPayment($user, $room, [
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/payment/callback', [
            'reference' => (string) $payment->id,
            'status' => 'success',
        ]);

        $response->assertOk()
            ->assertJsonPath('payment.status', 'paid');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }

    public function test_clickpesa_callback_marks_failed(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();

        $payment = $this->createPayment($user, $room);

        $response = $this->postJson('/api/payment/callback', [
            'reference' => (string) $payment->id,
            'status' => 'failed',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
    }

    public function test_clickpesa_callback_auto_assigns_room_on_success(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom(['status' => 'Available']);

        $payment = $this->createPayment($user, $room, [
            'status' => 'pending',
            'room_id' => $room->id,
        ]);

        $response = $this->postJson('/api/payment/callback', [
            'reference' => (string) $payment->id,
            'status' => 'completed',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'room_id' => $room->id]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'Occupied']);
    }

    public function test_clickpesa_callback_missing_reference_returns_400(): void
    {
        $response = $this->postJson('/api/payment/callback', [
            'status' => 'success',
        ]);

        $response->assertStatus(400);
    }

    public function test_clickpesa_callback_nonexistent_payment_returns_404(): void
    {
        $response = $this->postJson('/api/payment/callback', [
            'reference' => '99999',
            'status' => 'success',
        ]);

        $response->assertStatus(404);
    }

    // --- Full Flow: Select Room -> Manual Confirm -> Room Assigned ---

    public function test_full_room_selection_manual_payment_flow(): void
    {
        $landlord = $this->createLandlord();
        $tenant = $this->createUser([
            'email' => 'flow-test@example.com',
            'phone_number' => '0712345678',
        ]);
        $room = $this->createRoom(['status' => 'Available', 'room_price' => 180000]);

        // Step 1: Tenant selects room
        $this->authenticateAs($tenant);
        $selectResponse = $this->postJson("/api/room/select/{$room->id}", [
            'payment_method' => 'manual',
        ]);
        $selectResponse->assertStatus(201);
        $paymentId = $selectResponse->json('payment.id');

        // Step 2: Landlord checks unconfirmed payments
        $this->authenticateAs($landlord);
        $unconfResponse = $this->getJson('/api/payment/unconfirmed');
        $unconfResponse->assertOk();
        $unconfirmed = collect($unconfResponse->json('unconfirmed_payments'));
        $this->assertTrue($unconfirmed->contains('id', $paymentId));

        // Step 3: Landlord confirms payment
        $confirmResponse = $this->patchJson("/api/payment/confirm/{$paymentId}", [
            'confirmation_message' => 'Paid in cash at office',
        ]);
        $confirmResponse->assertOk();

        // Step 4: Verify room is assigned
        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'status' => 'paid']);
        $this->assertDatabaseHas('users', ['id' => $tenant->id, 'room_id' => $room->id]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'Occupied']);
    }

    // --- Full Flow: Select Room -> ClickPesa Callback -> Room Assigned ---

    public function test_full_room_selection_clickpesa_payment_flow(): void
    {
        $tenant = $this->createUser([
            'email' => 'clickpesa-flow@example.com',
            'phone_number' => '0712345678',
        ]);
        $room = $this->createRoom(['status' => 'Available', 'room_price' => 150000]);

        // Step 1: Tenant selects room with clickpesa
        $this->authenticateAs($tenant);
        $selectResponse = $this->postJson("/api/room/select/{$room->id}", [
            'payment_method' => 'clickpesa',
        ]);
        $selectResponse->assertStatus(201);
        $paymentId = $selectResponse->json('payment.id');

        // Step 2: ClickPesa sends success callback
        $callbackResponse = $this->postJson('/api/payment/callback', [
            'reference' => (string) $paymentId,
            'status' => 'success',
        ]);
        $callbackResponse->assertOk();

        // Step 3: Verify room auto-assigned
        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'status' => 'paid']);
        $this->assertDatabaseHas('users', ['id' => $tenant->id, 'room_id' => $room->id]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'Occupied']);
    }
}
