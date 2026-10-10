<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
 * Profil customer memakai halaman Blade sendiri (rapi di HP, tidak butuh Vite/Livewire).
 * Nama route lama (profile.edit, security.edit, appearance.edit) tetap ada supaya link lama jalan.
 */
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('settings/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::get('settings/security', fn () => redirect()->to(route('profile.edit') . '#password'))->name('security.edit');
    Route::get('settings/appearance', fn () => redirect()->route('profile.edit'))->name('appearance.edit');
});
