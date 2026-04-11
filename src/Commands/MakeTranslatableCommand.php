<?php

namespace Relodev\Translatable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeTranslatableCommand extends Command
{
    protected $signature = 'translatable:migration {table} {columns*}';
    protected $description = 'Génère une migration pour ajouter les colonnes traduites';

    public function handle(): void
    {
        $table = $this->argument('table');
        $columns = $this->argument('columns');
        $locales = config('translatable.supported_locales', ['fr', 'en']);

        $fields = '';
        foreach ($columns as $col) {
            foreach ($locales as $locale) {
                $fields .= "\n            \$table->text('{$col}_{$locale}')->nullable();";
            }
        }

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
            // drop columns
        });
    }
};
PHP;

        $filename = database_path('migrations/' . date('Y_m_d_His') . "_add_translatable_to_{$table}.php");
        file_put_contents($filename, $stub);

        $this->info("Migration créée : {$filename}");
    }
}