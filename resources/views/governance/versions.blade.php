@extends('layout')
@section('title', 'Version History — '.$document->title)
@section('content')

@include('components.back-bar', ['trail' => [
    ['Home', route('home')],
    ['Governance', route('governance.dashboard')],
    ['Kelola', route('governance.index')],
    [$document->title, route('governance.show', $document)],
    ['Version History']
]])

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-xs font-bold tracking-widest text-[#c90018]">VERSION HISTORY</p>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#002f80] mt-1">{{ $document->title }}</h1>
            <p class="text-sm text-slate-500 mt-1">v{{ $document->version }} · {{ strtoupper(str_replace('_', ' ', $document->status)) }} · {{ $document->doc_code ?? '—' }}</p>
        </div>
        <a href="{{ route('governance.show', $document) }}" class="btn-ghost">← Kembali</a>
    </div>

    <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/70">
            <div class="flex items-center justify-between">
                <h2 class="font-extrabold tracking-tight text-[#002f80]">Version History ({{ $versions->total() }} versi)</h2>
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span>{{ $document->versions->count() }} total versi</span>
                    <span class="px-2 py-0.5 rounded-full bg-[#002f80]/10 text-[#002f80]">v{{ $document->version }}</span>
                </div>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($versions as $v)
                <div class="p-5 hover:bg-slate-50/70 transition border-b border-slate-100/50 last:border-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-extrabold bg-[#002f80] text-white px-2.5 py-1 rounded-full shrink-0">v{{ $v->version }}</span>
                            <div>
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ \App\Models\Document::statusBadgeFor($v->to_status) }}">
                                        {{ strtoupper(str_replace('_', ' ', $v->from_status)) }} → <strong>{{ strtoupper(str_replace('_', ' ', $v->to_status)) }}</strong>
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                                    <span>{{ $v->user->name ?? 'sistem' }}</span>
                                    <span>·</span>
                                    <span>{{ $v->created_at->format('d M Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 text-right sm:text-left">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ \App\Models\Document::statusBadgeFor($v->to_status) }}">
                                {{ strtoupper(str_replace('_', ' ', $v->to_status)) }}
                            </span>
                        </div>
                    </div>

                    @if($v->change_note)
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-slate-400 shrink-0">Catatan:</span>
                                <span class="text-sm text-slate-600">{{ $v->change_note }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/70">
            <div class="text-sm text-slate-500">
                Total {{ $versions->total() }} versi · Versi saat ini: v{{ $document->version }} ({{ strtoupper(str_replace('_', ' ', $document->status)) }})
            </div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('governance.show', $document) }}" class="btn-ghost">← Kembali ke Detail</a>
    </div>
@endsection