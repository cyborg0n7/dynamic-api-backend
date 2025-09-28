<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Handles user registration.
 */
class RegisterController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|in:admin,developer,auditor'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'developer'
        ]);
         // 🔑 Generate and store API key
        $plainKey = \Illuminate\Support\Str::random(40);

        \App\Models\ApiKey::create([
        'user_id' => $user->id,
        'key' => hash('sha256', $plainKey)
        ]);
           // Return user info + plain API key (hashed one is in DB)
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'api_key' => $plainKey
        ], 201);
    }
}
