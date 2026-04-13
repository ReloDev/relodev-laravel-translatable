<?php

namespace Reodev\Translatable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeTranslatableCommand extends Command
{
    protected $signature = 'translatable:migration {table} {columns*}';
    protected $description = 'Génère une migration pour ajouter les colonnes traduites';

    public function handle(): void
    {
        $table   = $this->argument('table');
        $columns = $this->argument('columns');
        $locales = config('translatable.supported_locales', ['fr', 'en']);
        $defaultLocale = config('translatable.fallback_locale', 'fr');

        $nonDefaultLocales = array_filter($locales, fn($l) => $l !== $defaultLocale);

        // Vérifier que la table existe
        if (!Schema::hasTable($table)) {
            $this->error("La table '{$table}' n'existe pas en base de données.");
            return;
        }

        $fields   = '';
        $dropList = [];

        foreach ($columns as $col) {

            // Vérifier que la colonne source existe
            if (!Schema::hasColumn($table, $col)) {
                $this->warn("Colonne '{$col}' introuvable dans '{$table}', ignorée.");
                continue;
            }

            // Lire les infos de la colonne source
            $colInfo  = $this->getColumnInfo($table, $col);
            $colType  = $colInfo['type'];      // ex: text, string, integer, boolean...
            $nullable = $colInfo['nullable'];  // true / false

            foreach ($nonDefaultLocales as $locale) {
                $colName  = "{$col}_{$locale}";
                $nullable_str = $nullable ? '->nullable()' : '';
                $fields  .= "\n            \$table->{$colType}('{$colName}'){$nullable_str};";
                $dropList[] = "'{$colName}'";
            }
        }

        if (empty($dropList)) {
            $this->error("Aucune colonne valide trouvée, migration annulée.");
            return;
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

    /**
     * Lit le type et les attributs d'une colonne depuis la base de données
     */
    private function getColumnInfo(string $table, string $column): array
    {
        // Récupère les infos via Doctrine DBAL (disponible dans Laravel)
        $doctrine = Schema::getConnection()->getDoctrineSchemaManager();
        $columns  = $doctrine->listTableColumns($table);

        if (!isset($columns[$column])) {
            return ['type' => 'text', 'nullable' => true]; // fallback sécurisé
        }

        $col      = $columns[$column];
        $nullable = !$col->getNotnull();

        // Mapper le type Doctrine vers le type Blueprint Laravel
        $doctrineType = $col->getType()->getName();
        $type = match($doctrineType) {
            'string'   => 'string',
            'text'     => 'text',
            'integer'  => 'integer',
            'bigint'   => 'bigInteger',
            'smallint' => 'smallInteger',
            'boolean'  => 'boolean',
            'decimal'  => 'decimal',
            'float'    => 'float',
            'date'     => 'date',
            'datetime' => 'dateTime',
            'json'     => 'json',
            default    => 'text', // fallback sécurisé
        };

        return [
            'type'     => $type,
            'nullable' => $nullable,
        ];
    }
}