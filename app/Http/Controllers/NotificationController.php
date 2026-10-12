<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pusat notifikasi in-app (BAB 13): lonceng pemberitahuan governance
 * untuk dokumen yang masuk masa review atau diarsipkan otomatis.
 */
class NotificationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $notifications = $user->notifications()->latest()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('dashboard');

        return redirect()->to($url);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('ok', 'Semua notifikasi ditandai terbaca.');
    }
}
