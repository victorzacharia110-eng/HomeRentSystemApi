<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        $admin = auth()->user();
        if (!$admin->is_super_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json(['admin' => $admin]);
    }

    public function getLandlords(Request $request)
    {
        $admin = auth()->user();
        if (!$admin->is_super_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $query = User::where('is_landlord', true);
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $landlords = $query->paginate($perPage);
        return response()->json(['landlords' => $landlords]);
    }

    public function getTenants(Request $request)
    {
        $admin = auth()->user();
        if (!$admin->is_super_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $query = User::with('room')->where('is_landlord', false);
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $tenants = $query->paginate($perPage);
        return response()->json(['tenants' => $tenants]);
    }

    public function resetUserPassword(Request $request, string $userId)
    {
        $admin = auth()->user();
        if (!$admin->is_super_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail($userId);
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully for ' . $user->email]);
    }

    public function toggleLandlordStatus(Request $request, string $userId)
    {
        $admin = auth()->user();
        if (!$admin->is_super_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($userId);
        if (!$user->is_landlord) {
            return response()->json(['message' => 'User is not a landlord'], 400);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';
        return response()->json([
            'message' => "Landlord {$status} successfully",
            'user' => $user
        ]);
    }

    public function toggleUserStatus(Request $request, string $userId)
    {
        $admin = auth()->user();
        if (!$admin->is_super_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($userId);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';
        return response()->json([
            'message' => "User {$status} successfully",
            'user' => $user
        ]);
    }

    public function getUnconfirmedPayments(Request $request)
    {
        $admin = auth()->user();
        if (!$admin->is_super_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payments = Payment::with(['user', 'room'])
            ->where('payment_method', 'manual')
            ->where('confirmed_by_landlord', false)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['unconfirmed_payments' => $payments]);
    }
}
