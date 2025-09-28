<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiKey;

class ApiKeyController extends Controller
{
    public function create(Request $request)
    {
        $plainKey = bin2hex(random_bytes(32)); // secure random key
        $hashedKey = hash('sha256', $plainKey);

        ApiKey::create([
            'user_id' => auth()->id(),
            'key' => $hashedKey,
            'name' => $request->input('name', 'My API Key'),
        ]);

        return response()->json([
            'api_key' => $plainKey
        ]);
    }
}
