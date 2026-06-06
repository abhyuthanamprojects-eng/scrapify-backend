<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Send a success response.
     *
     * @param string $messageKey Translation key for the message
     * @param mixed $data Data payload
     * @param int $code HTTP Status Code
     */
    public function successResponse(string $messageKey, $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'success' => true,
            'code' => $code,
            'message' => trans($messageKey),
            'message_text' => trans($messageKey),
            'data' => $data,
            'errors' => null,
        ], $code);
    }

    /**
     * Send a paginated success response.
     */
    public function paginatedResponse(string $messageKey, $paginator, int $code = 200): JsonResponse
    {
        $data = [
            'items' => $paginator->items(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ]
        ];

        return $this->successResponse($messageKey, $data, $code);
    }

    /**
     * Send a validation error response.
     *
     * @param mixed $errors Validation errors
     * @param string $messageKey
     * @param int $code
     */
    public function validationErrorResponse($errors, string $messageKey = 'validation.failed', int $code = 422): JsonResponse
    {
        return response()->json([
            'status' => false,
            'success' => false,
            'code' => $code,
            'message' => trans($messageKey),
            'message_text' => trans($messageKey),
            'data' => null,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Send an error response.
     *
     * @param string $messageKey Translation key
     * @param int $code HTTP Status Code
     * @param mixed $errors Optional extra error details
     */
    public function errorResponse(string $messageKey, int $code = 500, $errors = null): JsonResponse
    {
        return response()->json([
            'status' => false,
            'success' => false,
            'code' => $code,
            'message' => trans($messageKey),
            'message_text' => trans($messageKey),
            'data' => null,
            'errors' => $errors,
        ], $code);
    }
}
