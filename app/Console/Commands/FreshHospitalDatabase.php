<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FreshHospitalDatabase extends Command
{
    protected $signature = 'lpmc:fresh
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Rebuild las_palmas_medical_center from create_db.sql, then seed it';

    public function handle(): int
    {
        $path = config('lpmc.schema_path');

        if (! $path || ! is_file($path)) {
            $this->error("create_db.sql not found. Set LPMC_SCHEMA_PATH in .env (currently: '{$path}').");

            return self::FAILURE;
        }

        if (! $this->option('force')
            && ! $this->confirm('This drops and recreates the las_palmas_medical_center database. Continue?')) {
            return self::SUCCESS;
        }

        $statements = $this->statements(file_get_contents($path));

        $this->components->task('Running create_db.sql ('.count($statements).' statements)', function () use ($statements) {
            $server = DB::connection('mysql_server');

            foreach ($statements as $statement) {
                $server->unprepared($statement);
            }
        });

        // the "mysql" connection may have been opened before the database was recreated
        DB::purge('mysql');

        return $this->call('db:seed', ['--force' => true]);
    }

    /**
     * Split the script into single statements.
     *
     * Comments go first: create_db.sql has a ";" inside a "#" comment. The script
     * has no string literal containing "#" or ";", so this simple split is safe for it.
     *
     * @return list<string>
     */
    private function statements(string $sql): array
    {
        $sql = preg_replace('/(#|--\s).*$/m', '', $sql);

        return array_values(array_filter(array_map('trim', explode(';', $sql))));
    }
}
