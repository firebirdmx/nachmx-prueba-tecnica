<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
        $exceptions->respond(function ($response) {
            if (request()->is('api/*')) {
                $message = match ($response->getStatusCode()) {
                    401 => 'Token inválido o vencido. Conecta de nuevo tu acceso.',
                    404 => 'No se encontró el recurso solicitado.',
                    405 => 'Método no permitido.',
                    429 => 'Demasiadas solicitudes. Intenta de nuevo en un minuto.',
                    500 => 'No fue posible procesar la solicitud. Intenta de nuevo.',
                    default => null,
                };
                if ($message !== null) {
                    return response()->json(['message' => $message], $response->getStatusCode());
                }
            }

            return $response;
        });
    })->create();
