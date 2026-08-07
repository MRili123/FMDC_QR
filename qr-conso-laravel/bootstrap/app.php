<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'setlocale' => \App\Http\Middleware\SetLocale::class,
        ]);

        // Sans cela, le middleware `auth` cherche une route nommée « login »,
        // qui n'existe pas ici : nos routes de connexion portent un préfixe de
        // locale. Un visiteur non authentifié récoltait donc une exception
        // « Route [login] not defined » au lieu de la page de connexion.
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            $locale = $request->route('locale');

            if (! in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED, true)) {
                $locale = config('app.locale');
            }

            return route('admin.login', $locale);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
