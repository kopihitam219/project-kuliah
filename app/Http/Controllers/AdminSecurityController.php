<?php

namespace App\Http\Controllers;

use App\Support\Security;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Perangkat login & backup database (Settings > Keamanan).
 */
class AdminSecurityController extends Controller
{
    /* ---------------------------------------------------------------
     | PERANGKAT LOGIN
     * --------------------------------------------------------------- */
    public function destroySession(Request $request, string $session): RedirectResponse
    {
        abort_unless(Security::usesDatabaseSessions(), 404);

        if ($session === $request->session()->getId()) {
            return $this->back('error', 'Tidak bisa mengeluarkan perangkat yang sedang dipakai. Gunakan tombol Logout.');
        }

        DB::table(config('session.table', 'sessions'))
            ->where('id', $session)
            ->where('user_id', $request->user()->id)
            ->delete();

        return $this->back('success', 'Perangkat tersebut sudah dikeluarkan.');
    }

    public function destroyOtherSessions(Request $request): RedirectResponse
    {
        abort_unless(Security::usesDatabaseSessions(), 404);

        $count = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return $this->back('success', $count > 0 ? "{$count} perangkat lain sudah dikeluarkan." : 'Tidak ada perangkat lain yang sedang login.');
    }

    /* ---------------------------------------------------------------
     | BACKUP DATABASE (SQLite)
     * --------------------------------------------------------------- */
    public function createBackup(): RedirectResponse
    {
        if (! Security::backupSupported()) {
            return $this->back('error', 'Backup dari aplikasi hanya untuk database SQLite. Untuk MySQL gunakan fitur backup di hosting / phpMyAdmin.');
        }

        $database = config('database.connections.' . config('database.default') . '.database');

        if (! $database || ! File::exists($database)) {
            return $this->back('error', 'File database tidak ditemukan.');
        }

        File::ensureDirectoryExists(Security::backupPath());

        $name = 'backup-' . now()->format('Ymd-His') . '.sqlite';
        File::copy($database, Security::backupPath() . '/' . $name);

        // Simpan 14 backup terakhir saja
        foreach (array_slice(Security::backups(), 14) as $old) {
            File::delete(Security::backupPath() . '/' . $old['name']);
        }

        return $this->back('success', "Backup {$name} berhasil dibuat.");
    }

    public function downloadBackup(string $file): BinaryFileResponse
    {
        $path = $this->backupFile($file);

        return response()->download($path);
    }

    public function deleteBackup(string $file): RedirectResponse
    {
        File::delete($this->backupFile($file));

        return $this->back('success', "Backup {$file} dihapus.");
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    private function backupFile(string $file): string
    {
        // Hanya nama file backup yang valid, tidak boleh keluar dari folder backup
        abort_unless(preg_match('/^backup-\d{8}-\d{6}\.sqlite$/', $file), 404);

        $path = Security::backupPath() . '/' . $file;

        abort_unless(File::exists($path), 404);

        return $path;
    }

    private function back(string $type, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.settings.index', ['tab' => 'security'])
            ->with($type, $message);
    }
}
