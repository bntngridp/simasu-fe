<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfilController;
use App\Http\Middleware\CekLoginApi;
use App\Http\Controllers\PermintaanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotifikasiController;

Route::get('/', function () {
    if (session()->has('api_token')) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware([CekLoginApi::class])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Announcements CRUD
    Route::get('/dashboard/announcements/create', [DashboardController::class, 'createAnnouncement'])->name('announcements.create');
    Route::post('/dashboard/announcements', [DashboardController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::get('/dashboard/announcements/{id}/edit', [DashboardController::class, 'editAnnouncement'])->name('announcements.edit');
    Route::put('/dashboard/announcements/{id}', [DashboardController::class, 'updateAnnouncement'])->name('announcements.update');
    Route::delete('/dashboard/announcements/{id}', [DashboardController::class, 'deleteAnnouncement'])->name('announcements.delete');

    // Events CRUD
    Route::get('/dashboard/events/create', [DashboardController::class, 'createEvent'])->name('events.create');
    Route::post('/dashboard/events', [DashboardController::class, 'storeEvent'])->name('events.store');
    Route::get('/dashboard/events/{id}/edit', [DashboardController::class, 'editEvent'])->name('events.edit');
    Route::put('/dashboard/events/{id}', [DashboardController::class, 'updateEvent'])->name('events.update');
    Route::delete('/dashboard/events/{id}', [DashboardController::class, 'deleteEvent'])->name('events.delete');

    // Inventaris
    Route::get('/inventaris', [InventarisController::class, 'index'])->name('inventaris');
    Route::post('/inventaris', [InventarisController::class, 'store'])->name('inventaris.store');
    Route::put('/inventaris/{id}', [InventarisController::class, 'update'])->name('inventaris.update');
    Route::delete('/inventaris/{id}', [InventarisController::class, 'destroy'])->name('inventaris.destroy');

    // Ruangan
    Route::get('/ruangan', [RuanganController::class, 'index'])->name('ruangan');
    Route::post('/ruangan', [RuanganController::class, 'store'])->name('ruangan.store');
    Route::get('/ruangan/{id}', [RuanganController::class, 'show'])->name('ruangan.show'); 
    Route::put('/ruangan/{id}', [RuanganController::class, 'update'])->name('ruangan.update');
    Route::delete('/ruangan/{id}', [RuanganController::class, 'destroy'])->name('ruangan.destroy');
    Route::get('/ruangan/{id}/book', [RuanganController::class, 'book'])->name('ruangan.book');

    // Kalender
    Route::get('/kalender', [KalenderController::class, 'index'])->name('kalender');
    Route::post('/kalender', [KalenderController::class, 'store'])->name('kalender.store');
    Route::put('/kalender/{id}/status', [KalenderController::class, 'updateStatus'])->name('kalender.update-status');
    Route::delete('/kalender/{id}', [KalenderController::class, 'destroy'])->name('kalender.destroy');
    Route::get('/kalender/{id}', [KalenderController::class, 'show'])->name('kalender.show');

    // Notifikasi Admin
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');

    // Profil
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil');
    Route::put('/profil/update', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');
    Route::post('/profil/photo', [ProfilController::class, 'updatePhoto'])->name('profil.photo');


    // Permintaan Inventaris
    Route::get('/permintaan', [PermintaanController::class, 'index'])->name('permintaan.index');
    Route::put('/permintaan/{id}/status', [PermintaanController::class, 'updateStatus'])->name('permintaan.update');

    //// Laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export', [LaporanController::class, 'export'])->name('laporan.export');
});