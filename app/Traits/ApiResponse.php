<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

trait ApiResponse
{
    protected function success($data = null, string $message = null, int $code = 200)
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    protected function error(string $message, int $code = 400, $errors = null)
    {
        $response = [
            'status'  => 'error',
            'message' => $message,
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    public function respondWithPagination(AnonymousResourceCollection $collection, string $message = '', int $code = 200): JsonResponse
    {
        return $collection->additional([
            'success' => true,
            'message' => $message,
        ])->response()->setStatusCode($code);
    }
}
