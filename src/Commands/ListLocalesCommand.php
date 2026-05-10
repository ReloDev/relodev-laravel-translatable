<?php

namespace Relodev\Translatable\Commands;

use Illuminate\Console\Command;

class ListLocalesCommand extends Command
{
    protected $signature = 'translatable:locales';
    protected $description = 'Affiche la configuration des langues du package';

    public function handle(): void
    {
        $primary   = config('translatable.primary_locale', 'fr');
        $secondary = config('translatable.secondary_locales', []);
        $fallback  = config('translatable.fallback_locale') ?? $primary . ' (défaut → primaire)';

        $this->newLine();
        $this->line('  <info>Laravel Relodev Translatable — Configuration des langues</info>');
        $this->newLine();
        $this->line("  Langue primaire    : <comment>{$primary}</comment>");
        $this->line("  Langues secondaires: <comment>" . (empty($secondary) ? '(aucune)' : implode(', ', $secondary)) . "</comment>");
        $this->line("  Fallback           : <comment>{$fallback}</comment>");
        $this->newLine();

        if (!empty($secondary)) {
            $this->line('  Toutes les locales supportées : <comment>' . implode(', ', array_merge([$primary], $secondary)) . '</comment>');
            $this->newLine();
            $this->line('  Convention des colonnes en BDD :');
            $this->line("    - Langue primaire ({$primary}) → colonne <info>sans suffixe</info>  ex: name");
            foreach ($secondary as $locale) {
                $this->line("    - Langue {$locale}              → colonne <info>suffixée _{$locale}</info>   ex: name_{$locale}");
            }
        } else {
            $this->warn('  ⚠  Aucune langue secondaire configurée.');
            $this->line('  Ajoutez des langues dans secondary_locales de config/translatable.php');
        }

        $this->newLine();
    }
}
