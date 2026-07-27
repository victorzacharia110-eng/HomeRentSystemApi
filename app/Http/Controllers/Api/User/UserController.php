<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use App\Models\RoomSelection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = User::with(['room.latestPayment','criticalRemarks'])->where('id', '=', auth()->user()->id)->first();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->is_landlord === 1) {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search', '');
            $usersQuery = User::with(['room.latestPayment','criticalRemarks'])
                ->where('is_landlord', '=', 0);
            if ($search) {
                $usersQuery->where(function($q) use ($search) {
                    $q->where('last_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            $users = $usersQuery->paginate($perPage);

            return response()->json([
                'user' => $user,
                'users' => $users
            ]);
        } else {
            return response()->json([
                'user' => $user,
            ]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'last_name' => 'required|string|max:50',
            'phone_number' => 'required|string|regex:/^\+?[0-9]{7,15}$/',
            'email' => 'required|string|email|max:100|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'user' => $user
        ]);
    }

    public function show(string $id)
    {
        $user = User::find($id);
        return response()->json(['user' => $user]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'last_name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone_number' => 'sometimes|string',
        ]);

        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $data = $request->only(['last_name', 'email', 'phone_number']);
        $user->update($data);

        return response()->json(['user' => $user]);
    }

    public function updatePhoneNumber(Request $request, string $id)
    {
        $request->validate([
            'phone_number' => 'required|string'
        ]);
        $user = User::find($id);
        $user->update([
            'phone_number' => $request->phone_number
        ]);
        return response()->json(['user' => $user]);
    }

    public function selectRoom(Request $request, string $roomId)
    {
        $user = auth()->user();
        $room = \App\Models\Room::findOrFail($roomId);

        if ($room->status === 'Occupied') {
            return response()->json(['message' => 'This room is already occupied'], 400);
        }

        $existingSelection = RoomSelection::where('user_id', $user->id)
            ->where('room_id', $roomId)
            ->first();

        if ($existingSelection) {
            return response()->json(['message' => 'You already have a pending selection for this room'], 400);
        }

        RoomSelection::create([
            'user_id' => $user->id,
            'room_id' => $roomId,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'room_id' => $roomId,
            'month' => now()->month,
            'year' => now()->year,
            'amount' => $room->room_price,
            'status' => 'pending',
            'due_date' => now()->addDays(7),
            'payment_method' => $request->payment_method ?? 'clickpesa',
        ]);

        return response()->json([
            'message' => 'Room selection pending. Please complete payment.',
            'payment' => $payment,
            'room' => $room,
        ], 201);
    }

    public function confirmPayment(Request $request, string $paymentId)
    {
        $user = auth()->user();
        if ($user->is_landlord !== 1) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payment = Payment::with(['user', 'room'])->findOrFail($paymentId);

        if ($payment->confirmed_by_landlord) {
            return response()->json(['message' => 'Payment already confirmed'], 400);
        }

        $payment->status = 'paid';
        $payment->confirmed_by_landlord = true;
        $payment->paid_at = now();
        $payment->room_selected = true;
        $payment->confirmation_message = $request->confirmation_message ?? "Payment confirmed by landlord";
        $payment->save();

        $tenant = $payment->user;
        $tenant->room_id = $payment->room_id;
        $tenant->save();

        $room = $payment->room;
        $room->status = 'Occupied';
        $room->user_id = $tenant->id;
        $room->save();

        RoomSelection::where('user_id', $tenant->id)->delete();

        return response()->json([
            'message' => 'Payment confirmed and room assigned successfully',
            'payment' => $payment,
        ]);
    }

    public function getUnconfirmedPayments(Request $request)
    {
        $user = auth()->user();

        $payments = Payment::with(['user', 'room'])
            ->where('payment_method', 'manual')
            ->where('confirmed_by_landlord', false)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['unconfirmed_payments' => $payments]);
    }

    public function destroy(string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
