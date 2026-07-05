<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $paymentMethodsQuery = PaymentMethod::orderBy("created_at", "desc");
        if ($search) {
            $paymentMethodsQuery->where(function($q) use ($search) {
                $q->where('airtel_money_number', 'like', "%{$search}%")
                  ->orWhere('m_pesa_number', 'like', "%{$search}%")
                  ->orWhere('mixx_by_yas_number', 'like', "%{$search}%")
                  ->orWhere('halopesa_number', 'like', "%{$search}%")
                  ->orWhere('nmb_account_number', 'like', "%{$search}%")
                  ->orWhere('crdb_account_number', 'like', "%{$search}%")
                  ->orWhere('nbc_account_number', 'like', "%{$search}%");
            });
        }
        $paymentMethods = $paymentMethodsQuery->paginate($perPage);
        return response()->json(['paymentMethods' => $paymentMethods]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            "airtel_money_number" => "numeric|nullable",
            "m_pesa_number" => "numeric|nullable",
            "mixx_by_yas_number" => "numeric|nullable",
            "halopesa_number" => "numeric|nullable",
            "nmb_account_number" => "numeric|nullable",
            "crdb_account_number" => "numeric|nullable",
            "nbc_account_number" => "numeric|nullable",
        ]);
        $paymentMethod = new PaymentMethod();
        $paymentMethod->user_id = auth()->user()->id;
        $paymentMethod->airtel_money_number = $request->airtel_money_number;
        $paymentMethod->m_pesa_number = $request->m_pesa_number;
        $paymentMethod->mixx_by_yas_number = $request->mixx_by_yas_number;
        $paymentMethod->halopesa_number = $request->halopesa_number;
        $paymentMethod->nmb_account_number = $request->nmb_account_number;
        $paymentMethod->crdb_account_number = $request->crdb_account_number;
        $paymentMethod->nmb_account_number = $request->nmb_account_number;
        $paymentMethod->save();

        return response()->json(['paymentMethod' => $paymentMethod]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        return response()->json([
            'paymentMethod' => $paymentMethod
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        $paymentMethod->update([
            'airtel_money_number' => $request->airtel_money_number,
            'm_pesa_number' => $request->m_pesa_number,
            'mixx_by_yas_number' => $request->mixx_by_yas_number,
            'halopesa_number' => $request->halopesa_number,
            'nmb_account_number' => $request->nmb_account_number,
            'crdb_account_number' => $request->crdb_account_number,
            'nbc_account_number' => $request->nbc_account_number,
        ]);

        return response()->json([
            'paymentMethod' => $paymentMethod
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);
        $paymentMethod->delete();
        return response()->json(['paymentMethod' => $paymentMethod]);
    }
}
