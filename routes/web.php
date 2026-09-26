<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/dokumen', [DocumentController::class, 'index'])->name('documents.index');

Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
Route::post('/daftar', [AuthController::class, 'register']);
Route::get('/email/verifikasi', fn () => view('auth.verify-email'))->middleware('auth')->name('verification.notice');
Route::get('/email/verifikasi/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

Route::get('/dokumen/{document:slug}', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/dokumen/{document:slug}/lihat', [DocumentController::class, 'stream'])->name('documents.stream');
Route::get('/semat/{document:slug}', [DocumentController::class, 'embed'])->name('documents.embed');

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::post('/email/verifikasi/kirim-ulang', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')->name('verification.send');

    Route::get('/unggah', [DocumentController::class, 'create'])->middleware('verified')->name('documents.create');
    Route::post('/unggah', [DocumentController::class, 'store'])->middleware(['verified', 'throttle:uploads'])->name('documents.store');
    Route::get('/dokumen-saya', [DocumentController::class, 'my'])->name('documents.my');
    Route::get('/dokumen/{document:slug}/ubah', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/dokumen/{document:slug}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/dokumen/{document:slug}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/dokumen/{document:slug}/unduh', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/dokumen/{document:slug}/unduh-html', [DocumentController::class, 'downloadHtml'])->name('documents.downloadHtml');
    Route::post('/dokumen/{document:slug}/suka', [InteractionController::class, 'toggleLike'])->middleware('throttle:interactions')->name('documents.like');
    Route::get('/dokumen/{document:slug}/bagikan', [InteractionController::class, 'share'])->name('documents.share');

    Route::get('/tulis', [EditorController::class, 'create'])->middleware('verified')->name('editor.create');
    Route::post('/tulis', [EditorController::class, 'store'])->middleware(['verified', 'throttle:uploads'])->name('editor.store');
    Route::get('/tulis/{document:slug}/ubah', [EditorController::class, 'edit'])->name('editor.edit');
    Route::put('/tulis/{document:slug}', [EditorController::class, 'update'])->name('editor.update');

    Route::post('/dokumen/{document:slug}/favorit', [InteractionController::class, 'toggleFavorite'])->middleware('throttle:interactions')->name('documents.favorite');
    Route::get('/favorit', [InteractionController::class, 'favorites'])->name('favorites.index');

    Route::post('/api/dokumen/{document:slug}/posisi', [InteractionController::class, 'savePosition'])->middleware('throttle:interactions')->name('api.position');
    Route::post('/api/preferensi-baca', [InteractionController::class, 'savePrefs'])->middleware('throttle:interactions')->name('api.prefs');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/kata-sandi', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profil/permintaan-peran', [PromotionController::class, 'store'])->middleware('verified')->name('profile.role-request');

    Route::middleware('staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/dokumen', [StaffController::class, 'index'])->name('documents');
        Route::post('/dokumen', [StaffController::class, 'store'])->middleware('throttle:uploads')->name('documents.store');
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
        Route::get('/persetujuan', [PromotionController::class, 'index'])->name('approvals');
        Route::post('/persetujuan/{request}/setujui', [PromotionController::class, 'approve'])->name('approvals.approve');
        Route::post('/persetujuan/{request}/tolak', [PromotionController::class, 'reject'])->name('approvals.reject');
    });

    Route::middleware('superadmin')->prefix('sa')->name('sa.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/pengguna', [SuperAdminController::class, 'users'])->name('users');
        Route::put('/pengguna/{user}/peran', [SuperAdminController::class, 'updateRole'])->name('users.role');
        Route::delete('/pengguna/{user}', [SuperAdminController::class, 'destroy'])->name('users.destroy');
        Route::get('/pengaturan', [SuperAdminController::class, 'settings'])->name('settings');
        Route::put('/pengaturan', [SuperAdminController::class, 'updateSettings'])->name('settings.update');
    });
});
