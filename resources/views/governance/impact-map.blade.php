@extends('layout')
@section('title', 'Impact Map — '.$document->title)
@section('content')

@include('components.back-bar', ['trail' => [
    ['Home', route('home')],
    ['Governance', route('governance.index')],
    ['Kelola', route('governance.index')],
    [$document->title, route('governance.show', $document)],
    ['Impact Map']
]])

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-xs font-bold tracking-widest text-[var(--ypl-red)]">IMPACT MAP</p>
            <h1 class="page-title mt-1">{{ $document->title }}</h1>
            <p class="text-sm text-slate-500 mt-1">Visualisasi relasi dokumen & sistem terkait</p>
        </div>
        <a href="{{ route('governance.show', $document) }}" class="btn-ghost">← Kembali</a>
    </div>

    @if($impactMap['references']->isEmpty() && $impactMap['referenced_by']->isEmpty() && count($graph['edges']) === 0)
        <div class="bg-white rounded-2xl border border-dashed border-[var(--ypl-gray)] p-12 text-center">
            @include('components.icon', ['name' => 'git-branch', 'class' => 'w-16 h-16 text-slate-300 mx-auto mb-4'])
            <h2 class="text-xl font-extrabold text-slate-900">Belum Ada Relasi</h2>
            <p class="text-slate-500 mt-2">Dokumen ini belum memiliki relasi eksplisit dengan dokumen atau sistem lain.</p>
            <p class="text-slate-400 text-sm mt-1">Tambahkan relasi di halaman detail untuk memulai visualisasi impact map.</p>
            <a href="{{ route('governance.show', $document) }}" class="btn-primary mt-4 inline-block">Kelola Relasi →</a>
        </div>
    @else
        {{-- Graf knowledge node-link (BAB 12): everything related, tanpa pencarian ulang --}}
        <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                <h2 class="font-extrabold text-[var(--ypl-navy)]">Graf Knowledge — {{ $document->title }}</h2>
                <div class="flex items-center gap-1 text-xs font-bold" role="group" aria-label="Kedalaman graf">
                    <span class="text-slate-400 mr-1">KEDALAMAN</span>
                    <a href="{{ route('governance.impact', ['document' => $document->slug, 'kedalaman' => 1]) }}" class="px-3 py-1.5 rounded-full {{ $depth === 1 ? 'bg-[var(--ypl-navy)] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">1</a>
                    <a href="{{ route('governance.impact', ['document' => $document->slug, 'kedalaman' => 2]) }}" class="px-3 py-1.5 rounded-full {{ $depth === 2 ? 'bg-[var(--ypl-navy)] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">2</a>
                </div>
            </div>
            <p class="text-sm text-slate-500 mb-4">Simpul = dokumen, sistem, agenda, FAQ, dan PIC yang tertaut langsung maupun dua langkah dari dokumen ini. Klik simpul untuk membuka. Hanya konten yang terlihat oleh Anda yang tampil.</p>
            @include('components.knowledge-graph', ['graph' => $graph, 'id' => 'kg-impact', 'label' => 'Graf relasi untuk '.$document->title])
            @if($graph['truncated'] > 0)
                <p class="text-xs text-slate-400 mt-2">+{{ $graph['truncated'] }} simpul lain disembunyikan agar graf tetap terbaca — gunakan daftar di bawah untuk melihat semuanya.</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-6">
            <h2 class="font-extrabold text-[var(--ypl-navy)] mb-4">Impact Map — {{ $document->title }}</h2>
            <p class="text-sm text-slate-500 mb-6">Relasi keluar (outgoing) = dokumen/sistem yang direferensikan. Relasi masuk (incoming) = dokumen yang mereferensikan dokumen ini.</p>

            <div class="grid lg:grid-cols-2 gap-6">
                {{-- Outgoing References --}}
                <div class="bg-slate-50/70 rounded-2xl p-5">
                    <h3 class="font-extrabold text-[var(--ypl-navy)] mb-4 flex items-center gap-2">
                        @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-5 h-5'])
                        REFERENSI KELUAR (Outgoing) <span class="text-xs font-bold bg-[var(--ypl-navy)] text-white px-2 py-0.5 rounded-full">{{ $impactMap['references']->count() }}</span>
                    </h3>
                    @if($impactMap['references']->isEmpty())
                        <p class="text-sm text-slate-400 text-center py-8">Tidak ada referensi keluar.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($impactMap['references'] as $ref)
                                <a href="{{ $ref['route'] }}" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-[var(--ypl-gray)] hover:border-[var(--ypl-navy)] hover:shadow-md transition group">
                                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full shrink-0 {{ $ref['target_type'] === 'system' ? 'bg-green-50 text-green-700' : 'bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)]' }}">{{ strtoupper($ref['type']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-slate-900 group-hover:text-[var(--ypl-navy)] truncate block">{{ $ref['title'] }}</div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $ref['target_type'] === 'system' ? 'bg-green-50 text-green-700' : 'bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)]' }}">{{ ucfirst($ref['target_type']) }}</span>
                                        </div>
                                    </div>
                                    @include('components.icon', ['name' => 'chev-right', 'class' => 'w-5 h-5 text-slate-300 group-hover:text-[var(--ypl-navy)] shrink-0'])
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Incoming References --}}
                <div class="bg-slate-50/70 rounded-2xl p-5">
                    <h3 class="font-extrabold text-[var(--ypl-red)] mb-4 flex items-center gap-2">
                        @include('components.icon', ['name' => 'arrow-left', 'class' => 'w-5 h-5'])
                        DIRUJUK OLEH (Incoming) <span class="text-xs font-bold bg-[var(--ypl-red)] text-white px-2 py-0.5 rounded-full">{{ $impactMap['referenced_by']->count() }}</span>
                    </h3>
                    @if($impactMap['referenced_by']->isEmpty())
                        <p class="text-sm text-slate-400 text-center py-8">Belum ada dokumen yang mereferensikan dokumen ini.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($impactMap['referenced_by'] as $ref)
                                <a href="{{ route('governance.show', $ref['slug']) }}" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-[var(--ypl-gray)] hover:border-[var(--ypl-red)] hover:shadow-md transition group">
                                    <span class="text-[10px] font-bold bg-[var(--ypl-red)]/10 text-[var(--ypl-red)] px-2.5 py-1 rounded-full shrink-0">{{ strtoupper($ref['type']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-bold text-slate-900 group-hover:text-[var(--ypl-red)] truncate block">{{ $ref['source_title'] }}</span>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[var(--ypl-red)]/10 text-[var(--ypl-red)]">{{ str_replace('_', ' ', $ref['source_type']) }}</span>
                                        </div>
                                    </div>
                                    @include('components.icon', ['name' => 'chev-right', 'class' => 'w-5 h-5 text-slate-300 group-hover:text-[var(--ypl-red)] shrink-0'])
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-6 bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-4">
            <h3 class="font-extrabold text-[var(--ypl-navy)] mb-3">Legenda</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-sm">
                <div class="flex items-center gap-2 p-2 bg-green-50 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-green-500 shrink-0"></span>
                    <span class="text-green-700 font-semibold">System</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-violet-50 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-violet-600 shrink-0"></span>
                    <span class="text-violet-700 font-semibold">Agenda</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-sky-50 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-sky-600 shrink-0"></span>
                    <span class="text-sky-700 font-semibold">FAQ</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-slate-100 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-slate-500 shrink-0"></span>
                    <span class="text-slate-600 font-semibold">PIC / Orang</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-[var(--ypl-navy)]/5 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-[var(--ypl-navy)] shrink-0"></span>
                    <span class="text-[var(--ypl-navy)] font-semibold">Policy / Regulation</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-amber-50 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-amber-700 font-semibold">SOP / Work Instruction</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-red-50 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span>
                    <span class="text-red-700 font-semibold">Form / Evidence</span>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-4 text-center">
        <a href="{{ route('governance.show', $document) }}" class="btn-ghost">← Kembali ke Detail</a>
    </div>
@endsection