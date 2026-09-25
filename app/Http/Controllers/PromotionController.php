<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\RoleRequest;
use Illuminate\Http\Request;

/**
 * Alur peningkatan otoritas: Anggota mengajukan, Admin/Super Admin menyetujui.
 * - staff  : dapat disetujui Admin atau Super Admin
 * - admin  : hanya dapat disetujui Super Admin
 * - superadmin: tidak dapat diminta (1 akun, milik developer)
 */
class PromotionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'requested_role' => ['required', 'in:staff,admin'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        if (in_array($user->role, ['staff', 'admin', 'superadmin'], true)) {
            return back()->withErrors(['requested_role' => 'Peranmu sudah setara atau lebih tinggi.']);
        }
        if (RoleRequest::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return back()->withErrors(['requested_role' => 'Kamu masih memiliki pengajuan yang menunggu ditinjau.']);
        }

        RoleRequest::create([
            'user_id' => $user->id,
            'requested_role' => $data['requested_role'],
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('ok', 'Pengajuan peningkatan peran dikirim. Tunggu persetujuan Admin YPL.');
    }

    public function index()
    {
        $requests = RoleRequest::with(['user', 'reviewer'])->latest()->paginate(20);

        return view('admin.role-requests', compact('requests'));
    }

    public function approve(RoleRequest $request)
    {
        $this->authorizeReview($request);

        if ($request->status !== 'pending') {
            return back()->withErrors(['status' => 'Pengajuan sudah ditinjau.']);
        }

        $request->user->update(['role' => $request->requested_role]);
        $request->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        ActivityLog::record(auth()->id(), null, 'role_approve', [
            'user' => $request->user->email, 'role' => $request->requested_role,
        ]);

        return back()->with('ok', "Peran {$request->user->name} ditingkatkan menjadi {$request->requested_role}.");
    }

    public function reject(RoleRequest $request)
    {
        $this->authorizeReview($request);

        if ($request->status !== 'pending') {
            return back()->withErrors(['status' => 'Pengajuan sudah ditinjau.']);
        }

        $request->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('ok', 'Pengajuan ditolak.');
    }

    private function authorizeReview(RoleRequest $request): void
    {
        // Peran admin hanya boleh disetujui Super Admin
        if ($request->requested_role === 'admin' && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Peningkatan ke peran admin hanya boleh disetujui Super Admin.');
        }
    }
}
