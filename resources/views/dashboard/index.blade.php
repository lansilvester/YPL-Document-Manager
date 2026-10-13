@extends('layout')
@section('title', 'Dashboard')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dashboard Saya']]])

<div class="flex flex-wrap items-center gap-3">
  <div class="flex-1 min-w-0">
    <p class="page-eyebrow text-[var(--ypl-red)] mt-2">DASHBOARD · {{ strtoupper($role->name) }}</p>
    <h1 class="page-title">Selamat datang, {{ auth()->user()->name }}</h1>
    <p class="text-sm text-slate-500 mt-1">{{ $role->description ?: 'Konten diprioritaskan sesuai peran Anda.' }}</p>
  </div>
</div>

{{-- Saya ingin… (aksi cepat sesuai peran) --}}
<div class="mt-5">
  <h2 class="section-label">SAYA INGIN…</h2>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-2">
    @foreach($quick as $key)
      @php($meta = \App\Models\Role::QUICK_META[$key])
      <a href="{{ route($meta['route'], $meta['params'] ?? []) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-4 block"><div class="font-bold text-sm text-slate-900">{{ $meta['label'] }}</div><div class="text-xs text-slate-500 mt-1">Explore →</div></a>
    @endforeach
  </div>
</div>

{{-- Seksi role-aware (satu templat, urutan mengikuti peran) --}}
<div class="mt-8 grid lg:grid-cols-2 gap-4">
  @foreach($sections as $section)
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] p-5 {{ $section['key'] === 'overview' ? 'lg:col-span-2' : '' }}">
      <div class="flex items-center gap-2">
        <span class="w-8 h-8 rounded-xl grid place-items-center text-white grad-navy shrink-0">@include('components.icon', ['name' => $section['icon'], 'class' => 'w-4 h-4'])</span>
        <h2 class="font-extrabold tracking-tight text-[var(--ypl-navy)] flex-1">{{ $section['title'] }}</h2>
        @if($section['more'])
          <a href="{{ $section['more'] }}" class="text-xs font-bold text-slate-400 hover:text-[var(--ypl-navy)] shrink-0">Lihat semua →</a>
        @endif
      </div>

      {{-- Geser statistik (ringkasan operasional pimpinan) --}}
      @if(! empty($section['stats']))
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
          @foreach($section['stats'] as $stat)
            <div class="rounded-xl border border-[var(--ypl-gray)] p-3">
              <div class="flex items-center gap-2 text-slate-400">@include('components.icon', ['name' => $stat['icon'], 'class' => 'w-4 h-4'])<span class="text-[10px] font-bold tracking-widest">{{ strtoupper($stat['label']) }}</span></div>
              <div class="text-2xl font-extrabold text-[var(--ypl-navy)] tabular-nums mt-1">{{ number_format($stat['value']) }}</div>
            </div>
          @endforeach
        </div>
      @endif

      @if($section['items']->isNotEmpty())
        <div class="mt-3 divide-y divide-slate-100">
          @foreach($section['items'] as $item)
            <a href="{{ $item['url'] }}" class="flex items-center gap-3 py-2.5 group">
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold text-slate-900 truncate group-hover:text-[var(--ypl-navy)]">{{ $item['title'] }}</span>
                @if(! empty($item['meta']))<span class="block text-xs text-slate-400 truncate">{{ $item['meta'] }}</span>@endif
              </span>
              @if(! empty($item['badge']))<span class="text-[10px] font-bold bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)] px-2 py-0.5 rounded-full shrink-0">{{ strtoupper($item['badge']) }}</span>@endif
            </a>
          @endforeach
        </div>
      @elseif($section['empty'])
        <p class="text-sm text-slate-400 mt-3">{{ $section['empty'] }}</p>
      @endif
    </div>
  @endforeach
</div>

{{-- Rekomendasi personal (mesin rekomendasi) --}}
@if($recommendations->isNotEmpty())
<div class="mt-8">
  <h2 class="section-label">UNTUK ANDA</h2>
  <p class="text-sm text-slate-500 mt-1">Rekomendasi berdasarkan dokumen yang Anda baca dan tandai.</p>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-3">
    @foreach($recommendations as $hit)
      @include('components.doc-card', ['d' => $hit['document']])
    @endforeach
  </div>
</div>
@endif

{{-- Explore the Knowledge (kartu di luar scope peran disembunyikan) --}}
<div class="mt-8">
  <h2 class="text-xl font-extrabold tracking-tight text-[var(--ypl-navy)]">EXPLORE THE KNOWLEDGE</h2>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-3">
    @foreach($explore as $key)
      @php($meta = \App\Models\Role::EXPLORE_META[$key])
      <a href="{{ route($meta['route'], $meta['params'] ?? []) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-5 block"><div class="font-extrabold text-slate-900">{{ $meta['title'] }}</div><div class="text-[13px] text-slate-500 mt-1">{{ $meta['desc'] }}</div></a>
    @endforeach
  </div>
</div>
@endsection
