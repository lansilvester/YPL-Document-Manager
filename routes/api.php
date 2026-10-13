<?php

use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CatalogApiController;
use App\Http\Controllers\Api\DocumentApiController;
use App\Http\Controllers\Api\GovernanceApiController;
use App\Http\Controllers\Api\SearchApiController;
use App\Http\Controllers\Api\ServiceApiController;
use App\Http\Controllers\Api\SuperAdminApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| YPL Smart Digital Library — REST API (v1)
|--------------------------------------------------------------------------
| Autentikasi: Laravel Sanctum (personal access token).
|   Authorization: Bearer <token>
| Otorisasi peran memakai middleware yang sama dengan web (staff/admin/superadmin).
| Semua respons JSON; pagination memakai metadata Laravel standar.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ---------------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------------
    Route::post('/auth/login', [AuthApiController::class, 'login'])->middleware('throttle:login')->name('auth.login');
    Route::post('/auth/register', [AuthApiController::class, 'register'])->middleware('throttle:register')->name('auth.register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('auth.logout');
    });

    // ---------------------------------------------------------------------
    // Katalog publik & komunitas (tanpa login; visibilitas tetap dijaga)
    // ---------------------------------------------------------------------
    Route::get('/categories', [CatalogApiController::class, 'categories'])->name('categories.index');
    Route::get('/systems', [CatalogApiController::class, 'systems'])->name('systems.index');
    Route::get('/systems/{system:slug}', [CatalogApiController::class, 'system'])->name('systems.show');
    Route::get('/faqs', [CatalogApiController::class, 'faqs'])->name('faqs.index');
    Route::get('/faqs/{faq:slug}', [CatalogApiController::class, 'faq'])->name('faqs.show');
    Route::get('/units', [CatalogApiController::class, 'units'])->name('units.index');
    Route::get('/contacts', [CatalogApiController::class, 'contacts'])->name('contacts.index');
    Route::get('/events', [CatalogApiController::class, 'events'])->name('events.index');
    Route::get('/events/{event:slug}', [CatalogApiController::class, 'event'])->name('events.show');
    Route::get('/insight', [CatalogApiController::class, 'insight'])->name('insight');
    Route::get('/community', [CatalogApiController::class, 'community'])->name('community');
    Route::get('/help', [CatalogApiController::class, 'help'])->name('help');
    Route::get('/services', [ServiceApiController::class, 'catalog'])->name('services.catalog');

    // ---------------------------------------------------------------------
    // Dokumen (baca) — permission-aware
    // ---------------------------------------------------------------------
    Route::get('/documents', [DocumentApiController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document:slug}', [DocumentApiController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document:slug}/content', [DocumentApiController::class, 'content'])->name('documents.content');
    Route::get('/documents/{document:slug}/related', [DocumentApiController::class, 'related'])->name('documents.related');

    // ---------------------------------------------------------------------
    // Governance
    // ---------------------------------------------------------------------
    Route::get('/governance/documents', [GovernanceApiController::class, 'index'])->name('governance.index');
    Route::get('/governance/documents/{document:slug}', [GovernanceApiController::class, 'show'])->name('governance.show');
    Route::get('/governance/documents/{document:slug}/versions', [GovernanceApiController::class, 'versions'])->name('governance.versions');
    Route::get('/governance/documents/{document:slug}/impact-map', [GovernanceApiController::class, 'impactMap'])->name('governance.impact');

    // ---------------------------------------------------------------------
    // Search & Ask YPL
    // ---------------------------------------------------------------------
    Route::get('/search', [SearchApiController::class, 'search'])->name('search');
    Route::post('/ask', [SearchApiController::class, 'ask'])->middleware('throttle:sensitive')->name('ask');

    // ---------------------------------------------------------------------
    // Terautentikasi (anggota ke atas)
    // ---------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {

        // Profil & keamanan akun
        Route::patch('/profile', [AccountApiController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [AccountApiController::class, 'updatePassword'])->middleware('throttle:sensitive')->name('profile.password');
        Route::get('/profile/stats', [AccountApiController::class, 'stats'])->name('profile.stats');
        Route::post('/profile/role-request', [AccountApiController::class, 'requestRole'])->name('profile.role-request');

        // Bookmark & suka (toggle)
        Route::get('/bookmarks', [AccountApiController::class, 'bookmarks'])->name('bookmarks.index');
        Route::post('/documents/{document:slug}/bookmark', [AccountApiController::class, 'toggleBookmark'])->name('bookmarks.toggle');
        Route::post('/documents/{document:slug}/like', [AccountApiController::class, 'toggleLike'])->name('likes.toggle');

        // Catatan baca
        Route::get('/documents/{document:slug}/notes', [AccountApiController::class, 'notes'])->name('notes.index');
        Route::post('/documents/{document:slug}/notes', [AccountApiController::class, 'storeNote'])->name('notes.store');
        Route::delete('/documents/{document:slug}/notes/{note}', [AccountApiController::class, 'destroyNote'])->name('notes.destroy');

        // Notifikasi
        Route::get('/notifications', [AccountApiController::class, 'notifications'])->name('notifications.index');
        Route::post('/notifications/{id}/read', [AccountApiController::class, 'readNotification'])->name('notifications.read');
        Route::post('/notifications/read-all', [AccountApiController::class, 'readAllNotifications'])->name('notifications.read-all');

        // Layanan — pengajuan & milik sendiri
        Route::post('/service-requests', [ServiceApiController::class, 'store'])->name('service-requests.store');
        Route::get('/service-requests/my', [ServiceApiController::class, 'my'])->name('service-requests.my');

        // Unggah/kelola dokumen (anggota boleh miliknya; staf/admin lebih luas)
        Route::post('/documents', [DocumentApiController::class, 'store'])->middleware('throttle:uploads')->name('documents.store');
        Route::put('/documents/{document:slug}', [DocumentApiController::class, 'update'])->name('documents.update');
        Route::delete('/documents/{document:slug}', [DocumentApiController::class, 'destroy'])->name('documents.destroy');

        // Governance write — transisi lifecycle & relasi
        Route::post('/documents/{document:slug}/transition', [GovernanceApiController::class, 'transition'])->name('governance.transition');
        Route::post('/documents/{document:slug}/relations', [GovernanceApiController::class, 'attachRelation'])->name('governance.relations.store');
        Route::delete('/documents/{document:slug}/relations/{relation}', [GovernanceApiController::class, 'detachRelation'])->name('governance.relations.destroy');

        // -----------------------------------------------------------------
        // Staf (staff | admin | superadmin)
        // -----------------------------------------------------------------
        Route::middleware('staff')->group(function () {
            Route::post('/events', [CatalogApiController::class, 'storeEvent'])->name('events.store');
            Route::put('/events/{event:slug}', [CatalogApiController::class, 'updateEvent'])->name('events.update');
            Route::delete('/events/{event:slug}', [CatalogApiController::class, 'destroyEvent'])->name('events.destroy');

            Route::get('/service-requests', [ServiceApiController::class, 'manage'])->name('service-requests.manage');
            Route::post('/service-requests/{serviceRequest}/decide', [ServiceApiController::class, 'decide'])->middleware('throttle:sensitive')->name('service-requests.decide');
        });

        // -----------------------------------------------------------------
        // Admin (admin | superadmin)
        // -----------------------------------------------------------------
        Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [AdminApiController::class, 'dashboard'])->name('dashboard');
            Route::get('/analytics', [AdminApiController::class, 'analytics'])->name('analytics');
            Route::get('/logs', [AdminApiController::class, 'logs'])->name('logs');

            Route::get('/users', [AdminApiController::class, 'users'])->name('users.index');
            Route::put('/users/{user}', [AdminApiController::class, 'updateUser'])->name('users.update');
            Route::delete('/users/{user}', [AdminApiController::class, 'destroyUser'])->name('users.destroy');

            Route::get('/categories', [AdminApiController::class, 'categories'])->name('categories.index');
            Route::post('/categories', [AdminApiController::class, 'storeCategory'])->name('categories.store');
            Route::put('/categories/{category:slug}', [AdminApiController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{category:slug}', [AdminApiController::class, 'destroyCategory'])->name('categories.destroy');

            Route::get('/units', [AdminApiController::class, 'units'])->name('units.index');
            Route::post('/units', [AdminApiController::class, 'storeUnit'])->name('units.store');
            Route::put('/units/{unit}', [AdminApiController::class, 'updateUnit'])->name('units.update');
            Route::delete('/units/{unit}', [AdminApiController::class, 'destroyUnit'])->name('units.destroy');

            Route::get('/contacts', [AdminApiController::class, 'contacts'])->name('contacts.index');
            Route::post('/contacts', [AdminApiController::class, 'storeContact'])->name('contacts.store');
            Route::put('/contacts/{contact}', [AdminApiController::class, 'updateContact'])->name('contacts.update');
            Route::delete('/contacts/{contact}', [AdminApiController::class, 'destroyContact'])->name('contacts.destroy');

            Route::get('/role-requests', [AdminApiController::class, 'roleRequests'])->name('role-requests.index');
            Route::post('/role-requests/{roleRequest}/approve', [AdminApiController::class, 'approveRoleRequest'])->name('role-requests.approve');
            Route::post('/role-requests/{roleRequest}/reject', [AdminApiController::class, 'rejectRoleRequest'])->name('role-requests.reject');
        });

        // -----------------------------------------------------------------
        // Super Admin
        // -----------------------------------------------------------------
        Route::middleware('superadmin')->prefix('sa')->name('sa.')->group(function () {
            Route::get('/dashboard', [SuperAdminApiController::class, 'dashboard'])->name('dashboard');
            Route::get('/users', [SuperAdminApiController::class, 'users'])->name('users.index');
            Route::put('/users/{user}/role', [SuperAdminApiController::class, 'updateRole'])->name('users.role');
            Route::delete('/users/{user}', [SuperAdminApiController::class, 'destroyUser'])->name('users.destroy');

            Route::get('/settings', [SuperAdminApiController::class, 'settings'])->name('settings');
            Route::put('/settings', [SuperAdminApiController::class, 'updateSettings'])->name('settings.update');

            Route::get('/roles', [SuperAdminApiController::class, 'roles'])->name('roles.index');
            Route::post('/roles', [SuperAdminApiController::class, 'storeRole'])->name('roles.store');
            Route::put('/roles/{role}', [SuperAdminApiController::class, 'updateRoleDefinition'])->name('roles.update');
            Route::delete('/roles/{role}', [SuperAdminApiController::class, 'destroyRole'])->name('roles.destroy');
        });
    });
});
