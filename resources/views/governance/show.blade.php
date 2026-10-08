@extends('layout')
@section('title', $document->title)
@section('content')

@include('components.governance-detail', [
    'document' => $document,
    'impactMap' => $impactMap,
    'transitions' => $transitions ?? [],
    'isOverdue' => $isOverdue ?? false,
])

{{-- Detail tambahan --}}
<div class="mt-8 space-y-6">

        {{-- Impact Map (ringkas → halaman penuh) --}}
        <a href="{{ route('governance.impact', $document) }}" class="flex items-center gap-3 bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 hover:border-[#002f80] hover:shadow-md transition group">
            <span class="w-10 h-10 rounded-xl grid place-items-center bg-[#002f80]/5 text-[#002f80] group-hover:bg-[#002f80] group-hover:text-white transition shrink-0">@include('components.icon', ['name' => 'git-branch', 'class' => 'w-5 h-5'])</span>
            <span class="min-w-0 flex-1">
                <span class="block font-extrabold text-[#002f80]">IMPACT MAP</span>
                <span class="block text-xs text-slate-500">{{ $impactMap['references']->count() }} referensi keluar · {{ $impactMap['referenced_by']->count() }} dirujuk oleh — buka visualisasi penuh →</span>
            </span>
        </a>

        {{-- Version History --}}
        <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-extrabold tracking-tight text-[#002f80] flex items-center gap-2">
                    @include('components.icon', ['name' => 'clock', 'class' => 'w-5 h-5'])
                    VERSION HISTORY
                </h2>
                <a href="{{ route('governance.versions', $document) }}" class="text-xs font-bold text-[#002f80] hover:underline">Lihat semua {{ $document->versions->count() }} versi →</a>
            </div>
            <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                @foreach($versions->take(10) as $v)
                    <div class="p-4 hover:bg-slate-50/70 transition">
                        <div class="flex items-start gap-3">
                            <span class="text-[11px] font-extrabold bg-[#002f80] text-white px-2 py-0.5 rounded-full shrink-0">v{{ $v->version }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $v->to_status === 'active' ? 'bg-green-50 text-green-700' : ($v->to_status === 'archived' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700') }}">
                                        {{ strtoupper(str_replace('_', ' ', $v->from_status)) }} → <strong>{{ strtoupper(str_replace('_', ' ', $v->to_status)) }}</strong>
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                                    <span>{{ $v->user->name ?? 'sistem' }}</span>
                                    <span>·</span>
                                    <span>{{ $v->created_at->format('d M Y H:i') }}</span>
                                </div>
                                @if($v->change_note)
                                    <div class="text-sm text-slate-600 mt-2 px-8 border-l-2 border-[#002f80]/30 pl-2">{{ $v->change_note }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @if($versions->count() > 10)
                    <div class="p-4 text-center border-t border-slate-100">
                        <a href="{{ route('governance.versions', $document) }}" class="text-xs font-bold text-[#002f80] hover:underline">Lihat semua {{ $versions->count() }} versi →</a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Related Documents (same category) --}}
        @if($relatedSameCat->isNotEmpty())
        <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
            <h2 class="font-extrabold text-[#002f80] mb-3">Dokumen Sejenis ({{ $document->category->name }})</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach($relatedSameCat as $r)
                    <a href="{{ route('governance.show', $r) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block">
                        <div class="font-bold text-sm text-slate-900 leading-snug line-clamp-2 group-hover:text-[#002f80]">{{ $r->title }}</div>
                        <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                            <span class="bg-[#002f80]/5 text-[#002f80] px-2 py-0.5 rounded-full text-[10px] font-bold">{{ strtoupper(str_replace('_', ' ', $r->doc_type ?? 'doc')) }}</span>
                            <span>{{ $r->doc_code ?? '—' }} · v{{ $r->version }} · {{ strtoupper(str_replace('_', ' ', $r->status)) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <a href="{{ route('governance.index', ['tipe' => $document->doc_type, 'status' => 'active']) }}" class="text-xs font-bold text-[#002f80] mt-3 inline-block hover:underline">Lihat semua {{ $document->category->name }} →</a>
        </div>
        @endif
    </div>
</div>
@endsection