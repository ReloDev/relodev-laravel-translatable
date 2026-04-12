<?php

namespace Relodev\Translatable\Commands;

use Illuminate\Console\Command;

class MakeTranslatableCommand extends Command
{
    protected $signature = 'translatable:migration {table} {columns*}';
    protected $description = 'Génère une migration pour ajouter les colonnes traduites';

    public function handle(): void
    {
        $table = $this->argument('table');
        $columns = $this->argument('columns');
        $locales = config('translatable.supported_locales', ['fr', 'en']);
        $defaultLocale = config('translatable.fallback_locale', 'fr');

        // Seules les locales non-défaut génèrent une colonne
        // car le champ de base (ex: "note") sert déjà de version par défaut
        $nonDefaultLocales = array_filter($locales, fn($l) => $l !== $defaultLocale);

        $fields = '';
        $dropList = [];

        foreach ($columns as $col) {
            foreach ($nonDefaultLocales as $locale) {
                $colName = "{$col}_{$locale}";
                $fields .= "\n            \$table->text('{$colName}')->nullable();";
                $dropList[] = "'{$colName}'";
            }
        }

        $dropColumns = implode(', ', $dropList);

        $stub = <<<PHP
<?php
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

        $this->info("Migration créée : {$filename}");
        $this->line("Colonnes ajoutées : {$dropColumns}");
    }
}