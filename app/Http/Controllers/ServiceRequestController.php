<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceRequestController extends Controller
{
    /**
     * Phase 4 — formulir pengajuan layanan ("Saya ingin…").
     */
    public function create(string $type): mixed
    {
        abort_unless(array_key_exists($type, ServiceRequest::TYPES), 404);

        return view('service_requests.create', ['type' => $type]);
    }

    public function store(Request $request): mixed
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(ServiceRequest::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'needed_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $req = ServiceRequest::create([
            'user_id' => auth()->id(),
            ...$data,
            'status' => 'pending',
        ]);
        ActivityLog::record(auth()->id(), null, 'service_request', ['id' => $req->id, 'type' => $req->type]);

        return redirect()->route('services.my')->with('ok', 'Pengajuan dikirim. Pantau status di Permintaan Saya.');
    }

    public function my(): mixed
    {
        $reqs = ServiceRequest::where('user_id', auth()->id())->latest()->paginate(12);

        return view('service_requests.my', compact('reqs'));
    }

    public function manage(Request $request): mixed
    {
        $status = $request->query('status');
        $type = $request->query('tipe');

        $reqs = ServiceRequest::query()->with(['user'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()->paginate(15)->withQueryString();

        $counts = ServiceRequest::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('service_requests.manage', [
            'reqs' => $reqs, 'counts' => $counts,
            'activeStatus' => $status, 'activeType' => $type,
        ]);
    }

    public function decide(Request $request, ServiceRequest $serviceRequest): mixed
    {
        $data = $request->validate([
            'to' => ['required', 'string', Rule::in(ServiceRequest::STATUSES)],
            'handled_note' => ['nullable', 'string', 'max:500'],
        ]);
        abort_unless($serviceRequest->canTransitionTo($data['to']), 422, 'Transisi tidak valid.');

        $serviceRequest->update([
            'status' => $data['to'],
            'handler_id' => auth()->id(),
            'handled_note' => $data['handled_note'] ?? null,
        ]);
        ActivityLog::record(auth()->id(), null, 'service_decide', ['id' => $serviceRequest->id, 'to' => $data['to']]);

        return back()->with('ok', "Permintaan {$data['to']}.");
    }
}
