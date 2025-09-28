<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuditorController extends Controller
{
    public function reports()
    {
        return response()->json(['message' => 'Auditor reports']);
    }
}
