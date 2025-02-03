<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use App\Enums\StatusCode;

trait HttpResponses
{

    /**
     * Returns as success with json response
     * 
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function success(mixed $data, string $message = 'okay', int $statusCode = StatusCode::Ok->value): JsonResponse
    {
        return response()->json([
            'status' => $statusCode,
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Returns as success with json response
     * 
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function error(string $message, int $statusCode = StatusCode::BadRequest->value, mixed $data = null): JsonResponse
    {
        return response()->json([
            'status' => $statusCode,
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
