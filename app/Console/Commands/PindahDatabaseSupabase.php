<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Menyalin seluruh data dari database SQLite lama ke PostgreSQL (Supabase).
 *
 *   1) Ubah .env ke Supabase (DB_CONNECTION=pgsql ...)
 *   2) php artisan migrate --force
 *   3) php artisan db:pindah-supabase
 */
class PindahDatabaseSupabase extends Command
{
    protected $signature = 'db:pindah-supabase
                            {--sqlite= : Lokasi file SQLite lama (default database/database.sqlite)}
                            {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Salin semua data dari SQLite lama ke database PostgreSQL / Supabase';

    /** Tabel sementara yang tidak perlu disalin. */
    private const SKIP = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    public function handle(): int
    {
        $sqlitePath = $this->option('sqlite') ?: database_path('database.sqlite');

        if (! file_exists($sqlitePath)) {
            $this->error("File SQLite tidak ditemukan: {$sqlitePath}");

            return self::FAILURE;
        }

        $target = config('database.default');

        if (config("database.connections.{$target}.driver") !== 'pgsql') {
            $this->error('Koneksi utama belum PostgreSQL. Ubah .env: DB_CONNECTION=pgsql lalu isi data Supabase.');

            return self::FAILURE;
        }

        config(['database.connections.sqlite_lama' => [
            'driver'                  => 'sqlite',
            'database'                => $sqlitePath,
            'prefix'                  => '',
            'foreign_key_constraints' => false,
        ]]);

        $src = DB::connection('sqlite_lama');
        $dst = DB::connection($target);

        try {
            $dst->getPdo();
        } catch (Throwable $e) {
            $this->error('Tidak bisa terhubung ke Supabase: ' . $e->getMessage());

            return self::FAILURE;
        }

        $sourceTables = collect($src->select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"))
            ->pluck('name')
            ->reject(fn ($t) => in_array($t, self::SKIP, true))
            ->values();

        $targetTables = collect($dst->select("select table_name from information_schema.tables where table_schema = 'public' and table_type = 'BASE TABLE'"))
            ->pluck('table_name');

        $missing = $sourceTables->diff($targetTables);
        $tables  = $sourceTables->intersect($targetTables)->values();

        if ($tables->isEmpty()) {
            $this->error('Tabel di Supabase masih kosong. Jalankan dulu: php artisan migrate --force');

            return self::FAILURE;
        }

        foreach ($missing as $table) {
            $this->warn("Tabel '{$table}' tidak ada di Supabase, dilewati.");
        }

        $tables = $this->sortByDependency($src, $tables->all());

        $this->line('Sumber : ' . $sqlitePath);
        $this->line('Tujuan : ' . config("database.connections.{$target}.host") . ' / ' . config("database.connections.{$target}.database"));
        $this->line('Tabel  : ' . implode(', ', $tables));
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Data tabel di atas yang ada di Supabase akan DIHAPUS lalu diganti data dari SQLite. Lanjut?', true)) {
            return self::SUCCESS;
        }

        $replica = false;
        $current = null;
        $columns = [];

        try {
            $dst->statement('SET session_replication_role = replica');
            $replica = true;
        } catch (Throwable) {
            // Tidak punya izin: urutan tabel sudah diatur supaya relasi tetap aman.
        }

        try {
            $dst->beginTransaction();

            $dst->statement('TRUNCATE TABLE ' . implode(', ', array_map(fn ($t) => '"' . $t . '"', $tables)) . ' RESTART IDENTITY CASCADE');

            foreach ($tables as $table) {
                $current = $table;
                $types   = collect($dst->select(
                    "select column_name, data_type from information_schema.columns where table_schema = 'public' and table_name = ?",
                    [$table]
                ))->pluck('data_type', 'column_name')->all();
                $columns[$table] = $types;

                $count = 0;
                $batch = [];

                foreach ($src->table($table)->cursor() as $row) {
                    $clean = [];

                    foreach ((array) $row as $column => $value) {
                        if (! array_key_exists($column, $types)) {
                            continue;
                        }
                        $clean[$column] = $this->convert($value, $types[$column]);
                    }

                    $batch[] = $clean;
                    $count++;

                    if (count($batch) >= 200) {
                        $dst->table($table)->insert($batch);
                        $batch = [];
                    }
                }

                if ($batch) {
                    $dst->table($table)->insert($batch);
                }

                $this->line(sprintf('  ✓ %-32s %6d baris', $table, $count));
            }

            $current = null;
            $dst->commit();
        } catch (Throwable $e) {
            $dst->rollBack();
            $this->resetRole($dst, $replica);
            $this->newLine();
            $this->error('Gagal' . ($current ? " di tabel '{$current}'" : '') . ': ' . $e->getMessage());
            $this->line('Tidak ada data yang berubah di Supabase. Perbaiki masalahnya lalu jalankan ulang.');

            return self::FAILURE;
        }

        $this->resetRole($dst, $replica);

        // Lanjutkan nomor ID otomatis setelah ID terbesar.
        foreach ($tables as $table) {
            if (! isset($columns[$table]['id'])) {
                continue;
            }

            try {
                $sequence = $dst->selectOne('select pg_get_serial_sequence(?, ?) as seq', ['"' . $table . '"', 'id'])?->seq ?? null;

                if ($sequence) {
                    $dst->statement("select setval(?, coalesce((select max(id) from \"{$table}\"), 1), (select max(id) from \"{$table}\") is not null)", [$sequence]);
                }
            } catch (Throwable $e) {
                $this->warn("Nomor ID tabel '{$table}' tidak bisa disetel: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Selesai. Semua data sudah pindah ke Supabase.');

        return self::SUCCESS;
    }

    private function resetRole($dst, bool $replica): void
    {
        if (! $replica) {
            return;
        }

        try {
            $dst->statement('SET session_replication_role = origin');
        } catch (Throwable) {
            // abaikan
        }
    }

    private function convert(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'boolean') {
            return in_array(strtolower((string) $value), ['1', 'true', 't', 'yes', 'on'], true);
        }

        $numeric = ['smallint', 'integer', 'bigint', 'numeric', 'real', 'double precision'];

        if ($value === '' && (in_array($type, $numeric, true) || str_starts_with($type, 'timestamp') || $type === 'date' || $type === 'time without time zone' || $type === 'json' || $type === 'jsonb' || $type === 'uuid')) {
            return null;
        }

        return $value;
    }

    /** Urutkan tabel induk dulu baru tabel anak (berdasarkan foreign key SQLite). */
    private function sortByDependency($src, array $tables): array
    {
        $deps = [];

        foreach ($tables as $table) {
            $deps[$table] = collect($src->select('PRAGMA foreign_key_list("' . $table . '")'))
                ->pluck('table')
                ->filter(fn ($parent) => $parent !== $table && in_array($parent, $tables, true))
                ->unique()
                ->values()
                ->all();
        }

        $sorted  = [];
        $visited = [];

        $visit = function (string $table) use (&$visit, &$sorted, &$visited, $deps) {
            if (isset($visited[$table])) {
                return;
            }
            $visited[$table] = true;
            foreach ($deps[$table] ?? [] as $parent) {
                $visit($parent);
            }
            $sorted[] = $table;
        };

        foreach ($tables as $table) {
            $visit($table);
        }

        return $sorted;
    }
}
