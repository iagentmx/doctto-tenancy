<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',

        then: function () {
            /**
             * 🔹 Grupo de rutas para Webhooks externos
             * /webhook/*
             */
            if (file_exists(base_path('routes/webhook.php'))) {
                Route::middleware('api')
                    ->prefix('webhook')
                    ->name('webhook.')
                    ->group(base_path('routes/webhook.php'));
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('api-secure', [
            \App\Http\Middleware\ValidateApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // 1) 404 Not Found en JSON
        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'message' => '404 not found',
                'result'  => [],
            ], 404);
        });

        // 2) ERRORES DE VALIDACIÓN => 422 Unprocessable Entity
        $exceptions->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
                'result'  => [],
            ], 422);
        });

        // 3) Cualquier otra excepción
        $exceptions->renderable(function (\Throwable $e, $request) {
            if (! config('app.debug')) {
                // En producción (debug=false)
                $response = [
                    'message' => 'Internal Server Error',
                    'result'  => [],
                ];
                $httpCode = 500;
            } else {
                // En desarrollo (debug=true) mostramos la cadena de mensajes
                $messages = [];
                do {
                    $messages[] = $e->getMessage();
                } while ($e = $e->getPrevious());

                $response = [
                    'message' => implode(' ', $messages),
                    'result'  => [],
                ];
                // Para errores en controladores, se usará 400
                $httpCode = 400;
            }
            return response()->json($response, $httpCode);
        });
    })
    ->create();
