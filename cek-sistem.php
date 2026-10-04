<?php
/**
 * ======================================================================
 *  CEK SISTEM - GOLF BOOKING LESSON
 * ======================================================================
 *  Pengecekan otomatis (HANYA MEMBACA, tidak mengubah file / data):
 *   1. Error penulisan (syntax) di semua file PHP
 *   2. Semua halaman Blade bisa di-compile
 *   3. Semua route yang dipanggil di kode memang terdaftar
 *   4. Semua view yang di-include / extends memang ada
 *   5. Migration database sudah dijalankan semua
 *   6. Tabel penting ada & bisa dibaca
 *   7. Storage link untuk upload gambar
 *   8. Error terbaru di log Laravel
 *   9. Data yang janggal (pembayaran tanpa booking, dsb.)
 *  10. File sisa installer
 *
 *  Cara pakai (dari folder root proyek):
 *      php cek-sistem.php
 * ======================================================================
 */

if (PHP_SAPI !== 'cli') {
    exit("Jalankan lewat terminal: php cek-sistem.php\n");
}

$root = getcwd();

if (! file_exists($root . '/artisan') || ! file_exists($root . '/vendor/autoload.php')) {
    fwrite(STDERR, "X Jalankan dari folder root proyek Laravel (golf-booking-lesson).\n");
    exit(1);
}

require $root . '/vendor/autoload.php';

$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$errors   = 0;
$warnings = 0;

function line(string $msg = ''): void
{
    echo $msg . PHP_EOL;
}

function ok(string $msg): void
{
    line("  [OK]   {$msg}");
}

function bad(string $msg): void
{
    global $errors;
    $errors++;
    line("  [ERROR] {$msg}");
}

function warn(string $msg): void
{
    global $warnings;
    $warnings++;
    line("  [CEK]  {$msg}");
}

