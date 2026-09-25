<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/dokumen', [DocumentController::class, 'index'])->name('documents.index');

Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
Route::post('/masuk', [AuthController::class, 'login']);
Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
Route::post('/daftar', [AuthController::class, 'register']);

Route::get('/dokumen/{document:slug}', [DocumentController::class, 'show'])->name('documents.show');

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

    Route::get('/unggah', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/unggah', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/dokumen-saya', [DocumentController::class, 'my'])->name('documents.my');
    Route::get('/dokumen/{document:slug}/ubah', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/dokumen/{document:slug}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/dokumen/{document:slug}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/dokumen/{document:slug}/unduh', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/dokumen/{document:slug}/bagikan', [InteractionController::class, 'share'])->name('documents.share');

    Route::get('/tulis', [EditorController::class, 'create'])->name('editor.create');
    Route::post('/tulis', [EditorController::class, 'store'])->name('editor.store');
    Route::get('/tulis/{document:slug}/ubah', [EditorController::class, 'edit'])->name('editor.edit');
    Route::put('/tulis/{document:slug}', [EditorController::class, 'update'])->name('editor.update');

    Route::post('/dokumen/{document:slug}/favorit', [InteractionController::class, 'toggleFavorite'])->name('documents.favorite');
    Route::get('/favorit', [InteractionController::class, 'favorites'])->name('favorites.index');

    Route::post('/api/dokumen/{document:slug}/posisi', [InteractionController::class, 'savePosition'])->name('api.position');
    Route::post('/api/preferensi-baca', [InteractionController::class, 'savePrefs'])->name('api.prefs');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/dokumen', [StaffController::class, 'index'])->name('documents');
        Route::post('/dokumen', [StaffController::class, 'store'])->name('documents.store');
        Route::put('/dokumen/{document}', [StaffController::class, 'update'])->name('documents.update');
        Route::delete('/dokumen/{document}', [StaffController::class, 'destroy'])->name('documents.destroy');
        Route::post('/badge', [StaffController::class, 'storeTag'])->name('tags.store');
        Route::delete('/badge/{tag}', [StaffController::class, 'destroyTag'])->name('tags.destroy');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/kategori', [AdminController::class, 'categories'])->name('categories');
        Route::post('/kategori', [AdminController::class, 'storeCategory'])->name('categories.store');
        Route::delete('/kategori/{category:slug}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');
        Route::get('/riwayat', [AdminController::class, 'logs'])->name('logs');
    });
});
