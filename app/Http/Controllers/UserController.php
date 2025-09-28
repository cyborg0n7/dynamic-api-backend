<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'users' => [
                ['id' => 1, 'name' => 'Eli'],
                ['id' => 2, 'name' => 'Sarah']
            ]
        ]);
    }

    public function store(Request $request)
    {
        return response()->json([
            'message' => 'User created',
            'data' => $request->all()
        ], 201);
    }
}
