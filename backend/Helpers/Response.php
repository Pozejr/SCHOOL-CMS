<?php

namespace App\Helpers;

class Response
{
    public static function success($data = null, string $message = 'Operation successful', int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ]);
        exit;
    }

    public static function paginated($data, int $page, int $perPage, int $total, string $message = 'Records retrieved'): void
    {
        $totalPages = (int)ceil($total / $perPage);
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ]);
        exit;
    }

    public static function error(string $code, string $message, int $statusCode = 400, array $details = []): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ]);
        exit;
    }

    public static function validationError(array $errors): void
    {
        self::error('VALIDATION_ERROR', 'Validation failed', 422, $errors);
    }

    public static function unauthorized(string $message = 'Authentication required'): void
    {
        self::error('UNAUTHORIZED', $message, 401);
    }

    public static function forbidden(string $message = 'Access denied'): void
    {
        self::error('FORBIDDEN', $message, 403);
    }

    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error('NOT_FOUND', $message, 404);
    }

    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error('SERVER_ERROR', $message, 500);
    }

    public static function tooManyRequests(string $message = 'Too many requests. Please try again later.'): void
    {
        self::error('RATE_LIMITED', $message, 429);
    }
}
