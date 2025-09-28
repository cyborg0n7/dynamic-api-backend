<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json(['message' => 'Welcome to the admin dashboard']);
    }
}
