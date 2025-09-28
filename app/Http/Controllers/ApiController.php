<?php

namespace App\Http\Controllers;
use App\Models\Api;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function index()
    {
        return response()->json(Api::with('transformations')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string',
            'endpoint' => 'required|string',
            'method' => 'required|in:GET,POST,PUT,DELETE',
            'auth_type' => 'required|in:API_KEY,OAUTH,JWT'
        ]);

        $api = Api::create([
    'user_id' => $request->user_id,
    'name' => $request->name,
    'endpoint' => $request->endpoint,
    'method' => $request->method,
    'auth_type' => $request->auth_type,
    'transformation_rules' => $request->transformation_rules ?? '{}',
    'api_key' => bin2hex(random_bytes(16))
]);


        return response()->json($api, 201);
    }
}
