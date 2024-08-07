<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponseService
{
    public function sendResponse($result, $message, $code = Response::HTTP_OK)
    {
        if ($result instanceof ResourceCollection) {
            return $result->additional([
                'status' => $code,
                'success' => true,
                'message' => $message,
            ]);
        }

        return response()->json([
            'status' => $code,
            'success' => true,
            'message' => $message,
            'data' => $result
        ], $code);
    }
    
    public function sendError($error, $code = Response::HTTP_NOT_FOUND)
    {
        return response()->json([
            'status' => $code,
            'success' => false,
            'message' => $error,
            'data' => null
        ], $code);
    }
}
