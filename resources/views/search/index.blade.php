@extends('layout')
@section('title', $q !== '' ? 'Hasil cari: '.$q : 'Pencarian')
@section('content')
<p class="page-eyebrow text-[var(--ypl-red)] mt-2">UNIVERSAL SEARCH</p>
<h1 class="page-title">Cari knowledge YPL</h1>

<form action="{{ route('search.index') }}" method="get" class="mt-5 flex gap-2 bg-white rounded-full p-1.5 shadow-lg border border-[var(--ypl-gray)] max-w-xl">
  <input name="q" value="{{ $q }}" maxlength="200" placeholder="Dokumen, layanan, FAQ, agenda, atau orang…" class="flex-1 bg-transparent text-slate-800 text-sm px-4 outline-none min-w-0">
  <button class="shrink-0 rounded-full font-bold text-sm px-6 py-2.5 text-white transition hover:brightness-110 grad-navy">Cari</button>
</form>

@if($q === '')
  <p class="text-sm text-slate-500 mt-6">Ketik kata kunci untuk mencari di dokumen, layanan, FAQ, agenda, dan direktori orang — sesuai otoritas Anda.</p>
@else
  @if($ai && $ai['found'])
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5 mt-5">
      <div class="text-xs font-bold tracking-widest text-[var(--ypl-navy)]">JAWABAN ASK YPL {{ ($ai['ai'] ?? false) ? '🧪' : '' }}</div>
      <div class="prose font-reading max-w-none mt-2 text-slate-700 whitespace-pre-line">{{ $ai['answer'] }}</div>
      <div class="flex flex-wrap gap-2 mt-3">
        @foreach($ai['sources'] as $hit)
          <a href="{{ route('documents.show', $hit['document']) }}" class="text-xs font-bold bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)] rounded-full px-3 py-1.5 hover:bg-[var(--ypl-navy)] hover:text-white transition">[{{ $loop->iteration }}] {{ \Illuminate\Support\Str::limit($hit['document']->title, 40) }}@if(!empty($hit['section'])) · § {{ \Illuminate\Support\Str::limit($hit['section'], 30) }}@endif</a>
        @endforeach
      </div>
    </div>
  @endif

  @if($services->isNotEmpty())
    <h2 class="section-label mt-6 mb-2">LAYANAN & AKSI ({{ $services->count() }})</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      @foreach($services as $s)
        <a href="{{ route('services.request', $s['key']) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-4 block">
          <div class="font-bold text-sm text-slate-900">{{ $s['label'] }}</div>
          <div class="text-xs text-slate-500 mt-0.5">{{ $s['sub'] }}</div>
          <div class="text-xs font-bold text-[var(--ypl-navy)] mt-2 text-right">Start →</div>
        </a>
      @endforeach
    </div>
  @endif

  @if($grouped->isNotEmpty())
    @foreach($grouped as $type => $docs)
      <h2 class="section-label mt-6 mb-2">{{ strtoupper(str_replace('_', ' ', $type)) }} ({{ $docs->count() }})</h2>
      <div class="grid sm:grid-cols-2 gap-3">
        @foreach($docs as $d)
          <a href="{{ route('documents.show', $d) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-4 block group">
            <div class="font-bold text-sm text-slate-900 leading-snug group-hover:text-[var(--ypl-navy)]">{{ $d->title }}</div>
            <div class="text-[11px] text-slate-400 mt-1">{{ $d->category->name ?? 'Tanpa kategori' }} · v{{ $d->version }} · {{ strtoupper(str_replace('_', ' ', $d->status)) }}</div>
            @if($d->description)<p class="text-[13px] text-slate-500 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($d->description), 120) }}</p>@endif
          </a>
        @endforeach
      </div>
    @endforeach
  @endif

  @if($contacts->isNotEmpty())
    <h2 class="section-label mt-6 mb-2">PIC / KONTAK ({{ $contacts->count() }})</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
      @foreach($contacts as $c)
        <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] p-4">
          <div class="font-bold text-sm text-slate-900">{{ $c->name }}</div>
          @if($c->position)<div class="text-[11px] text-slate-400 mt-0.5">{{ $c->position }}</div>@endif
          @if($c->unit)<div class="text-[11px] font-bold text-[var(--ypl-navy)] mt-1">{{ $c->unit->name }}</div>@endif
          <div class="mt-1.5 flex flex-col gap-0.5 text-[13px]">
            @if($c->email)<a href="mailto:{{ $c->email }}" class="text-slate-600 hover:text-[var(--ypl-navy)]">✉ {{ $c->email }}</a>@endif
            @if($c->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $c->phone) }}" class="text-slate-600 hover:text-[var(--ypl-navy)]">☎ {{ $c->phone }}</a>@endif
          </div>
        </div>
      @endforeach
    </div>
  @endif

  @if($faqs->isNotEmpty())
    <h2 class="section-label mt-6 mb-2">FAQ ({{ $faqs->count() }})</h2>
    <div class="grid sm:grid-cols-2 gap-3">
      @foreach($faqs as $f)
        <a href="{{ route('faqs.show', $f) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-4 block group">
          <div class="font-bold text-sm text-slate-900 leading-snug group-hover:text-[var(--ypl-navy)]">{{ $f->question }}</div>
          <div class="text-[13px] text-slate-500 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit($f->answer, 120) }}</div>
        </a>
      @endforeach
    </div>
  @endif

  @if($events->isNotEmpty())
    <h2 class="section-label mt-6 mb-2">AGENDA ({{ $events->count() }})</h2>
    <div class="grid sm:grid-cols-2 gap-3">
      @foreach($events as $e)
        <a href="{{ route('events.show', $e) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-4 block group">
          <div class="font-bold text-sm text-slate-900 leading-snug group-hover:text-[var(--ypl-navy)]">{{ $e->title }}</div>
          <div class="text-[11px] text-slate-400 mt-1">{{ strtoupper($e->category) }}{{ $e->starts_at ? ' · '.$e->starts_at->format('d M Y') : '' }}{{ $e->location ? ' · '.$e->location : '' }}</div>
        </a>
      @endforeach
    </div>
  @endif

  @if(auth()->check())
    @if($people->isNotEmpty())
      <h2 class="section-label mt-6 mb-2">ORANG ({{ $people->count() }})</h2>
      <div class="grid sm:grid-cols-2 gap-3">
        @foreach($people as $p)
          <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] p-4">
            <div class="font-bold text-sm text-slate-900">{{ $p->name }}</div>
            <div class="text-[11px] text-slate-400 mt-1">{{ ucfirst($p->role) }}{{ $p->academic_info ? ' · '.$p->academic_info : '' }}</div>
          </div>
        @endforeach
      </div>
    @endif
  @endif

  @if($grouped->isEmpty() && $services->isEmpty() && $contacts->isEmpty() && $faqs->isEmpty() && $events->isEmpty() && (!$ai || !$ai['found']))
    <div class="bg-white rounded-2xl border border-dashed border-[var(--ypl-gray)] p-8 text-center mt-5">
      <p class="font-bold text-slate-700">Tidak ada hasil untuk “{{ $q }}”</p>
      <p class="text-sm text-slate-500 mt-1">Coba kata kunci lain, atau <a href="{{ route('ask.index') }}" class="text-[var(--ypl-navy)] font-bold underline">tanya Ask YPL</a>.</p>
    </div>
  @endif
@endif
@endsection
