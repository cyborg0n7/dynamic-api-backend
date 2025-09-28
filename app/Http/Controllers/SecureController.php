<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SecureController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Secure data endpoint']);
    }
}
