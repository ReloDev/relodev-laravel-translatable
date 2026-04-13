<?php

namespace Relodev\Translatable;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Relodev\Translatable\Commands\MakeTranslatableCommand;
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

        // Publier les dossiers lang vides
        $this->publishes([
            __DIR__ . '/../lang' => lang_path(),
        ], 'translatable-lang');

        // Créer automatiquement les dossiers lang/fr et lang/en
        $locales = config('translatable.supported_locales', ['fr', 'en']);
        foreach ($locales as $locale) {
            $path = lang_path($locale);
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        // Enregistrer le middleware globalement
        $this->app[Kernel::class]->pushMiddleware(SetLocale::class);

        // Commandes artisan
        if ($this->app->runningInConsole()) {
            $this->commands([MakeTranslatableCommand::class]);
        }
    }
}