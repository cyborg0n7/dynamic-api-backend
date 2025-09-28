<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeveloperController extends Controller
{
    public function dashboard()
    {
        return response()->json(['message' => 'Welcome to the developer dashboard']);
    }
}
