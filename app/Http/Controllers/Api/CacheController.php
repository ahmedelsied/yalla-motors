<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class CacheController extends Controller
{
    public function purge()
    {
        Cache::flush();
        return response()->json(['message' => 'Cache purged']);
    }
}
