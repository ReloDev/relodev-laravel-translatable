<?php

namespace Relodev\Translatable\Commands;

use Illuminate\Console\Command;

class MakeTranslatableCommand extends Command
{
    protected $signature = 'translatable:migration
                            {table : Nom de la table cible}
                            {columns* : Colonnes à rendre traduisibles}
                            {--only= : Générer uniquement pour une locale secondaire précise}';

    protected $description = 'Génère une migration pour ajouter les colonnes traduites (toutes les langues secondaires)';

    public function handle(): void
    {
        $table        = $this->argument('table');
        $columns      = $this->argument('columns');
        $primary      = config('translatable.primary_locale', 'fr');
        $secondary    = config('translatable.secondary_locales', []);
        $onlyLocale   = $this->option('only');

        if (empty($secondary)) {
            $this->warn('Aucune langue secondaire définie dans config/translatable.php.');
            $this->line('Ajoutez des langues dans la clé "secondary_locales" pour générer des colonnes.');
            return;
        }

        // Si --only est passé, filtrer sur cette locale uniquement
        $targetLocales = $onlyLocale
            ? array_filter($secondary, fn($l) => $l === $onlyLocale)
            : $secondary;

        if (empty($targetLocales)) {
            $this->error("La locale '{$onlyLocale}' n'est pas dans secondary_locales.");
            return;
        }

        $fields   = '';
        $dropList = [];

        foreach ($columns as $col) {
            foreach ($targetLocales as $locale) {
                $colName   = "{$col}_{$locale}";
                $fields   .= "\n            \$table->text('{$colName}')->nullable();";
                $dropList[] = "'{$colName}'";
            }
        }

        $dropColumns = implode(', ', $dropList);

        // Commentaire informatif dans la migration
        $localeInfo  = "Langue primaire : {$primary} (colonne de base, sans suffixe)\n";
        $localeInfo .= ' * Langues secondaires : ' . implode(', ', $targetLocales) . ' (colonnes suffixées)';

        $stub = <<<PHP
<?php

/**
 * Migration générée par relodev/laravel-translatable
 *
 * {$localeInfo}
 *
 * Colonnes créées :
PHP;

        foreach ($columns as $col) {
            $stub .= "\n * - {$col} (primaire/{$primary}) : colonne déjà existante, pas de modification";
            foreach ($targetLocales as $locale) {
                $stub .= "\n * - {$col}_{$locale} : traduction en {$locale}";
            }
        }

        $stub .= <<<PHP

 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {{$fields}
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn([{$dropColumns}]);
        });
    }
};
PHP;

        $filename = database_path('migrations/' . date('Y_m_d_His') . "_add_translatable_to_{$table}.php");
        file_put_contents($filename, $stub);

        $this->info("✅ Migration créée : {$filename}");
        $this->newLine();
        $this->line("  Langue primaire  : <comment>{$primary}</comment> (colonne de base sans suffixe)");
        $this->line("  Langues cibles   : <comment>" . implode(', ', $targetLocales) . "</comment>");
        $this->newLine();
        $this->line("  Colonnes ajoutées : {$dropColumns}");
        $this->newLine();
        $this->line("  Lancez ensuite : <info>php artisan migrate</info>");
    }
}
