<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponseHelper
 *
 * Menyediakan format response JSON yang seragam untuk seluruh API.
 * Format: { "success": bool, "data": mixed, "message": string }
 */
trait ApiResponseHelper
{
    /**
     * Mengembalikan response sukses (2xx).
     */
    public function successResponse(mixed $data = null, string $message = 'Request successful', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Mengembalikan response error (4xx / 5xx).
     *
     * @param  mixed  $errors  Detail error (opsional, misal validation errors)
     */
    public function errorResponse(string $message = 'Something went wrong', mixed $errors = null, int $code = 400): JsonResponse
    {
        $payload = [
            'success' => false,
            'data' => $errors,
            'message' => $message,
        ];

        return response()->json($payload, $code);
    }
}
