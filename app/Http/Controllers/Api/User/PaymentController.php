<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // ✅ ADD THIS
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
            $tenant_payment = Payment::with("room")->where('room_id', '=', $user->room_id)->orderBy("id", "desc")->first();
            $count_tenant_unpaid_payment = Payment::with("room")->where("room_id", "=", $user->room_id)->where("status", "=", 'unpaid')->count();
            return response()->json(['tenant_payment' => $tenant_payment, 'count_tenant_unpaid_payment' => $count_tenant_unpaid_payment]);
        } else {
            $payments = Payment::with("room")->orderBy("id", "desc")->get();
            return response()->json(['payments' => $payments]);
        }
    }

    public function callback(Request $request)
    {

        Log::info('CALLBACK HIT', $request->all());
        $paymentId = $request->reference;

        if (!$paymentId) {
            return response()->json([
                'error' => 'Missing reference'
            ], 400);
        }

        $payment = Payment::find($paymentId);

        if (!$payment) {
            return response()->json([
                'error' => 'Payment not found',
                'reference' => $paymentId
            ], 404);
        }

        $payment->status = $request->status === 'success'
            ? 'paid'
            : 'failed';

        $payment->save();

        return response()->json([
            'message' => 'Callback processed',
            'payment' => $payment
        ]);
    }
    private function initiateClickPesaPayment($payment)
    {
        $clientId = env('CLICKPESA_CLIENT_ID');
        $apiKey = env('CLICKPESA_API_KEY');

        $response = Http::withHeaders([
            'Authorization' => "Bearer $apiKey",
            'Content-Type' => 'application/json'
        ])->post(env('CLICKPESA_BASE_URL') . '/payments', [
            'amount' => $payment->amount,
            'currency' => 'TZS',
            'reference' => $payment->id,
            'callback_url' => env('CLICKPESA_CALLBACK_URL'),
        ]);

        return $response->json();
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            "room_id" => "required|integer",
            "month" => "required|integer|min:1|max:12",
            "year" => "required|integer|min:2000",
            "amount" => "required|numeric|min:0",
            "due_date" => "required|date",
        ]);

        $payment = new Payment();
        $payment->user_id = auth()->id();
        $payment->room_id = $request->room_id;
        $payment->amount = $request->amount;
        $payment->month = $request->month;
        $payment->year = $request->year;
        $payment->due_date = $request->due_date;

        // ✅ ALWAYS PENDING
        $payment->status = 'pending';
        $payment->save();

        try {
            $response = $this->initiateClickPesaPayment($payment);

            return response()->json([
                'payment' => $payment,
                'gateway' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
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
        $payment = Payment::find($id);
        return response()->json($payment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::find($id);
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
        return response()->json(['payment' => $payment]);
    }
}
