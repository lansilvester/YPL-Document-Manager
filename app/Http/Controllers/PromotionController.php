<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\RoleRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Alur peningkatan otoritas: Anggota mengajukan, Admin/Super Admin menyetujui.
 * - staff  : dapat disetujui Admin atau Super Admin
 * - admin  : hanya dapat disetujui Super Admin
 * - audiens (BAB 11): penetapan preferensi pengalaman, disetujui Admin/Super Admin
 * - superadmin: tidak dapat diminta (1 akun, milik developer)
 */
class PromotionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'requested_role' => ['nullable', 'in:staff,admin'],
            'requested_audience' => ['nullable', Rule::in(User::AUDIENCES)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($data['requested_role']) === empty($data['requested_audience'])) {
            return back()->withErrors(['requested_role' => 'Pilih salah satu: peran atau audiens.']);
        }

        $user = $request->user();

        // Pengajuan peran: hanya untuk anggota (belum berperan lebih tinggi).
        if (! empty($data['requested_role']) && in_array($user->role, ['staff', 'admin', 'superadmin'], true)) {
            return back()->withErrors(['requested_role' => 'Peranmu sudah setara atau lebih tinggi.']);
        }
        // Pengajuan audiens: tidak bermakna bila sudah sama.
        if (! empty($data['requested_audience']) && $user->audience === $data['requested_audience']) {
            return back()->withErrors(['requested_audience' => 'Audiensmu sudah '.$data['requested_audience'].'.']);
        }

        if (RoleRequest::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return back()->withErrors(['requested_role' => 'Kamu masih memiliki pengajuan yang menunggu ditinjau.']);
        }

        RoleRequest::create([
            'user_id' => $user->id,
            'requested_role' => $data['requested_role'] ?? null,
            'requested_audience' => $data['requested_audience'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('ok', 'Pengajuan dikirim. Tunggu persetujuan Admin YPL.');
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

        if ($request->requested_audience) {
            $request->user->update(['audience' => $request->requested_audience]);
            $label = 'audiens '.$request->requested_audience;
            $log = ['user' => $request->user->email, 'audience' => $request->requested_audience];
        } else {
            $request->user->update(['role' => $request->requested_role]);
            $label = 'peran '.$request->requested_role;
            $log = ['user' => $request->user->email, 'role' => $request->requested_role];
        }

        $request->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        ActivityLog::record(auth()->id(), null, 'role_approve', $log);

        return back()->with('ok', "Pengajuan {$request->user->name} ({$label}) disetujui.");
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
        // Peran admin hanya boleh disetujui Super Admin. Audiens cukup Admin.
        if ($request->requested_role === 'admin' && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Peningkatan ke peran admin hanya boleh disetujui Super Admin.');
        }
    }
}
