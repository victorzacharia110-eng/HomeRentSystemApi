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
    public function index()
    {
        $paymentMethods = PaymentMethod::orderBy("created_at","desc")->get();
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