function phpFiles(string $root, array $dirs): array
{
    $files = [];

    foreach ($dirs as $dir) {
        $path = $root . '/' . $dir;

        if (! is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (str_ends_with($file->getFilename(), '.php')) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

function rel(string $path): string
{
    global $root;
    return str_replace('\\', '/', ltrim(substr($path, strlen($root)), '/\\'));
}

line();
line('==============================================');
line('  CEK SISTEM - GOLF BOOKING LESSON');
line('==============================================');

/* ----------------------------------------------------------------------
 | 1. SYNTAX PHP
 * ---------------------------------------------------------------------- */
line();
line('[1/10] Error penulisan (syntax) di file PHP...');

$php       = escapeshellarg(PHP_BINARY);
$codeFiles = array_filter(
    phpFiles($root, ['app', 'routes', 'database', 'config', 'bootstrap']),
    fn ($file) => ! str_contains(str_replace('\\', '/', $file), 'bootstrap/cache/')
);
$syntaxErrors = 0;

foreach ($codeFiles as $file) {
    $output = [];
    exec($php . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);

    if ($code !== 0) {
        $syntaxErrors++;
        bad(rel($file) . ': ' . trim(implode(' ', $output)));
    }
}

if ($syntaxErrors === 0) {
    ok(count($codeFiles) . ' file PHP tanpa error penulisan');
}

/* ----------------------------------------------------------------------
 | 2. COMPILE SEMUA VIEW BLADE
 * ---------------------------------------------------------------------- */
line();
line('[2/10] Compile semua halaman Blade...');

try {
    Artisan::call('view:cache');
    ok('Semua halaman Blade berhasil di-compile');
} catch (Throwable $e) {
    bad('Gagal compile view: ' . $e->getMessage());
} finally {
    try {
        Artisan::call('view:clear');
    } catch (Throwable $e) {
        // abaikan
    }
}

/* ----------------------------------------------------------------------
 | 3. ROUTE YANG DIPANGGIL DI KODE
 * ---------------------------------------------------------------------- */
line();
line('[3/10] Route yang dipanggil di kode...');

$sourceFiles = array_merge(phpFiles($root, ['resources/views']), phpFiles($root, ['app']));
$missingRoutes = [];
$checkedRoutes = [];

foreach ($sourceFiles as $file) {
    $content = file_get_contents($file);

    if (preg_match_all("/route\(\s*['\"]([A-Za-z0-9_.\-]+)['\"]/", $content, $matches)) {
        foreach ($matches[1] as $name) {
            $checkedRoutes[$name] = true;

            if (! Route::has($name)) {
                $missingRoutes[$name][] = rel($file);
            }
        }
    }
}

if ($missingRoutes) {
    foreach ($missingRoutes as $name => $usedIn) {
        $usedIn = array_unique($usedIn);

        // Route opsional yang memang dicek Route::has() sebelum dipakai
        $guarded = true;
        foreach ($usedIn as $relFile) {
            if (! str_contains(file_get_contents($root . '/' . $relFile), "Route::has('{$name}')")) {
                $guarded = false;
            }
        }

        $message = "Route '{$name}' tidak terdaftar, dipakai di: " . implode(', ', array_slice($usedIn, 0, 3));
        $guarded ? warn($message . ' (aman, dicek dulu dengan Route::has)') : bad($message);
    }
} else {
    ok(count($checkedRoutes) . ' nama route dipanggil, semuanya terdaftar');
}

/* ----------------------------------------------------------------------
 | 4. VIEW YANG DI-INCLUDE / EXTENDS
 * ---------------------------------------------------------------------- */
line();
line('[4/10] View yang di-include / extends...');

$missingViews = [];

foreach (phpFiles($root, ['resources/views']) as $file) {
    $content = file_get_contents($file);

    if (preg_match_all("/@(include|extends)\(\s*['\"]([A-Za-z0-9_.\-\/]+)['\"]/", $content, $matches)) {
        foreach ($matches[2] as $view) {
            if (! View::exists($view)) {
                $missingViews[$view][] = rel($file);
            }
        }
    }
}

if ($missingViews) {
    foreach ($missingViews as $view => $usedIn) {
        bad("View '{$view}' tidak ditemukan, dipakai di: " . implode(', ', array_slice(array_unique($usedIn), 0, 3)));
    }
} else {
    ok('Semua view yang di-include / extends tersedia');
}

/* ----------------------------------------------------------------------
 | 5. MIGRATION
 * ---------------------------------------------------------------------- */
line();
line('[5/10] Migration database...');

try {
    Artisan::call('migrate:status');
    $status  = Artisan::output();
    $pending = preg_match_all('/Pending/i', $status);

    if ($pending > 0) {
        bad("{$pending} migration belum dijalankan. Jalankan: php artisan migrate");
    } else {
        ok('Semua migration sudah dijalankan');
    }
} catch (Throwable $e) {
    bad('Tidak bisa membaca status migration: ' . $e->getMessage());
}

/* ----------------------------------------------------------------------
 | 6. TABEL PENTING
 * ---------------------------------------------------------------------- */
line();
line('[6/10] Tabel penting...');

$tables = [
    'users', 'bookings', 'payments', 'galleries', 'events', 'programs',
    'contact_settings', 'contact_locations', 'notifications', 'schedule_blocks', 'settings',
];

foreach ($tables as $table) {
    try {
        if (! Schema::hasTable($table)) {
            bad("Tabel '{$table}' tidak ada");
            continue;
        }

        $count = DB::table($table)->count();
        ok(str_pad($table, 18) . " {$count} baris");
    } catch (Throwable $e) {
        bad("Tabel '{$table}' tidak bisa dibaca: " . $e->getMessage());
    }
}

if (Schema::hasTable('payments')) {
    foreach (['proof_path', 'proof_uploaded_at'] as $column) {
        if (! Schema::hasColumn('payments', $column)) {
            bad("Kolom payments.{$column} tidak ada (fitur bukti bayar). Jalankan: php artisan migrate");
        }
    }
}

/* ----------------------------------------------------------------------
 | 7. STORAGE LINK
 * ---------------------------------------------------------------------- */
line();
line('[7/10] Storage link (untuk gambar upload)...');

if (file_exists($root . '/public/storage')) {
    ok('public/storage sudah terhubung');
} else {
    bad('public/storage belum ada, gambar upload tidak akan tampil. Jalankan: php artisan storage:link');
}

/* ----------------------------------------------------------------------
 | 8. ERROR DI LOG
 * ---------------------------------------------------------------------- */
line();
line('[8/10] Error terbaru di log Laravel (7 hari terakhir)...');

$logFile = storage_path('logs/laravel.log');

if (! file_exists($logFile)) {
    ok('Belum ada file log (tidak ada error tercatat)');
} else {
    $since  = now()->subDays(7);
    $recent = [];
    $lastAt = [];

    $handle = fopen($logFile, 'r');
    while (($row = fgets($handle)) !== false) {
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)/', $row, $m)) {
            try {
                if (\Carbon\Carbon::parse($m[1])->gte($since)) {
                    $message = mb_substr(trim($m[3]), 0, 160);
                    $recent[$message] = ($recent[$message] ?? 0) + 1;
                    $lastAt[$message] = $m[1];
                }
            } catch (Throwable $e) {
                // abaikan baris yang tidak bisa dibaca
            }
        }
    }
    fclose($handle);

    if (! $recent) {
        ok('Tidak ada error dalam 7 hari terakhir');
    } else {
        warn(count($recent) . ' jenis error tercatat (bisa jadi error lama yang sudah diperbaiki):');
        arsort($recent);
        foreach (array_slice($recent, 0, 8, true) as $message => $times) {
            line("         - {$times}x, terakhir {$lastAt[$message]}: {$message}");
        }
        line('         Cek apakah error di atas masih muncul. Kalau sudah diperbaiki, log bisa dikosongkan.');
    }
}

/* ----------------------------------------------------------------------
 | 9. DATA JANGGAL
 * ---------------------------------------------------------------------- */
line();
line('[9/10] Data yang janggal...');

$dataIssues = 0;

try {
    $orphanPayments = DB::table('payments')
        ->leftJoin('bookings', 'bookings.id', '=', 'payments.booking_id')
        ->whereNull('bookings.id')
        ->count();

    if ($orphanPayments > 0) {
        $dataIssues++;
        warn("{$orphanPayments} data pembayaran tanpa booking");
    }

    $stalePending = DB::table('bookings')
        ->where('status', 'pending')
        ->whereDate('booking_date', '<', today())
        ->count();

    if ($stalePending > 0) {
        $dataIssues++;
        warn("{$stalePending} booking masih PENDING padahal tanggalnya sudah lewat (sebaiknya di-approve / reject)");
    }

    $waiting = DB::table('payments')->where('status', 'verifying')->count();

    if ($waiting > 0) {
        $dataIssues++;
        warn("{$waiting} pembayaran menunggu verifikasi admin");
    }

    $overlaps = DB::table('bookings as a')
        ->join('bookings as b', function ($join) {
            $join->on('a.booking_date', '=', 'b.booking_date')
                ->whereColumn('a.id', '<', 'b.id')
                ->whereColumn('a.start_time', '<', 'b.end_time')
                ->whereColumn('a.end_time', '>', 'b.start_time');
        })
        ->whereIn('a.status', ['pending', 'booked'])
        ->whereIn('b.status', ['pending', 'booked'])
        ->count();

    if ($overlaps > 0) {
        $dataIssues++;
        bad("{$overlaps} pasang booking aktif bentrok di jam yang sama");
    }

    if ($dataIssues === 0) {
        ok('Tidak ada data janggal');
    }
} catch (Throwable $e) {
    warn('Pengecekan data dilewati: ' . $e->getMessage());
}

/* ----------------------------------------------------------------------
 | 10. FILE SISA
 * ---------------------------------------------------------------------- */
line();
line('[10/10] File sisa installer / backup...');

$leftovers = array_merge(
    glob($root . '/install-*.php') ?: [],
    glob($root . '/fix-*.php') ?: [],
    glob($root . '/hapus-*.php') ?: [],
    glob($root . '/samakan-*.php') ?: [],
    glob($root . '/rapikan-*.php') ?: [],
    glob($root . '/notif-*.php') ?: [],
);

$bakCount = 0;
foreach (['app', 'resources', 'routes', 'database', 'bootstrap'] as $dir) {
    if (! is_dir($root . '/' . $dir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (preg_match('/\.bak(-\d{8}_\d{6})?$/', $file->getFilename())) {
            $bakCount++;
        }
    }
}

if (! $leftovers && $bakCount === 0) {
    ok('Tidak ada file sisa');
} else {
    if ($leftovers) {
        warn(count($leftovers) . ' file installer di folder proyek: ' . implode(', ', array_map('basename', $leftovers)));
    }
    if ($bakCount > 0) {
        warn("{$bakCount} file backup .bak (aman, sudah diabaikan Git, boleh dihapus)");
    }
}

/* ----------------------------------------------------------------------
 | RINGKASAN
 * ---------------------------------------------------------------------- */
line();
line('==============================================');

if ($errors === 0 && $warnings === 0) {
    line('  HASIL: SEMUA AMAN, tidak ada masalah ditemukan.');
} elseif ($errors === 0) {
    line("  HASIL: Tidak ada ERROR. Ada {$warnings} hal yang perlu dicek (bukan bug).");
} else {
    line("  HASIL: {$errors} ERROR dan {$warnings} hal yang perlu dicek.");
    line('  Kirim hasil ini ke chat untuk diperbaiki.');
}

line('==============================================');
line();
