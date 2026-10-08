@extends('layout')
@section('title', 'Impact Map — '.$document->title)
@section('content')

@include('components.back-bar', ['trail' => [
    ['Home', route('home')],
    ['Governance', route('governance.dashboard')],
    ['Kelola', route('governance.index')],
    [$document->title, route('governance.show', $document)],
    ['Impact Map']
]])

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-xs font-bold tracking-widest text-[#c90018]">IMPACT MAP</p>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#002f80] mt-1">{{ $document->title }}</h1>
            <p class="text-sm text-slate-500 mt-1">Visualisasi relasi dokumen & sistem terkait</p>
        </div>
        <a href="{{ route('governance.show', $document) }}" class="btn-ghost">← Kembali</a>
    </div>

    @if($impactMap['references']->isEmpty() && $impactMap['referenced_by']->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-[#d6d6d6] p-12 text-center">
            @include('components.icon', ['name' => 'git-branch', 'class' => 'w-16 h-16 text-slate-300 mx-auto mb-4'])
            <h2 class="text-xl font-extrabold text-slate-900">Belum Ada Relasi</h2>
            <p class="text-slate-500 mt-2">Dokumen ini belum memiliki relasi eksplisit dengan dokumen atau sistem lain.</            <p class="text-slate-400 text-sm mt-1">Tambahkan relasi di halaman detail untuk memulai visualisasi impact map.</p>
            <a href="{{ route('governance.show', $document) }}" class="btn-primary mt-4 inline-block">Kelola Relasi →</a>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-6">
            <h2 class="font-extrabold text-[#002f80] mb-4">Impact Map — {{ $document->title }}</h2>
            <p class="text-sm text-slate-500 mb-6">Relasi keluar (outgoing) = dokumen/sistem yang direferensikan. Relasi masuk (incoming) = dokumen yang mereferensikan dokumen ini.</p>

            <div class="grid lg:grid-cols-2 gap-6">
                {{-- Outgoing References --}}
                <div class="bg-slate-50/70 rounded-2xl p-5">
                    <h3 class="font-extrabold text-[#002f80] mb-4 flex items-center gap-2">
                        @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-5 h-5'])
                        REFERENSI KELUAR (Outgoing) <span class="text-xs font-bold bg-[#002f80] text-white px-2 py-0.5 rounded-full">{{ $impactMap['references']->count() }}</span>
                    </h3>
                    @if($impactMap['references']->isEmpty())
                        <p class="text-sm text-slate-400 text-center py-8">Tidak ada referensi keluar.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($impactMap['references'] as $ref)
                                <a href="{{ $ref['route'] }}" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-[#d6d6d6] hover:border-[#002f80] hover:shadow-md transition group">
                                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full shrink-0 {{ $ref['target_type'] === 'system' ? 'bg-green-50 text-green-700' : 'bg-[#002f80]/5 text-[#002f80]' }}">{{ strtoupper($ref['type']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-slate-900 group-hover:text-[#002f80] truncate block">{{ $ref['title'] }}</div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $ref['target_type'] === 'system' ? 'bg-green-50 text-green-700' : 'bg-[#002f80]/5 text-[#002f80]' }}">{{ ucfirst($ref['target_type']) }}</span>
                                        </div>
                                    </div>
                                    @include('components.icon', ['name' => 'chev-right', 'class' => 'w-5 h-5 text-slate-300 group-hover:text-[#002f80] shrink-0'])
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Incoming References --}}
                <div class="bg-slate-50/70 rounded-2xl p-5">
                    <h3 class="font-extrabold text-[#c90018] mb-4 flex items-center gap-2">
                        @include('components.icon', ['name' => 'arrow-left', 'class' => 'w-5 h-5'])
                        DIRUJUK OLEH (Incoming) <span class="text-xs font-bold bg-[#c90018] text-white px-2 py-0.5 rounded-full">{{ $impactMap['referenced_by']->count() }}</span>
                    </h3>
                    @if($impactMap['referenced_by']->isEmpty())
                        <p class="text-sm text-slate-400 text-center py-8">Belum ada dokumen yang mereferensikan dokumen ini.</                    @else
                        <div class="space-y-3">
                            @foreach($impactMap['referenced_by'] as $ref)
                                <a href="{{ route('governance.show', $ref['slug']) }}" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-[#d6d6d6] hover:border-[#c90018] hover:shadow-md transition group">
                                    <span class="text-[10px] font-bold bg-[#c90018]/10 text-[#c90018] px-2.5 py-1 rounded-full shrink-0">{{ strtoupper($ref['type']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('governance.show', $ref['slug']) }}" class="font-bold text-slate-900 group-hover:text-[#c90018] truncate block">{{ $ref['source_title'] }}</a>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#c90018]/10 text-[#c90018]">{{ str_replace('_', ' ', $ref['source_type']) }}</span>
                                        </div>
                                    </div>
                                    @include('components.icon', ['name' => 'chev-right', 'class' => 'w-5 h-5 text-slate-300 group-hover:text-[#c90018] shrink-0'])
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-6 bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-4">
            <h3 class="font-extrabold text-[#002f80] mb-3">Legenda</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                <div class="flex items-center gap-2 p-2 bg-green-50 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-green-500 shrink-0"></span>
                    <span class="text-green-700 font-semibold">System</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-[#002f80]/5 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-[#002f80] shrink-0"></span>
                    <span class="text-[#002f80] font-semibold">Policy / Regulation</span>
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