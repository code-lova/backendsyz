<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    // ... (any other methods like register() or __construct())

    public function render($request, Throwable $exception): Response
    {
        if ($request->expectsJson()) {
            if ($exception instanceof ThrottleRequestsException) {
                return response()->json([
                    'message' => 'Too many requests. Please wait a moment and try again.',
                ], 429);
            }

            // Handle HTTP exceptions (like 404, 403, etc.)
            if ($exception instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'An unexpected error occurred.',
                ], $exception->getStatusCode());
            }

            // All other exceptions (default 500)
            return response()->json([
                'message' => app()->environment('production')
                    ? 'Server error. Please try again later.'
                    : $exception->getMessage(), // show full error in local/dev
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
