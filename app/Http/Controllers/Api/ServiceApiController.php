<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ServiceRequestResource;
use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceApiController extends ApiController
{
    public function catalog(): JsonResponse
    {
        return $this->ok(
            collect(ServiceRequest::TYPES)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(ServiceRequest::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'needed_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $req = ServiceRequest::create([
            'user_id' => $request->user()->id,
            ...$data,
            'status' => 'pending',
        ]);
        ActivityLog::record($request->user()->id, null, 'service_request', ['id' => $req->id, 'type' => $req->type, 'via' => 'api']);

        return $this->created(new ServiceRequestResource($req), 'Pengajuan dikirim.', route('services.my'));
    }

    public function my(Request $request): JsonResponse
    {
        $reqs = ServiceRequest::where('user_id', $request->user()->id)
            ->latest()->paginate(15)->withQueryString();

        return $this->ok(ServiceRequestResource::collection($reqs));
    }

    public function manage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', Rule::in(ServiceRequest::STATUSES)],
            'type' => ['nullable', 'string', Rule::in(array_keys(ServiceRequest::TYPES))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $reqs = ServiceRequest::query()->with(['user:id,name', 'handler:id,name'])
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($data['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->latest()->paginate($data['per_page'] ?? 15)->withQueryString();

        $counts = ServiceRequest::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return $this->ok([
            'data' => ServiceRequestResource::collection($reqs->items()),
            'meta' => [
                'current_page' => $reqs->currentPage(),
                'last_page' => $reqs->lastPage(),
                'per_page' => $reqs->perPage(),
                'total' => $reqs->total(),
                'counts' => $counts,
            ],
        ]);
    }

    public function decide(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $data = $request->validate([
            'to' => ['required', 'string', Rule::in(ServiceRequest::STATUSES)],
            'handled_note' => ['nullable', 'string', 'max:500'],
        ]);
        abort_unless($serviceRequest->canTransitionTo($data['to']), 422, 'Transisi tidak valid.');

        $serviceRequest->update([
            'status' => $data['to'],
            'handler_id' => $request->user()->id,
            'handled_note' => $data['handled_note'] ?? null,
        ]);
        ActivityLog::record($request->user()->id, null, 'service_decide', ['id' => $serviceRequest->id, 'to' => $data['to'], 'via' => 'api']);

        return $this->ok(new ServiceRequestResource($serviceRequest->fresh()), "Permintaan {$data['to']}.");
    }
}
