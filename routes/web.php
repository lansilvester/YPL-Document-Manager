<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AskController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/dokumen', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/cari', [SearchController::class, 'index'])->name('search.index');

// YPL SDL Phase 1 — Information Architecture.
Route::get('/perpustakaan', [LibraryController::class, 'index'])->name('library.index');
// Peta Pengetahuan (BAB 12) — bagian dari Library: eksplorasi graf permission-aware.
Route::get('/perpustakaan/peta-pengetahuan', [LibraryController::class, 'graph'])->name('library.graph');
Route::get('/perpustakaan/{category:slug}', [LibraryController::class, 'show'])->name('library.show');

// Governance (BAB 13) — Document Control & Governance
Route::get('/governance', [GovernanceController::class, 'index'])->name('governance.index');
Route::post('/governance/ekspor', [GovernanceController::class, 'export'])->middleware('throttle:sensitive')->name('governance.export');
Route::get('/governance/{document:slug}', [GovernanceController::class, 'show'])->name('governance.show');
Route::get('/governance/{document:slug}/versi', [GovernanceController::class, 'versions'])->name('governance.versions');
Route::get('/governance/{document:slug}/impact', [GovernanceController::class, 'impactMap'])->name('governance.impact');

// URL lama Peta Pengetahuan → dipertahankan sebagai redirect agar tautan tetap hidup.
Route::redirect('/peta-pengetahuan', '/perpustakaan/peta-pengetahuan', 301);

Route::get('/layanan', [PortalController::class, 'services'])->name('services.index');
Route::get('/help', [PortalController::class, 'help'])->name('help.index');
Route::get('/community', [PortalController::class, 'community'])->name('community.index');
Route::get('/calendar', [PortalController::class, 'calendar'])->name('calendar.index');
Route::get('/tanya', [AskController::class, 'index'])->name('ask.index');
Route::post('/tanya', [AskController::class, 'ask'])->middleware('throttle:sensitive')->name('ask.query');
Route::get('/insight', [PortalController::class, 'insight'])->name('insight.index');
Route::get('/sistem', [SystemController::class, 'index'])->name('systems.index');
Route::get('/sistem/{system:slug}', [SystemController::class, 'show'])->name('systems.show');
Route::get('/calendar/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/faq/{faq:slug}', [FaqController::class, 'show'])->name('faqs.show');

Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:register');

