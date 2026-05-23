<?php

namespace App\Traits;

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

    protected function error(string $message, int $code = 404, $data = null)
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'data'    => $data
        ], $code);
    }
}
