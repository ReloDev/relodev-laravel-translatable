<?php

namespace Relodev\Translatable;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Relodev\Translatable\Commands\MakeTranslatableCommand;
use Relodev\Translatable\Commands\AddLocaleCommand;
use Relodev\Translatable\Commands\ListLocalesCommand;
use Relodev\Translatable\Middleware\SetLocale;

class TranslatableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/translatable.php', 'translatable');
    }

    public function boot(): void
    {
        // Publier la config
        $this->publishes([
            __DIR__ . '/../config/translatable.php' => config_path('translatable.php'),
        ], 'translatable-config');

        // Publier les stubs de migration
        $this->publishes([
            __DIR__ . '/../database/stubs' => base_path('stubs'),
        ], 'translatable-stubs');

        // Publier les dossiers lang
        $this->publishes([
            __DIR__ . '/../lang' => lang_path(),
        ], 'translatable-lang');

        // Créer automatiquement les dossiers lang/{locale} pour toutes les locales configurées
        $primary   = config('translatable.primary_locale', 'fr');
        $secondary = config('translatable.secondary_locales', []);
        $all       = array_unique(array_merge([$primary], $secondary));

        foreach ($all as $locale) {
            $path = lang_path($locale);
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        // Enregistrer le middleware globalement
        $this->app[Kernel::class]->pushMiddleware(SetLocale::class);
        $this->app['router']->pushMiddlewareToGroup('web', SetLocale::class);

        // Commandes artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeTranslatableCommand::class,
                AddLocaleCommand::class,
                ListLocalesCommand::class,
            ]);
        }
    }
}
