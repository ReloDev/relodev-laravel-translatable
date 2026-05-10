<?php

namespace Relodev\Translatable\Commands;

use Illuminate\Console\Command;

class AddLocaleCommand extends Command
{
    protected $signature = 'translatable:add-locale
                            {locale : Code de la langue à ajouter (ex: es, de, ar)}
                            {table : Table ciblée}
                            {columns* : Colonnes à étendre vers cette nouvelle langue}';

    protected $description = 'Ajoute une nouvelle langue secondaire et génère la migration correspondante';

    public function handle(): void
    {
        $locale    = $this->argument('locale');
        $table     = $this->argument('table');
        $columns   = $this->argument('columns');
        $primary   = config('translatable.primary_locale', 'fr');
        $secondary = config('translatable.secondary_locales', []);
        $all       = array_merge([$primary], $secondary);

        // Vérifications
        if ($locale === $primary) {
            $this->error("'{$locale}' est déjà la langue primaire. Aucune colonne supplémentaire à créer.");
            return;
        }

        if (in_array($locale, $secondary)) {
            $this->warn("'{$locale}' est déjà dans secondary_locales.");
            $this->line("La migration sera quand même générée pour les colonnes demandées.");
            $this->line("Si les colonnes existent déjà, la migration échouera — vérifiez avant de migrer.");
            $this->newLine();
        } else {
            $this->warn("⚠️  N'oubliez pas d'ajouter '{$locale}' dans secondary_locales de config/translatable.php :");
            $this->line("   'secondary_locales' => [" . implode(', ', array_map(fn($l) => "'{$l}'", array_merge($secondary, [$locale]))) . "],");
            $this->newLine();
        }

        // Génération de la migration
        $fields   = '';
        $dropList = [];

        foreach ($columns as $col) {
            $colName   = "{$col}_{$locale}";
            $fields   .= "\n            \$table->text('{$colName}')->nullable();";
            $dropList[] = "'{$colName}'";
        }

        $dropColumns = implode(', ', $dropList);

        $stub = <<<PHP
<?php

/**
 * Migration générée par relodev/laravel-translatable
 *
 * Ajout de la langue secondaire : {$locale}
 * Langue primaire du projet     : {$primary}
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

        $filename = database_path('migrations/' . date('Y_m_d_His') . "_add_locale_{$locale}_to_{$table}.php");
        file_put_contents($filename, $stub);

        $this->info("✅ Migration créée : {$filename}");
        $this->newLine();
        $this->line("  Colonnes ajoutées : {$dropColumns}");
        $this->newLine();
        $this->line("  Étapes suivantes :");
        $this->line("  1. Ajoutez '{$locale}' dans <info>config/translatable.php</info> → secondary_locales");
        $this->line("  2. Lancez : <info>php artisan migrate</info>");
        $this->line("  3. Ajoutez '{$locale}' au \$fillable de vos modèles concernés");
    }
}
