<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportInserts extends Command
{
    protected $signature = 'lpmc:export
                            {--path= : Output file (defaults to LPMC_EXPORT_PATH)}';

    protected $description = 'Write the current rows of every hospital table as insert_db.sql';

    public function handle(): int
    {
        $path = $this->option('path') ?: config('lpmc.export_path');
        $pdo = DB::connection()->getPdo();

        $lines = [
            'use `'.DB::connection()->getDatabaseName().'`;',
            '',
        ];
        $total = 0;

        // parents first, so the file runs top to bottom without foreign key errors
        foreach (config('lpmc.tables') as $table => $primaryKey) {
            $query = DB::table($table);

            foreach ($primaryKey as $column) {
                $query->orderBy($column);
            }

            $rows = $query->get();

            $lines[] = "# {$table}";

            foreach ($rows as $row) {
                $row = (array) $row;
                $columns = implode(', ', array_keys($row));
                $values = implode(', ', array_map(fn ($value) => $this->literal($pdo, $value), $row));

                $lines[] = "insert into `{$table}` ({$columns}) values ({$values});";
            }

            $lines[] = '';
            $total += $rows->count();
        }

        file_put_contents($path, implode(PHP_EOL, $lines));

        $this->components->info("Wrote {$total} insert statements to {$path}");

        return self::SUCCESS;
    }

    private function literal(\PDO $pdo, mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_int($value), is_float($value) => (string) $value,
            default => $pdo->quote((string) $value),
        };
    }
}
