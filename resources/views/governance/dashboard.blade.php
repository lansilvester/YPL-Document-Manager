@extends('layout')
@section('title', 'Dashboard Governance')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-xs font-bold tracking-widest text-[#c90018]">GOVERNANCE</p>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#002f80]">Dashboard Governance</h1>
        <p class="text-sm text-slate-500 mt-1">Ringkasan status dokumen, review mendatang, dan aktivitas terkini.</p>
    </div>
    <a href="{{ route('governance.index') }}" class="btn-primary">Kelola Dokumen</a>
</div>

{{-- Statistik Ringkasan (klik → filter) --}}
<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4 mb-8">
    <a href="{{ route('governance.index') }}" class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 hover:border-[#002f80] hover:shadow-md transition block">
        <div class="text-3xl font-extrabold text-[#002f80]">{{ $stats->total }}</div>
        <div class="text-xs text-slate-500 font-semibold mt-1">Total Dokumen</div>
    </a>
    <a href="{{ route('governance.index', ['status' => 'active']) }}" class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 hover:border-[#002f80] hover:shadow-md transition block">
        <div class="text-3xl font-extrabold text-green-700">{{ $stats->active }}</div>
        <div class="text-xs text-slate-500 font-semibold mt-1">Active</div>
    </a>
    <a href="{{ route('governance.index', ['status' => 'draft']) }}" class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 hover:border-[#002f80] hover:shadow-md transition block">
        <div class="text-3xl font-extrabold text-blue-700">{{ $stats->draft }}</div>
        <div class="text-xs text-slate-500 font-semibold mt-1">Draft</div>
    </a>
    <a href="{{ route('governance.index', ['status' => 'review']) }}" class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 hover:border-[#002f80] hover:shadow-md transition block">
        <div class="text-3xl font-extrabold text-amber-700">{{ $stats->review }}</div>
        <div class="text-xs text-slate-500 font-semibold mt-1">Review</div>
    </a>
    <a href="{{ route('governance.index', ['status' => 'approval']) }}" class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 hover:border-[#002f80] hover:shadow-md transition block">
        <div class="text-3xl font-extrabold text-purple-700">{{ $stats->approval }}</div>
        <div class="text-xs text-slate-500 font-semibold mt-1">Approval</div>
    </a>
    <a href="{{ route('governance.index', ['status' => 'review_due']) }}" class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 hover:border-[#002f80] hover:shadow-md transition block">
        <div class="text-3xl font-extrabold text-orange-700">{{ $stats->review_due }}</div>
        <div class="text-xs text-slate-500 font-semibold mt-1">Review Due</div>
    </a>
    <a href="{{ route('governance.index', ['status' => 'archived']) }}" class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 hover:border-[#002f80] hover:shadow-md transition block">
        <div class="text-3xl font-extrabold text-slate-500">{{ $stats->archived }}</div>
        <div class="text-xs text-slate-500 font-semibold mt-1">Archived</div>
    </a>
    <a href="{{ route('governance.index', ['status' => 'active', 'sort' => 'review_date']) }}" class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 hover:border-[#c90018] hover:shadow-md transition block">
        <div class="text-3xl font-extrabold {{ $stats->overdue_review > 0 ? 'text-[#c90018]' : 'text-green-700' }}">{{ $stats->overdue_review }}</div>
        <div class="text-xs text-slate-500 font-semibold mt-1">Overdue Review</div>
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-8">
    {{-- Distribusi per Tipe & Status --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
        <h2 class="font-extrabold text-[#002f80] mb-4">Distribusi Dokumen per Tipe & Status</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-bold tracking-widest text-slate-400 border-b border-slate-100">
                        <th class="pb-2 px-3">Tipe</th>
                        @foreach(['active','draft','review','approval','review_due','archived'] as $s)
                            <th class="pb-2 px-3 text-center">{{ strtoupper(str_replace('_', ' ', $s)) }}</th>
                        @endforeach
                        <th class="pb-2 px-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($typeStats->sortKeys() as $type => $counts)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-3 py-2 font-bold text-slate-900"><a href="{{ route('governance.index', ['tipe' => $type]) }}" class="hover:text-[#002f80] hover:underline">{{ strtoupper(str_replace('_', ' ', $type)) }}</a></td>
                            @foreach(['active','draft','review','approval','review_due','archived'] as $s)
                                <td class="px-3 py-2 text-center text-slate-600">{{ $counts[$s] ?? 0 }}</td>
                            @endforeach
                            <td class="px-3 py-2 text-right font-bold text-slate-900">{{ array_sum($counts) }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-bold bg-slate-50/70">
                        <td class="px-3 py-2">TOTAL</td>
                        @php
                            $totals = [];
                            foreach(['active','draft','review','approval','review_due','archived'] as $s) {
                                $totals[$s] = $typeStats->sum(fn ($c) => $c[$s] ?? 0);
                            }
                        @endphp
                        @foreach(['active','draft','review','approval','review_due','archived'] as $s)
                            <td class="px-3 py-2 text-center font-bold">{{ $totals[$s] }}</td>
                        @endforeach
                        <td class="px-3 py-2 text-right font-bold">{{ array_sum($totals) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Review Mendatang --}}
    <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-extrabold text-[#002f80]">Review Mendatang (30 Hari)</h2>
            <a href="{{ route('governance.index', ['status' => 'active', 'sort' => 'review_date']) }}" class="text-xs font-bold text-[#002f80] hover:underline">Lihat semua →</a>
        </div>
        @if($upcomingReviews->isEmpty())
            <p class="text-sm text-slate-400 text-center py-4">Tidak ada review mendatang dalam 30 hari.</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($upcomingReviews as $doc)
                    <div class="flex items-center gap-3 py-3 group">
                        <span class="text-[10px] font-bold bg-[#002f80]/5 text-[#002f80] px-2 py-1 rounded-full shrink-0">{{ strtoupper(str_replace('_', ' ', $doc->doc_type ?? 'doc')) }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('governance.show', $doc) }}" class="font-bold text-sm text-slate-900 group-hover:text-[#002f80] truncate block">{{ $doc->title }}</a>
                            <div class="text-[11px] text-slate-400">{{ $doc->review_at->format('d M Y') }} · {{ $doc->category->name ?? '—' }}</div>
                        </div>
                        <span class="text-[11px] font-bold bg-amber-50 text-amber-700 px-2 py-1 rounded-full shrink-0">{{ $doc->review_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    {{-- Perubahan Terbaru --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
        <h2 class="font-extrabold text-[#002f80] mb-4">Perubahan Terbaru (Version History)</h2>
        @if($recentChanges->isEmpty())
            <p class="text-sm text-slate-400 text-center py-4">Belum ada perubahan versi.</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($recentChanges as $v)
                    <div class="flex items-center gap-3 py-3 group">
                        <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded-full shrink-0">v{{ $v->version }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('governance.show', $v->document) }}" class="font-bold text-sm text-slate-900 group-hover:text-[#002f80] truncate block">{{ $v->document->title }}</a>
                            <div class="text-[11px] text-slate-400">{{ $v->from_status }} → <strong>{{ $v->to_status }}</strong> · {{ $v->user->name ?? 'sistem' }} · {{ $v->created_at->format('d M Y H:i') }}</div>
                            @if($v->change_note)
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ $v->change_note }}</div>
                            @endif
                        </div>
                        <span class="text-[10px] font-bold {{ \App\Models\Document::statusBadgeFor($v->to_status) }} px-2 py-0.5 rounded-full shrink-0">{{ strtoupper(str_replace('_', ' ', $v->to_status)) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
        <h2 class="font-extrabold text-[#002f80] mb-3">Aksi Cepat</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ route('documents.create') }}" class="bg-white border border-[#d6d6d6] rounded-2xl p-4 hover:border-[#002f80] hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl grid place-items-center text-white mb-2" style="background:linear-gradient(135deg,#002f80,#0046c0)">✎</div>
                <div class="font-bold text-sm text-slate-900">Dokumen Baru</div>
                <div class="text-xs text-slate-500">Unggah dokumen baru</div>
            </a>
            <a href="{{ route('governance.index', ['status' => 'review']) }}" class="bg-white border border-[#d6d6d6] rounded-2xl p-4 hover:border-[#002f80] hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl grid place-items-center text-white mb-2" style="background:linear-gradient(135deg,#0a4fc4,#002f80)">👁</div>
                <div class="font-bold text-sm text-slate-900">Perlu Review</div>
                <div class="text-xs text-slate-500">{{ $stats->review }} dokumen</div>
            </a>
            <a href="{{ route('governance.index', ['status' => 'approval']) }}" class="bg-white border border-[#d6d6d6] rounded-2xl p-4 hover:border-[#002f80] hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl grid place-items-center text-white mb-2" style="background:linear-gradient(135deg,#7c3aed,#a855f7)">✓</div>
                <div class="font-bold text-sm text-slate-900">Menunggu Approval</div>
                <div class="text-xs text-slate-500">{{ $stats->approval }} dokumen</div>
            </a>
            <a href="{{ route('governance.index', ['status' => 'review_due']) }}" class="bg-white border border-[#d6d6d6] rounded-2xl p-4 hover:border-[#c90018] hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl grid place-items-center text-white mb-2" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">⚠</div>
                <div class="font-bold text-sm text-slate-900">Overdue Review</div>
                <div class="text-xs text-slate-500">{{ $stats->overdue_review }} dokumen</div>
            </a>
        </div>
    </div>
</div>
@endsection