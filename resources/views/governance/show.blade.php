@extends('layout')
@section('title', $document->title)
@section('content')

{{-- Breadcrumbs --}}
@include('components.back-bar', ['trail' => [
    ['Home', route('home')],
    ['Governance', route('governance.dashboard')],
    ['Kelola', route('governance.index')],
    [$document->title]
]])

{{-- Document Passport + Detail --}}
<div class="grid lg:grid-cols-[340px_minmax(0,1fr)] gap-6 lg:gap-10 mt-3">
    {{-- Sidebar: Document Passport --}}
    <aside class="lg:sticky lg:top-24 space-y-5">
        {{-- Passport Card --}}
        <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
            <h2 class="font-extrabold tracking-tight text-[#002f80] flex items-center gap-2">
                @include('components.icon', ['name' => 'shield', 'class' => 'w-5 h-5'])
                DOCUMENT PASSPORT
            </h2>
            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-[13px]">
                <div><dt class="text-[11px] font-bold text-slate-400">DOC ID</dt><dd class="font-bold font-mono">{{ $document->doc_code ?? '—' }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">TYPE</dt><dd class="font-bold">{{ $document->doc_type ? strtoupper(str_replace('_', ' ', $document->doc_type)) : '—' }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">VERSION</dt><dd class="font-bold">v{{ $document->version }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">STATUS</dt><dd class="font-bold"><span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 px-2 py-0.5 rounded-full">{{ strtoupper(str_replace('_', ' ', $document->status)) }}</span></dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">CLASS</dt><dd class="font-bold">{{ $document->classification ? strtoupper($document->classification) : '—' }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">EFFECTIVE</dt><dd class="font-bold">{{ $document->effective_at?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">REVIEW</dt><dd class="font-bold">{{ $document->review_at?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">OWNER</dt><dd class="font-bold">{{ $document->user->name }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">APPROVER</dt><dd class="font-bold">{{ $document->approver->name ?? '—' }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">PIC</dt><dd class="font-bold">{{ $document->pic->name ?? $document->pic_name ?? '—' }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">UNIT</dt><dd class="font-bold">{{ $document->unit ?? '—' }}</dd></div>
                @if(!empty($document->audience))
                    <div class="col-span-2"><dt class="text-[11px] font-bold text-slate-400">AUDIENCE</dt><dd class="flex flex-wrap gap-1 mt-1">@foreach($document->audience as $a)<span class="text-[11px] font-bold bg-[#002f80]/5 text-[#002f80] px-2 py-0.5 rounded-full">{{ strtoupper(str_replace('_', ' ', $a)) }}</span>@endforeach</dd></div>
                @endif
                <div><dt class="text-[11px] font-bold text-slate-400">KATEGORI</dt><dd class="font-bold">{{ $document->category->name ?? '—' }}</dd></div>
                <div><dt class="text-[11px] font-bold text-slate-400">DOC CODE</dt><dd class="font-bold font-mono">{{ $document->doc_code ?? '—' }}</dd></div>
            </dl>
        </div>

        {{-- Transitions --}}
        @if(!empty($transitions))
        <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
            <h3 class="font-extrabold text-[#002f80] mb-3">TRANSISI STATUS</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($transitions as $next)
                    <form method="post" action="{{ route('documents.transition', $document) }}" class="inline">@csrf
                        <input type="hidden" name="to" value="{{ $next }}">
                        <button class="text-xs font-bold rounded-full px-4 py-2 {{ $next === 'active' ? 'bg-[#002f80] text-white' : ($next === 'archived' ? 'bg-[#c90018] text-white' : 'border border-[#002f80]/30 text-[#002f80] hover:bg-[#002f80] hover:text-white') }} transition">→ {{ strtoupper(str_replace('_', ' ', $next)) }}</button>
                    </form>
                @endforeach
            </div>
            <p class="text-[11px] text-slate-400 mt-2">Alur: DRAFT → REVIEW → APPROVAL → ACTIVE → REVIEW DUE → ARCHIVED</p>
        </div>
        @endif

        {{-- Overdue Warning --}}
        @if($isOverdue)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <div class="flex items-center gap-2 text-amber-800">
                @include('components.icon', ['name' => 'alert-triangle', 'class' => 'w-5 h-5'])
                <span class="font-bold text-sm">REVIEW OVERDUE</span>
            </div>
            <p class="text-sm text-amber-700 mt-1">Review date: {{ $document->review_at->format('d M Y') }} (lewat {{ $document->review_at->diffForHumans() }})</p>
        </div>
        @endif
    </aside>

    {{-- Main Content --}}
    <div class="min-w-0 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold uppercase tracking-wider">
                    <span class="bg-[#002f80]/5 text-[#002f80] px-2.5 py-1 rounded-full">{{ $document->doc_type ? strtoupper(str_replace('_', ' ', $document->doc_type)) : 'DOCUMENT' }}</span>
                    <span class="bg-green-50 text-green-700 px-2.5 py-1 rounded-full">{{ strtoupper(str_replace('_', ' ', $document->status)) }}</span>
                    <span class="bg-slate-100 text-slate-500 px-2.5 py-1 rounded-full">v{{ $document->version }}</span>
                    @if($document->doc_code)
                        <span class="bg-slate-100 text-slate-500 px-2.5 py-1 rounded-full font-mono text-[10px]">{{ $document->doc_code }}</span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#002f80] mt-2">{{ $document->title }}</h1>
                @if($document->subtitle)
                    <p class="text-slate-500 mt-1 font-reading italic">{{ $document->subtitle }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 shrink-0 mt-3 sm:mt-0">
                @can('transition', [$document, 'active'])
                    <a href="{{ route('governance.show', $document) }}" class="btn-primary text-sm">Buka Detail</a>
                @endcan
                <a href="{{ route('governance.index') }}" class="btn-ghost">← Kembali</a>
            </div>
        </div>

        {{-- Description --}}
        @if($document->description)
        <div class="prose max-w-none text-slate-600 bg-white rounded-2xl border border-[#d6d6d6] p-5">
            {!! \Illuminate\Support\Str::markdown($document->description) !!}
        </div>
        @endif

        {{-- Impact Map --}}
        <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-extrabold tracking-tight text-[#002f80] flex items-center gap-2">
                    @include('components.icon', ['name' => 'git-branch', 'class' => 'w-5 h-5'])
                    IMPACT MAP
                </h2>
            </div>
            <div class="p-5 space-y-5">
                {{-- References (Outgoing) --}}
                @if($impactMap['references']->isNotEmpty())
                <div>
                    <h3 class="text-sm font-extrabold tracking-widest text-slate-400 mb-3">REFERENSI KELUAR (Outgoing)</h3>
                    <div class="space-y-2">
                        @foreach($impactMap['references'] as $ref)
                            <a href="{{ $ref['route'] }}" class="flex items-center gap-3 p-3 bg-slate-50/70 rounded-xl hover:bg-[#002f80]/5 transition group">
                                <span class="text-[10px] font-bold px-2 py-1 rounded-full shrink-0 {{ $ref['target_type'] === 'system' ? 'bg-green-50 text-green-700' : 'bg-[#002f80]/5 text-[#002f80]' }}">{{ strtoupper($ref['type']) }}</span>
                                <span class="font-bold text-slate-900 group-hover:text-[#002f80] truncate flex-1">{{ $ref['title'] }}</span>
                                <span class="text-[10px] font-bold text-slate-400 shrink-0">{{ ucfirst($ref['target_type']) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Referenced By (Incoming) --}}
                @if($impactMap['referenced_by']->isNotEmpty())
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-sm font-extrabold tracking-widest text-slate-400 mb-3">DIRUJUK OLEH (Incoming)</h3>
                    <div class="space-y-2">
                        @foreach($impactMap['referenced_by'] as $ref)
                            <a href="{{ route('documents.show', $ref['slug']) }}" class="flex items-center gap-3 p-3 bg-slate-50/70 rounded-xl hover:bg-[#c90018]/5 transition group">
                                <span class="text-[10px] font-bold bg-[#c90018]/10 text-[#c90018] px-2 py-1 rounded-full shrink-0">{{ strtoupper($ref['type']) }}</span>
                                <span class="font-bold text-slate-900 group-hover:text-[#c90018] truncate flex-1">{{ $ref['source_title'] }}</span>
                                <span class="text-[10px] font-bold text-slate-400 shrink-0">{{ strtoupper(str_replace('_', ' ', $ref['source_type'])) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($impactMap['references']->isEmpty() && $impactMap['referenced_by']->isEmpty())
                    <p class="text-sm text-slate-400 text-center py-8">Belum ada relasi eksplisit. Tambahkan relasi di detail dokumen.</p>
                @endif
            </div>
        </div>

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
        @php
            $relatedSameCat = \App\Models\Document::query()
                ->with(['category', 'user'])
                ->where('visibility', 'public')
                ->where('status', 'active')
                ->where('category_id', $document->category_id)
                ->where('id', '!=', $document->id)
                ->latest()
                ->limit(6)
                ->get();
        @endphp
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