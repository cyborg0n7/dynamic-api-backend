<?php

namespace App\Http\Controllers;

use App\Models\BillingRecord;
use Illuminate\Http\Request;

class BillingRecordController extends Controller
{
    public function index()
    {
        return response()->json(BillingRecord::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'currency' => 'required|string|max:10',
            'payment_status' => 'required|in:pending,paid,failed'
        ]);

        $billing = BillingRecord::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'payment_status' => $request->payment_status
        ]);

        return response()->json($billing, 201);
    }

    public function show($id)
    {
        return response()->json(BillingRecord::findOrFail($id));
    }

    public function destroy($id)
    {
        BillingRecord::destroy($id);
        return response()->json(['message' => 'Billing record deleted']);
    }
}
