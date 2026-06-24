<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof QueryException && $this->isConnectionError($e)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'База данных недоступна. Проверьте DATABASE_URL на Railway.',
                ], 503);
            }

            return response()->view('errors.database', [], 503);
        }

        if (! config('app.debug')) {
            report($e);
        }

        return parent::render($request, $e);
    }

    private function isConnectionError(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'Connection refused')
            || str_contains($message, 'getaddrinfo failed')
            || str_contains($message, 'Access denied')
            || str_contains($message, 'Unknown database');
    }
}
