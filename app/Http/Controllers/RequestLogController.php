<?php

namespace App\Http\Controllers;

use App\Models\RequestLog;
use Illuminate\Http\Request;

class RequestLogController extends Controller
{
    public function index()
    {
        return response()->json(RequestLog::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'api_id' => 'required|exists:apis,id',
            'request_payload' => 'nullable|array',
            'response_payload' => 'nullable|array',
            'status_code' => 'required|integer'
        ]);

        $log = RequestLog::create([
            'api_id' => $request->api_id,
            'request_payload' => $request->request_payload,
            'response_payload' => $request->response_payload,
            'status_code' => $request->status_code
        ]);

        return response()->json($log, 201);
    }

    public function show($id)
    {
        return response()->json(RequestLog::findOrFail($id));
    }

    public function destroy($id)
    {
        RequestLog::destroy($id);
        return response()->json(['message' => 'Log deleted']);
    }
}
