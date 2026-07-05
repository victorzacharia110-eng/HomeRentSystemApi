<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\LatePaymentReason;
use Illuminate\Http\Request;

class LatePaymentReasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'reason_text' => 'required|string|max:255'
        ]);

        $latePaymentReason = new LatePaymentReason();
        $latePaymentReason->user_id = auth()->user()->id;
        $latePaymentReason->payment_id = $request->payment_id;
        $latePaymentReason->reason_text = $request->reason_text;
        $latePaymentReason->save();

        return response()->json(['latePaymentReason' => $latePaymentReason]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
