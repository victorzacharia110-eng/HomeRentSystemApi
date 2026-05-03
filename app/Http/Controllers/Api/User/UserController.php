<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::with(['room.latestPayment','criticalRemarks'])->where('id', '=', auth()->user()->id)->first();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->is_landlord === 1) {
            $users = User::with(['room.latestPayment','criticalRemarks'])->where('is_landlord','=', 0)->get();

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

    /**
     * Store a newly created resource in storage.
     */
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


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        return response()->json(['user' => $user]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'last_name' => 'required|string',
            'email' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'required|string'
        ]);
        $user = User::find($id);
        $user->update($request->all());
        return response()->json(['user' => $user]);
    }

    public function updatePhoneNumber(Request $request, string $id) {
        $request->validate([
            'phone_number' => 'required|string'
        ]);
                $user = User::find($id);
        $user->update([
            'phone_number' => $request->phone_number
        ]);
        return response()->json(['user' => $user]);
    }

    /**
     * Remove the specified resource from storage.
     */
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
