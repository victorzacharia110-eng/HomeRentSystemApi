<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\CreatesTestData;
use App\Models\LatePaymentReason;

class LatePaymentReasonTest extends TestCase
{
    use CreatesTestData;

    public function test_fetch_late_payment_reasons(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room);
        LatePaymentReason::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'reason_text' => 'Could not pay on time',
        ]);

        $response = $this->getJson('/api/reasons/fetch');

        $response->assertOk()
            ->assertJsonStructure(['latePaymentReasons']);
    }

    public function test_create_late_payment_reason(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room);

        $response = $this->postJson('/api/reasons/create', [
            'payment_id' => $payment->id,
            'reason_text' => 'Hospital emergency',
        ]);

        $response->assertOk()
            ->assertJsonPath('latePaymentReason.reason_text', 'Hospital emergency');

        $this->assertDatabaseHas('late_payment_reasons', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'reason_text' => 'Hospital emergency',
        ]);
    }

    public function test_create_reason_validates_required_fields(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->postJson('/api/reasons/create', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_id', 'reason_text']);
    }

    public function test_create_reason_validates_payment_exists(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->postJson('/api/reasons/create', [
            'payment_id' => 99999,
            'reason_text' => 'Some reason',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_id']);
    }

    public function test_show_late_payment_reason(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room);
        $reason = LatePaymentReason::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'reason_text' => 'Test reason',
        ]);

        $response = $this->getJson("/api/reasons/show/{$reason->id}");

        $response->assertOk()
            ->assertJsonPath('latePaymentReason.reason_text', 'Test reason');
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $user = $this->createUser();
        $this->authenticateAs($user);

        $response = $this->getJson('/api/reasons/show/99999');

        $response->assertStatus(404);
    }

    public function test_update_late_payment_reason(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room);
        $reason = LatePaymentReason::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'reason_text' => 'Old reason',
        ]);

        $response = $this->patchJson("/api/reasons/update/{$reason->id}", [
            'reason_text' => 'Updated reason',
        ]);

        $response->assertOk()
            ->assertJsonPath('latePaymentReason.reason_text', 'Updated reason');

        $this->assertDatabaseHas('late_payment_reasons', [
            'id' => $reason->id,
            'reason_text' => 'Updated reason',
        ]);
    }

    public function test_delete_late_payment_reason(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room);
        $reason = LatePaymentReason::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'reason_text' => 'Delete me',
        ]);

        $response = $this->deleteJson("/api/reasons/delete/{$reason->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('late_payment_reasons', ['id' => $reason->id]);
    }

    public function test_fetch_supports_search(): void
    {
        $user = $this->createUser();
        $room = $this->createRoom();
        $this->authenticateAs($user);

        $payment = $this->createPayment($user, $room);
        LatePaymentReason::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'reason_text' => 'Medical issue',
        ]);
        LatePaymentReason::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'reason_text' => 'Travel delay',
        ]);

        $response = $this->getJson('/api/reasons/fetch?search=Medical');

        $response->assertOk();
        $reasons = $response->json('latePaymentReasons.data');
        $this->assertCount(1, $reasons);
    }
}
