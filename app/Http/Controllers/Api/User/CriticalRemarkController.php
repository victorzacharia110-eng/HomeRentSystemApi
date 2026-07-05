<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\CriticalRemark;
use Illuminate\Http\Request;

class CriticalRemarkController extends Controller
{
    /**
     * Display a listing of the resource.
     * Landlord sees ALL remarks (for all tenants)
     * Tenant sees ONLY remarks about themselves
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        if ($user->is_landlord === 1) {
            // Landlord sees ALL remarks with tenant info
            $criticalRemarksQuery = CriticalRemark::with('user')->latest();
        } else {
            // Tenant sees ONLY remarks where user_id matches their ID
            $criticalRemarksQuery = CriticalRemark::with('user')
                ->where('user_id', $user->id)
                ->latest();
        }

        if ($search) {
            $criticalRemarksQuery->where(function($q) use ($search) {
                $q->where('reason_text', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $criticalRemarks = $criticalRemarksQuery->paginate($perPage);

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
            'user_id' => 'required|exists:users,id',  //  The TENANT this remark is for
            'reason' => 'required|string',
            'type' => 'required|string',
            'active' => 'required|boolean',
        ]);

        $criticalRemark = new CriticalRemark();
        $criticalRemark->user_id = $request->user_id;  //   Assign to TENANT, 
        $criticalRemark->reason_text = $request->reason;
        $criticalRemark->type = $request->type;
        $criticalRemark->active = $request->active;
        $criticalRemark->save();

        // Load user so frontend can access tenant name
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
        $user = auth()->user();

        $remark = CriticalRemark::with('user')
            ->where('id', $id);

        // Security: Tenant can only see their own remarks
        if ($user->is_landlord !== 1) {
            $remark->where('user_id', $user->id);
        }

        $remark = $remark->firstOrFail();

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
