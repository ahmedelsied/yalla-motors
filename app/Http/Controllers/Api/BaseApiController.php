<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    protected function respondSuccess(mixed $data = null, int $statusCode = 200): JsonResponse
    {
        return response()->json($data, $statusCode);
    }
}
