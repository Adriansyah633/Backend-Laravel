<?php

namespace App\Http\Controllers;


use App\Models\StatusMeter;

class StatusController extends Controller
{
    public function index() {
    $status = StatusMeter::all(); // Mengambil semua status
    return response()->json($status);
    }
}
