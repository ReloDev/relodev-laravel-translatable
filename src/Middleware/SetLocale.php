<?php

namespace Relodev\Translatable\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locales = config('translatable.supported_locales', ['fr', 'en']);

        // Priorité 1 : segment de route /fr/... ou /en/...
        $segment = $request->segment(1);
        if (in_array($segment, $locales)) {
            app()->setLocale($segment);
            session(['locale' => $segment]);
            return $next($request);
        }

        // Priorité 2 : valeur en session (bouton switch de langue)
        $sessionLocale = session('locale');
        if ($sessionLocale && in_array($sessionLocale, $locales)) {
            app()->setLocale($sessionLocale);
            return $next($request);
        }

        // Priorité 3 : fallback config
        app()->setLocale(config('translatable.fallback_locale', 'fr'));

        return $next($request);
    }
}