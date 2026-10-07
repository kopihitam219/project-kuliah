<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Upload file lama (logo, background, galeri, QRIS, bukti transfer) ke Supabase Storage.
 * Jalankan setelah SUPABASE_STORAGE=true di .env.
 */
class PindahFileSupabase extends Command
{
    protected $signature = 'storage:pindah-supabase';

    protected $description = 'Upload file di storage/app ke Supabase Storage';

    public function handle(): int
    {
        if (config('filesystems.disks.public.driver') !== 's3') {
            $this->error('Aktifkan dulu di .env: SUPABASE_STORAGE=true (beserta kunci S3 Supabase).');

            return self::FAILURE;
        }

        $privateRoot = is_dir(storage_path('app/private')) ? storage_path('app/private') : storage_path('app');

        $jobs = [
            'public' => storage_path('app/public'),
            'local'  => $privateRoot,
        ];

        $total  = 0;
        $failed = 0;

        foreach ($jobs as $disk => $root) {
            if (! is_dir($root)) {
                continue;
            }

            $this->info("Disk '{$disk}' ← {$root}");

            foreach (File::allFiles($root) as $file) {
                $relative = str_replace('\\', '/', $file->getRelativePathname());

                if ($file->getFilename() === '.gitignore') {
                    continue;
                }
                if ($disk === 'local' && $root === storage_path('app') && str_starts_with($relative, 'public/')) {
                    continue;
                }

                try {
                    Storage::disk($disk)->put($relative, File::get($file->getPathname()));
                    $this->line('  ✓ ' . $relative);
                    $total++;
                } catch (Throwable $e) {
                    $this->warn('  ✗ ' . $relative . ' : ' . $e->getMessage());
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->info("Selesai: {$total} file diupload" . ($failed ? ", {$failed} gagal" : '') . '.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
