@extends('layout')
@section('title', $q !== '' ? 'Hasil cari: '.$q : 'Pencarian')
@section('content')
<p class="page-eyebrow text-[#c90018] mt-2">UNIVERSAL SEARCH</p>
<h1 class="page-title">Cari knowledge YPL</h1>

<form action="{{ route('search.index') }}" method="get" class="mt-5 flex gap-2 bg-white rounded-full p-1.5 shadow-lg border border-[#d6d6d6] max-w-xl">
  <input name="q" value="{{ $q }}" maxlength="200" placeholder="Dokumen, layanan, atau orang…" class="flex-1 bg-transparent text-slate-800 text-sm px-4 outline-none min-w-0">
  <button class="shrink-0 rounded-full font-bold text-sm px-6 py-2.5 text-white transition hover:brightness-110" style="background:linear-gradient(135deg,#002f80,#0046c0)">Cari</button>
</form>

@if($q === '')
  <p class="text-sm text-slate-500 mt-6">Ketik kata kunci untuk mencari di dokumen, layanan, dan direktori orang — sesuai otoritas Anda.</p>
@else
  @if($ai && $ai['found'])
    <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 mt-5">
      <div class="text-xs font-bold tracking-widest text-[#002f80]">JAWABAN ASK YPL {{ ($ai['ai'] ?? false) ? '🧪' : '' }}</div>
      <div class="prose max-w-none mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $ai['answer'] }}</div>
      <div class="flex flex-wrap gap-2 mt-3">
        @foreach($ai['sources'] as $hit)
          <a href="{{ route('documents.show', $hit['document']) }}" class="text-xs font-bold bg-[#002f80]/5 text-[#002f80] rounded-full px-3 py-1.5 hover:bg-[#002f80] hover:text-white transition">[{{ $loop->iteration }}] {{ \Illuminate\Support\Str::limit($hit['document']->title, 40) }}</a>
        @endforeach
      </div>
    </div>
  @endif

  @if($services->isNotEmpty())
    <h2 class="section-label mt-6 mb-2">LAYANAN & AKSI ({{ $services->count() }})</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      @foreach($services as $s)
        <a href="{{ route('services.request', $s['key']) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block">
          <div class="font-bold text-sm text-slate-900">{{ $s['label'] }}</div>
          <div class="text-xs text-slate-500 mt-0.5">{{ $s['sub'] }}</div>
          <div class="text-xs font-bold text-[#002f80] mt-2 text-right">Start →</div>
        </a>
      @endforeach
    </div>
  @endif

  @if($grouped->isNotEmpty())
    @foreach($grouped as $type => $docs)
      <h2 class="section-label mt-6 mb-2">{{ strtoupper(str_replace('_', ' ', $type)) }} ({{ $docs->count() }})</h2>
      <div class="grid sm:grid-cols-2 gap-3">
        @foreach($docs as $d)
          <a href="{{ route('documents.show', $d) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block group">
            <div class="font-bold text-sm text-slate-900 leading-snug group-hover:text-[#002f80]">{{ $d->title }}</div>
            <div class="text-[11px] text-slate-400 mt-1">{{ $d->category->name ?? 'Tanpa kategori' }} · v{{ $d->version }} · {{ strtoupper(str_replace('_', ' ', $d->status)) }}</div>
            @if($d->description)<p class="text-[13px] text-slate-500 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($d->description), 120) }}</p>@endif
          </a>
        @endforeach
      </div>
    @endforeach
  @endif

  @if(auth()->check())
    @if($people->isNotEmpty())
      <h2 class="section-label mt-6 mb-2">ORANG ({{ $people->count() }})</h2>
      <div class="grid sm:grid-cols-2 gap-3">
        @foreach($people as $p)
          <div class="bg-white rounded-2xl border border-[#d6d6d6] p-4">
            <div class="font-bold text-sm text-slate-900">{{ $p->name }}</div>
            <div class="text-[11px] text-slate-400 mt-1">{{ ucfirst($p->role) }}{{ $p->academic_info ? ' · '.$p->academic_info : '' }}</div>
          </div>
        @endforeach
      </div>
    @endif
  @endif

  @if($grouped->isEmpty() && $services->isEmpty() && (!$ai || !$ai['found']))
    <div class="bg-white rounded-2xl border border-dashed border-[#d6d6d6] p-8 text-center mt-5">
      <p class="font-bold text-slate-700">Tidak ada hasil untuk “{{ $q }}”</p>
      <p class="text-sm text-slate-500 mt-1">Coba kata kunci lain, atau <a href="{{ route('ask.index') }}" class="text-[#002f80] font-bold underline">tanya Ask YPL</a>.</p>
    </div>
  @endif
@endif
@endsection