Route::get('/lupa-password', [AuthController::class, 'showForgot'])->name('password.request');
Route::post('/lupa-password', [AuthController::class, 'sendResetToken'])->middleware('throttle:sensitive')->name('password.email');
Route::get('/reset-password', [AuthController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:sensitive')->name('password.update');

Route::get('/dokumen/{document:slug}', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/baca/{document:slug}', [ReaderController::class, 'show'])->name('reader.show');
Route::get('/dokumen/{document:slug}/lihat', [DocumentController::class, 'stream'])->name('documents.stream');
Route::get('/semat/{document:slug}', [DocumentController::class, 'embed'])->name('documents.embed');

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

    Route::get('/unggah', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/unggah', [DocumentController::class, 'store'])->middleware('throttle:uploads')->name('documents.store');
    Route::get('/dokumen-saya', [DocumentController::class, 'my'])->name('documents.my');
    Route::get('/dokumen/{document:slug}/ubah', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/dokumen/{document:slug}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/dokumen/{document:slug}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/dokumen/{document:slug}/unduh', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/dokumen/{document:slug}/unduh-html', [DocumentController::class, 'downloadHtml'])->name('documents.downloadHtml');
    Route::post('/dokumen/{document:slug}/suka', [InteractionController::class, 'toggleLike'])->middleware('throttle:interactions')->name('documents.like');
    Route::get('/dokumen/{document:slug}/bagikan', [InteractionController::class, 'share'])->name('documents.share');

    Route::get('/tulis', [EditorController::class, 'create'])->name('editor.create');
    Route::post('/tulis', [EditorController::class, 'store'])->middleware('throttle:uploads')->name('editor.store');
    Route::get('/tulis/{document:slug}/ubah', [EditorController::class, 'edit'])->name('editor.edit');
    Route::put('/tulis/{document:slug}', [EditorController::class, 'update'])->name('editor.update');

    Route::post('/dokumen/{document:slug}/bookmark', [InteractionController::class, 'toggleBookmark'])->middleware('throttle:interactions')->name('documents.bookmark');
    Route::get('/bookmark', [InteractionController::class, 'bookmarks'])->name('bookmarks.index');

    // Phase 4 — Services: pengajuan layanan + permintaan saya.
    Route::get('/layanan/minta/{type}', [ServiceRequestController::class, 'create'])->name('services.request');
    Route::post('/layanan/minta', [ServiceRequestController::class, 'store'])->middleware('throttle:sensitive')->name('services.store');
    Route::get('/permintaan-saya', [ServiceRequestController::class, 'my'])->name('services.my');

    // Phase 3 — Governance lifecycle + connected knowledge.
    Route::post('/dokumen/{document:slug}/transisi', [DocumentController::class, 'transition'])->middleware('throttle:sensitive')->name('documents.transition');
    Route::post('/dokumen/{document:slug}/relasi', [DocumentController::class, 'attachRelation'])->middleware('throttle:sensitive')->name('documents.relations.store');
    Route::delete('/dokumen/{document:slug}/relasi/{relation}', [DocumentController::class, 'detachRelation'])->middleware('throttle:sensitive')->name('documents.relations.destroy');

    Route::post('/api/dokumen/{document:slug}/posisi', [InteractionController::class, 'savePosition'])->middleware('throttle:interactions')->name('api.position');
    Route::post('/api/preferensi-baca', [InteractionController::class, 'savePrefs'])->middleware('throttle:interactions')->name('api.prefs');
    Route::post('/api/dokumen/{document:slug}/catatan', [InteractionController::class, 'storeNote'])->middleware('throttle:interactions')->name('api.notes.store');
    Route::delete('/api/dokumen/{document:slug}/catatan/{note}', [InteractionController::class, 'destroyNote'])->middleware('throttle:interactions')->name('api.notes.destroy');

    // Phase 6 — Role Dashboard (BAB 11): satu dashboard, konten menyesuaikan peran.
    Route::get('/dashboard', [RoleDashboardController::class, 'index'])->name('dashboard');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/kata-sandi', [ProfileController::class, 'updatePassword'])->middleware('throttle:sensitive')->name('profile.password');
    Route::post('/profil/permintaan-peran', [PromotionController::class, 'store'])->middleware('throttle:sensitive')->name('profile.role-request');

    Route::middleware('staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/dokumen', [StaffController::class, 'index'])->name('documents');
        Route::post('/dokumen', [StaffController::class, 'store'])->middleware('throttle:uploads')->name('documents.store');
        Route::put('/dokumen/{document}', [StaffController::class, 'update'])->name('documents.update');
        Route::delete('/dokumen/{document}', [StaffController::class, 'destroy'])->name('documents.destroy');
        Route::post('/badge', [StaffController::class, 'storeTag'])->name('tags.store');
        Route::delete('/badge/{tag}', [StaffController::class, 'destroyTag'])->name('tags.destroy');
        Route::get('/permintaan', [ServiceRequestController::class, 'manage'])->name('requests');
        Route::post('/permintaan/{serviceRequest}/putus', [ServiceRequestController::class, 'decide'])->middleware('throttle:sensitive')->name('requests.decide');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/kategori', [AdminController::class, 'categories'])->name('categories');
        Route::post('/kategori', [AdminController::class, 'storeCategory'])->name('categories.store');
        Route::put('/kategori/{category:slug}', [AdminController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/kategori/{category:slug}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');
        Route::get('/pengguna', [AdminController::class, 'users'])->name('users');
        Route::put('/pengguna/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/pengguna/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
        Route::get('/riwayat', [AdminController::class, 'logs'])->name('logs');
        Route::get('/unit', [UnitController::class, 'index'])->name('units');
        Route::post('/unit', [UnitController::class, 'store'])->name('units.store');
        Route::put('/unit/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::delete('/unit/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
        Route::get('/kontak', [ContactController::class, 'index'])->name('contacts');
        Route::post('/kontak', [ContactController::class, 'store'])->name('contacts.store');
        Route::put('/kontak/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('/kontak/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
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
        Route::get('/peran', [SuperAdminController::class, 'roles'])->name('roles');
        Route::post('/peran', [SuperAdminController::class, 'storeRole'])->name('roles.store');
        Route::put('/peran/{role}', [SuperAdminController::class, 'updateRoleDefinition'])->name('roles.update');
        Route::delete('/peran/{role}', [SuperAdminController::class, 'destroyRole'])->name('roles.destroy');
    });
});
