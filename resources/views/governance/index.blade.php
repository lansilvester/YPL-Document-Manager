@extends('layout')
@section('title', 'Kelola Governance')
@section('content')
<div class="mb-6">
    <p class="text-xs font-bold tracking-widest text-[#c90018]">GOVERNANCE</p>
    <h1 class="page-title">Kelola Dokumen Governance</h1>
</div>

{{-- Filter Bar --}}
<div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-4 mb-6">
    <form action="{{ route('governance.index') }}" method="get" class="flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="tipe" value="{{ $filters['tipe'] ?? '' }}">
        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">

        <div class="flex-1 min-w-0">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul, kode, unit…" class="field !rounded-full" maxlength="200">
        </div>

        <select name="tipe" class="field !w-auto !rounded-full" onchange="this.form.submit()">
            <option value="">Semua Tipe</option>
            @foreach(\App\Models\Document::TYPES as $t)
                <option value="{{ $t }}" {{ ($filters['tipe'] ?? '') === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $t)) }}{{ isset($typeCounts[$t]) ? ' · '.$typeCounts[$t] : '' }}</option>
            @endforeach
        </select>

        <select name="status" class="field !w-auto !rounded-full" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            @foreach(\App\Models\Document::STATUSES as $s)
                <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $s)) }}{{ isset($statusCounts[$s]) ? ' · '.$statusCounts[$s] : '' }}</option>
            @endforeach
        </select>

        <select name="audiens" class="field !w-auto !rounded-full" onchange="this.form.submit()">
            <option value="">Semua Audience</option>
            @foreach(\App\Models\Document::AUDIENCES as $a)
                <option value="{{ $a }}" {{ ($filters['audiens'] ?? '') === $a ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
            @endforeach
        </select>

        <div class="flex gap-1 bg-white border border-[#d6d6d6] rounded-full p-1">
            <input name="unit" value="{{ $filters['unit'] ?? '' }}" placeholder="Cari unit…" class="bg-transparent text-xs px-3 outline-none w-32" aria-label="Cari unit">
            <button class="rounded-full bg-[#002f80] text-white text-xs font-bold px-4 py-1.5">Cari</button>
        </div>

        @if($filters['q'] || $filters['tipe'] || $filters['status'] || $filters['audiens'] || $filters['unit'])
            <a href="{{ route('governance.index') }}" class="text-xs font-bold text-[#c90018]">Reset ✕</a>
        @endif
    </form>
    <form action="{{ route('governance.export') }}" method="post" class="mt-3 flex justify-end">@csrf
        @foreach(['q', 'tipe', 'status', 'audiens', 'unit'] as $k)
            <input type="hidden" name="{{ $k }}" value="{{ $filters[$k] ?? '' }}">
        @endforeach
        <button class="text-xs font-bold text-[#002f80] border border-[#002f80]/30 rounded-full px-4 py-1.5 hover:bg-[#002f80] hover:text-white transition">Ekspor CSV</button>
    </form>
</div>

{{-- Hasil --}}
<div class="grid sm:grid-cols-2 gap-4">
    @forelse($docs as $d)
        <a href="{{ route('governance.show', $d) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block group">
            <div class="flex flex-wrap gap-1.5 text-[10px] font-bold">
                <span class="bg-[#002f80]/5 text-[#002f80] px-2 py-1 rounded-full">{{ $d->doc_type ? strtoupper(str_replace('_', ' ', $d->doc_type)) : 'NO TYPE' }}</span>
                <span class="{{ $d->statusBadge() }} px-2 py-1 rounded-full">{{ strtoupper(str_replace('_', ' ', $d->status)) }}</span>
                <span class="bg-slate-100 text-slate-500 px-2 py-1 rounded-full">v{{ $d->version }}</span>
            </div>
            <div class="font-bold text-slate-900 mt-2 leading-snug line-clamp-2 group-hover:text-[#002f80] transition">{{ $d->title }}</div>
            <div class="text-[11px] text-slate-400 mt-1 flex flex-wrap gap-1">
                <span>{{ $d->doc_code ?? '—' }}</span>
                <span>·</span>
                <span>{{ $d->category->name ?? 'Tanpa kategori' }}</span>
                @if($d->unit)
                    <span>·</span>
                    <span class="bg-[#002f80]/5 text-[#002f80] px-1.5 py-0.5 rounded-full text-[10px] font-bold">{{ $d->unit }}</span>
                @endif
                @if($d->audience && count($d->audience) > 0)
                    <span>·</span>
                    @foreach($d->audience as $a)
                        <span class="bg-[#002f80]/5 text-[#002f80] px-1.5 py-0.5 rounded-full text-[10px] font-bold">{{ str_replace('_', ' ', $a) }}</span>
                    @endforeach
                @endif
            </div>
        </a>
    @empty
        <div class="sm:col-span-2 bg-white rounded-2xl border border-dashed border-[#d6d6d6] p-10 text-center text-slate-400">
            <p class="font-bold text-slate-700">Belum ada dokumen governance pada filter ini.</p>
        </div>
    @endforelse
</div>

<div class="mt-5">{{ $docs->links() }}</div>

{{-- Summary Stats --}}
<div class="mt-6 bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-4">
    <h2 class="font-extrabold text-[#002f80] mb-3">Ringkasan</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-sm">
        @foreach([
            ['Total', $docs->total(), 'users', null],
            ['Active', $statusCounts['active'] ?? 0, 'check', 'active'],
            ['Draft', $statusCounts['draft'] ?? 0, 'file', 'draft'],
            ['Review', $statusCounts['review'] ?? 0, 'eye', 'review'],
            ['Approval', $statusCounts['approval'] ?? 0, 'shield', 'approval'],
            ['Review Due', $statusCounts['review_due'] ?? 0, 'clock', 'review_due'],
            ['Archived', $statusCounts['archived'] ?? 0, 'archive', 'archived'],
            ['Overdue', $overdueCount, 'alert-triangle', null],
        ] as [$label, $count, $icon, $statusLink])
            @if($statusLink)
                <a href="{{ route('governance.index', ['status' => $statusLink]) }}" class="bg-slate-50/70 rounded-xl p-3 hover:border-[#002f80] border border-transparent transition block">
            @else
                <div class="bg-slate-50/70 rounded-xl p-3">
            @endif
                <div class="text-2xl font-extrabold text-[#002f80]">{{ $count }}</div>
                <div class="text-xs text-slate-500 font-semibold">{{ $label }}</div>
            @if($statusLink)
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>
</div>
@endsection