<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServerLog;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    public function storeLogs()
    {
        // Nonaktifkan fungsi ini karena menggunakan Elasticsearch
        return response()->json(['message' => 'This feature is disabled. Using Elasticsearch instead.']);
    }

    public function showLogs($filename)
    {
        // Nonaktifkan fungsi ini karena menggunakan Elasticsearch
        return response()->json(['message' => 'This feature is disabled. Using Elasticsearch instead.']);
    }
}
