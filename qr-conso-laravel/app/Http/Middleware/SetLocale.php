<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La locale est portée par l'URL (/fr/..., /ar/...) plutôt que par la session :
 * un QR imprimé doit pouvoir pointer vers une langue précise, et un lien de
 * suivi partagé doit s'ouvrir dans la même langue pour tout le monde.
 */
class SetLocale
{
    public const SUPPORTED = ['fr', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
