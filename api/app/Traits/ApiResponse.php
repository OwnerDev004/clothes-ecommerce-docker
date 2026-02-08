<?php
// app/Traits/ApiResponse.php

namespace App\Traits;

trait ApiResponse
{
    protected static function success($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected static function error($message = 'Error', $code = 400, $errors = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    protected function created($data = null, $message = 'Resource created')
    {
        return self::success($data, $message, 201);
    }

    protected static function noContent($message = 'No content')
    {
        return self::success(null, $message, 204);
    }

    protected function paginate($data, $message = 'Success')
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ]
        ]);
    }
}
