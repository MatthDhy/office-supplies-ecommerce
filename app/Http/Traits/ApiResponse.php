<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * QUY ƯỚC JSON CHUNG cho mọi API AJAX của cả team:
 *   { "success": bool, "message": string, "data": object|array, "errors": object|null }
 * JS chỉ cần đọc res.success / res.message / res.data.
 */
trait ApiResponse
{
    protected function success(mixed $data = [], string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data, 'errors' => null], $code);
    }

    protected function fail(string $message, int $code = 422, mixed $errors = null): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'data' => null, 'errors' => $errors], $code);
    }
}
