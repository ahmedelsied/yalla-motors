<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseApiController extends Controller
{
    protected function respondSuccess(mixed $data = null): JsonResponse
    {
        return response()->json($data);
    }
}
