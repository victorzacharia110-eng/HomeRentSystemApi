<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->is_landlord === 0) {
            // Get current month and year
            $currentMonth = now()->month;
            $currentYear = now()->year;

            // Get payment for current month (or latest if no current month)
            $tenant_payment = Payment::with("room")
                ->where('room_id', '=', $user->room_id)
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->first();

            // If no payment for current month, get the latest
            if (!$tenant_payment) {
                $tenant_payment = Payment::with("room")
                    ->where('room_id', '=', $user->room_id)
                    ->orderBy("id", "desc")
                    ->first();
            }

            $count_tenant_unpaid_payment = Payment::with("room")
                ->where("room_id", "=", $user->room_id)
                ->where("status", "=", 'unpaid')
                ->count();

            return response()->json([
                'tenant_payment' => $tenant_payment,
                'count_tenant_unpaid_payment' => $count_tenant_unpaid_payment
            ]);
        } else {
            $payments = Payment::with("room")->orderBy("id", "desc")->get();
            return response()->json(['payments' => $payments]);
        }
    }

    /**
     * CLICKPESA WEBHOOK CALLBACK
     * This is where ClickPesa sends payment confirmation
     */
    public function callback(Request $request)
    {
        $payload = $request->all();
        Log::info('CALLBACK HIT - Full Payload:', $payload);

        // Different payment gateways send different field names
        $paymentId = $payload['reference'] ?? $payload['order_reference'] ?? $payload['merchant_reference'] ?? null;
        $status = strtolower($payload['status'] ?? $payload['transaction_status'] ?? '');

        Log::info('Parsed callback data:', [
            'payment_id' => $paymentId,
            'status' => $status
        ]);

        if (!$paymentId) {
            Log::error('Callback missing reference', $payload);
            return response()->json(['error' => 'Missing reference'], 400);
        }

        $payment = Payment::find($paymentId);

        if (!$payment) {
            Log::error('Payment not found', ['payment_id' => $paymentId]);
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Update payment status based on callback
        $successStatuses = ['success', 'successful', 'paid', 'completed', 'complete'];

        if (in_array($status, $successStatuses)) {
            $payment->status = 'paid';
            $payment->paid_at = now();
            Log::info('Payment marked as PAID', ['payment_id' => $paymentId]);
        } else {
            $payment->status = 'failed';
            Log::warning('Payment marked as FAILED', ['payment_id' => $paymentId, 'status' => $status]);
        }

        // Store the full callback response for reference
        $payment->clickpesa_response = json_encode($payload);
        $payment->save();

        return response()->json([
            'message' => 'Callback processed successfully',
            'payment' => $payment
        ], 200);
    }

    /**
     * GET CLICKPESA JWT TOKEN
     * Exchange API Key and Client ID for a JWT token
     */
    private function getClickPesaToken()
    {
        $apiKey = env('CLICKPESA_API_KEY');
        $clientId = env('CLICKPESA_CLIENT_ID');

        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Getting JWT token...\n", FILE_APPEND);

        $response = Http::post('https://api.clickpesa.com/v1/auth/token', [
            'clientId' => $clientId,
            'apiKey' => $apiKey,
        ]);

        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Token Response Status: " . $response->status() . "\n", FILE_APPEND);
        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Token Response Body: " . $response->body() . "\n", FILE_APPEND);

        if (!$response->successful()) {
            throw new \Exception('Failed to get ClickPesa token: ' . $response->body());
        }

        $data = $response->json();
        return $data['access_token'];
    }

    /**
     * INITIATE CLICKPESA PAYMENT
     * This sends the payment request to ClickPesa
     */
    private function initiateClickPesaPayment($payment)
    {
        // Load relationships
        $payment->load('user', 'room');

        $user = $payment->user;

        if (!$user) {
            throw new \Exception('No user associated with this payment.');
        }

        // Get user's phone number
        $phoneNumber = $user->phone_number;

        if (!$phoneNumber) {
            throw new \Exception('User has no phone number. Please update your profile with a valid phone number.');
        }

        // Clean phone number (remove spaces, ensure correct format)
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // ClickPesa expects phone number in format: 255XXXXXXXXX (without leading 0 or +)
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '255' . substr($phoneNumber, 1);
        } elseif (substr($phoneNumber, 0, 3) === '255') {
            // Already in correct format
        } elseif (substr($phoneNumber, 0, 4) === '+255') {
            $phoneNumber = substr($phoneNumber, 1);
        }

        $baseUrl = env('CLICKPESA_BASE_URL', 'https://api.clickpesa.com');

        // Get JWT token first
        $token = $this->getClickPesaToken();

        // Debug logging
        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " === Starting ClickPesa Payment ===\n", FILE_APPEND);
        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Payment ID: {$payment->id}, Amount: {$payment->amount}, Phone: {$phoneNumber}\n", FILE_APPEND);
        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " JWT Token obtained (first 50 chars): " . substr($token, 0, 50) . "...\n", FILE_APPEND);

        // ✅ STEP 1: PREVIEW/VALIDATE (Optional but recommended)
        try {
            $previewPayload = [
                'amount' => (string) $payment->amount,
                'currency' => 'TZS',
                'orderReference' => (string) $payment->id,
                'phoneNumber' => $phoneNumber,
            ];

            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Step 1 - Preview Payload: " . json_encode($previewPayload) . "\n", FILE_APPEND);

            $previewResponse = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($baseUrl . '/third-parties/payments/preview-ussd-push-request', $previewPayload);

            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Preview Response Status: " . $previewResponse->status() . "\n", FILE_APPEND);
            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Preview Response Body: " . $previewResponse->body() . "\n", FILE_APPEND);

            if (!$previewResponse->successful()) {
                file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Preview failed but continuing...\n", FILE_APPEND);
            }
        } catch (\Exception $e) {
            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Preview Exception: " . $e->getMessage() . "\n", FILE_APPEND);
            // Continue anyway - preview is optional
        }

        // ✅ STEP 2: INITIATE ACTUAL USSD PAYMENT
        $payload = [
            'amount' => (string) $payment->amount,
            'currency' => 'TZS',
            'orderReference' => (string) $payment->id,
            'phoneNumber' => $phoneNumber,
        ];

        // Add optional fields if you have them
        if ($user->email) {
            $payload['email'] = $user->email;
        }
        if ($user->last_name) {
            $payload['customerName'] = $user->last_name;
        }

        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Step 2 - Initiate Payload: " . json_encode($payload) . "\n", FILE_APPEND);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($baseUrl . '/third-parties/payments/initiate-ussd-push-request', $payload);

            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Initiate Response Status: " . $response->status() . "\n", FILE_APPEND);
            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Initiate Response Body: " . $response->body() . "\n", FILE_APPEND);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['message'] ?? $errorBody['error'] ?? $response->body();
                throw new \Exception("ClickPesa API Error: {$errorMessage}");
            }

            $responseData = $response->json();

            // Store transaction ID if returned
            if (isset($responseData['id'])) {
                $payment->clickpesa_transaction_id = $responseData['id'];
                $payment->save();
                file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Transaction ID saved: {$responseData['id']}\n", FILE_APPEND);
            }

            // Store the full response
            $payment->clickpesa_response = json_encode($responseData);
            $payment->save();

            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " === Payment Initiated Successfully ===\n", FILE_APPEND);

            return $responseData;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Connection Error: " . $e->getMessage() . "\n", FILE_APPEND);
            Log::error('ClickPesa Connection Error:', ['error' => $e->getMessage()]);
            throw new \Exception('Could not connect to payment gateway. Please try again.');
        } catch (\Exception $e) {
            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " General Error: " . $e->getMessage() . "\n", FILE_APPEND);
            Log::error('ClickPesa General Error:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " === STORE METHOD STARTED ===\n", FILE_APPEND);

        $request->validate([
            "room_id" => "required|integer|exists:rooms,id",
            "month" => "required|integer|min:1|max:12",
            "year" => "required|integer|min:2000",
            "amount" => "required|numeric|min:0",
            "due_date" => "required|date",
        ]);

        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Validation passed\n", FILE_APPEND);

        $user = auth()->user();
        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " User ID: {$user->id}, Phone: " . ($user->phone_number ?? 'MISSING') . "\n", FILE_APPEND);

        // Check if user has phone number
        if (!$user->phone_number) {
            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " ERROR: Phone number missing\n", FILE_APPEND);
            return response()->json([
                'error' => 'Phone number required',
                'message' => 'Please update your profile with a valid phone number before making a payment.'
            ], 400);
        }

        // Check for existing pending payment
        $existingPending = Payment::where('room_id', $request->room_id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Pending payment exists: {$existingPending->id}\n", FILE_APPEND);
            return response()->json([
                'error' => 'Pending payment exists',
                'message' => 'You already have a pending payment for this period. Please wait for it to complete or contact support.',
                'payment_id' => $existingPending->id
            ], 409);
        }

        // Create payment record
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->room_id = $request->room_id;
        $payment->amount = $request->amount;
        $payment->month = $request->month;
        $payment->year = $request->year;
        $payment->due_date = $request->due_date;
        $payment->status = 'pending';
        $payment->save();

        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " Payment created with ID: {$payment->id}\n", FILE_APPEND);
        file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " About to call initiateClickPesaPayment...\n", FILE_APPEND);

        try {
            // Initiate ClickPesa payment
            $gatewayResponse = $this->initiateClickPesaPayment($payment);
            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " ClickPesa call SUCCESSFUL!\n", FILE_APPEND);

            return response()->json([
                'success' => true,
                'payment' => $payment,
                'gateway_response' => $gatewayResponse,
                'message' => 'Payment initiated! Check your phone for the USSD prompt from ClickPesa.'
            ], 201);
        } catch (\Exception $e) {
            file_put_contents('/tmp/clickpesa_debug.log', date('Y-m-d H:i:s') . " ClickPesa call FAILED: " . $e->getMessage() . "\n", FILE_APPEND);

            $payment->status = 'failed';
            $payment->save();

            return response()->json([
                'success' => false,
                'error' => 'Payment initiation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment = Payment::with('room')->find($id);

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        return response()->json(['payment' => $payment]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);
        $payment->update($request->all());
        return response()->json(['payment' => $payment]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();
        return response()->json(['message' => 'Payment deleted successfully']);
    }

    /**
     * Check payment status manually
     */
    public function checkStatus(string $paymentId)
    {
        $payment = Payment::find($paymentId);

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        return response()->json([
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'paid_at' => $payment->paid_at,
            'transaction_id' => $payment->clickpesa_transaction_id
        ]);
    }
}
