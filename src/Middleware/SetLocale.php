<?php

namespace Relodev\Translatable\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $primary   = config('translatable.primary_locale', 'fr');
        $secondary = config('translatable.secondary_locales', []);
        $all       = array_unique(array_merge([$primary], $secondary));

        // Priorité 1 : segment de route (/fr/... ou /en/...)
        $segment = $request->segment(1);
        if (in_array($segment, $all)) {
            app()->setLocale($segment);
            session(['locale' => $segment]);
            return $next($request);
        }

        // Priorité 2 : valeur en session (bouton switch de langue)
        $sessionLocale = session('locale');
        if ($sessionLocale && in_array($sessionLocale, $all)) {
            app()->setLocale($sessionLocale);
            return $next($request);
        }

        // Priorité 3 : langue primaire configurée
        app()->setLocale($primary);

        return $next($request);
    }
}
