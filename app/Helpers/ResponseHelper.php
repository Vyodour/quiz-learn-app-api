<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    /**
     * Mengembalikan respons JSON sukses dengan struktur standar.
     * @param string $message Pesan respons.
     * @param mixed $data Data payload (bisa berupa Model, array, atau Resource).
     * @param string $key Kunci yang digunakan untuk data (misal: 'user', 'learning_paths').
     * @param int $status HTTP Status Code.
     */
    public static function success(string $message, $data = null, string $key = 'data', int $status = 200): JsonResponse
    {
        $response = [
            'error' => false,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response[$key] = $data;
        }

        return response()->json($response, $status);
    }

    public static function error(string $message, int $status = 500): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $message,
        ], $status);
    }

    public static function paginatedWithMeta($message, $collection, $resource, $key)
    {
        return response()->json([
            'error' => false,
            'message' => $message,
            'founded' => $collection->total(),
            $key => $resource,
            'pagination' => [
                'current_page' => $collection->currentPage(),
                'last_page' => $collection->lastPage(),
                'per_page' => $collection->perPage(),
                'total' => $collection->total(),
                'has_more_pages' => $collection->hasMorePages(),
            ],
        ]);
    }
}
