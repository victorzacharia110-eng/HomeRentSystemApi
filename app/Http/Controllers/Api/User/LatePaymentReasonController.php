<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\LatePaymentReason;
use Illuminate\Http\Request;

class LatePaymentReasonController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $latePaymentReasonsQuery = LatePaymentReason::with('user')->latest();
        if ($search) {
            $latePaymentReasonsQuery->where(function($q) use ($search) {
                $q->where('reason_text', 'like', "%{$search}%");
            });
        }
        $latePaymentReasons = $latePaymentReasonsQuery->paginate($perPage);

        return response()->json([
            'latePaymentReasons' => $latePaymentReasons
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'reason_text' => 'required|string|max:255'
        ]);

        $latePaymentReason = new LatePaymentReason();
        $latePaymentReason->user_id = auth()->user()->id;
        $latePaymentReason->payment_id = $request->payment_id;
        $latePaymentReason->reason_text = $request->reason_text;
        $latePaymentReason->save();

        return response()->json(['latePaymentReason' => $latePaymentReason]);
    }

    public function show(string $id)
    {
        $latePaymentReason = LatePaymentReason::with('user')->findOrFail($id);
        return response()->json(['latePaymentReason' => $latePaymentReason]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'reason_text' => 'sometimes|string|max:255'
        ]);

        $latePaymentReason = LatePaymentReason::findOrFail($id);
        $latePaymentReason->update($request->only(['reason_text']));
        return response()->json(['latePaymentReason' => $latePaymentReason]);
    }

    public function destroy(string $id)
    {
        $latePaymentReason = LatePaymentReason::findOrFail($id);
        $latePaymentReason->delete();
        return response()->json(['message' => 'Late payment reason deleted successfully']);
    }
}
