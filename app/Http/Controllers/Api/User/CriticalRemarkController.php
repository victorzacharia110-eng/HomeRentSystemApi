<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\CriticalRemark;
use Illuminate\Http\Request;

class CriticalRemarkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->is_landlord === 1) {
            $criticalRemarks = CriticalRemark::with('user')
                ->latest()
                ->get();
        } else {
            $criticalRemarks = CriticalRemark::with('user')
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        }

        return response()->json([
            'criticalRemarks' => $criticalRemarks
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'reason' => 'required|string',
            'type' => 'required|string',
            'active' => 'required|boolean',
        ]);

        $criticalRemark = new CriticalRemark();
        $criticalRemark->user_id = auth()->id(); // ✅ FIXED
        $criticalRemark->reason_text = $request->reason;
        $criticalRemark->type = $request->type;
        $criticalRemark->active = $request->active;

        $criticalRemark->save();

        // ✅ load user so Vue can access last_name
        $criticalRemark->load('user');

        return response()->json([
            'criticalRemark' => $criticalRemark
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $remark = CriticalRemark::with('user')
            ->where('user_id', auth()->id())
            ->first();

        return response()->json([
            'criticalRemark' => $remark
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $remark = CriticalRemark::findOrFail($id);

        $request->validate([
            'reason_text' => 'nullable|string',
            'type' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);

        $remark->update($request->only([
            'reason_text',
            'type',
            'active'
        ]));

        $remark->load('user');

        return response()->json([
            'criticalRemark' => $remark
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $remark = CriticalRemark::findOrFail($id);
        $remark->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}