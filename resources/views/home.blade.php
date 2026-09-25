@extends('layout')
@section('title', 'Beranda')
@section('content')

{{-- Hero --}}
<section class="rounded-3xl overflow-hidden text-white relative shadow-xl shadow-[#002f80]/25" style="background:linear-gradient(115deg,#001233 5%,#002f80 55%,#0a4fc4 100%)">
  <div class="absolute inset-y-0 right-0 w-1/2 opacity-20 hidden md:block" aria-hidden="true">
    <svg class="h-full w-full" viewBox="0 0 400 300" fill="none" preserveAspectRatio="xMidYMid slice">
      <rect x="60" y="40" width="120" height="220" rx="10" fill="white" opacity=".25"/>
      <rect x="200" y="70" width="120" height="190" rx="10" fill="white" opacity=".18"/>
      <rect x="120" y="110" width="150" height="150" rx="10" fill="#c90018" opacity=".55"/>
      <path d="M140 150h110M140 170h80M140 190h95" stroke="white" stroke-width="8" stroke-linecap="round" opacity=".9"/>
      <circle cx="320" cy="70" r="26" fill="white" opacity=".25"/>
      <rect x="286" y="200" width="70" height="14" rx="7" fill="white" opacity=".35"/>
    </svg>
  </div>
  <div class="absolute top-0 right-0 w-40 h-40 rounded-full opacity-25" style="background:radial-gradient(circle,#c90018 0%,transparent 70%)" aria-hidden="true"></div>
  <div class="relative p-7 sm:p-10 max-w-2xl">
    <div class="inline-flex items-center gap-2 bg-white/10 border border-white/25 rounded-full px-3 py-1 text-xs font-semibold tracking-wide">@include('components.icon', ['name' => 'sparkles', 'class' => 'w-3.5 h-3.5']) PERPUSTAKAAN DIGITAL YPL</div>
    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mt-3 leading-tight">Semua pengetahuan Lokon,<br class="hidden sm:block"> dalam satu tempat.</h1>
    <p class="text-slate-200 mt-2 text-sm sm:text-base">Jelajahi {{ number_format($stats['dokumen']) }} dokumen · {{ $stats['kategori'] }} kategori · {{ number_format($stats['views']) }} kali dibaca komunitas.</p>
    <form action="{{ route('home') }}" method="get" class="mt-5 flex gap-2 bg-white rounded-full p-1.5 shadow-lg max-w-xl">
      <input name="q" value="{{ $q }}" placeholder="Coba: “matematika”, “panduan”, “praktikum”…" class="flex-1 bg-transparent text-slate-800 text-sm px-4 outline-none min-w-0">
      <button class="shrink-0 rounded-full font-bold text-sm px-6 py-2.5 text-white transition hover:brightness-110 inline-flex items-center gap-2" style="background:linear-gradient(135deg,#002f80,#0046c0)">@include('components.icon', ['name' => 'search', 'class' => 'w-4 h-4']) Cari Dokumen</button>
    </form>
    @guest
      <p class="mt-3 text-xs text-slate-300">Punya materi untuk dibagikan? <a href="{{ route('register') }}" class="underline font-bold text-white">Daftar dengan email YPL</a> — gratis.</p>
    @endguest
  </div>
</section>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
  <div class="lg:col-span-2">
    <div class="flex items-center gap-2 mb-1">
      <h2 class="text-xl font-extrabold tracking-tight text-[#002f80]">Galeri Dokumen Publik</h2>
      @if($q)<span class="text-xs bg-[#002f80]/10 text-[#002f80] font-bold px-2.5 py-1 rounded-full">“{{ $q }}” · {{ $docs->total() }} hasil</span>@endif
    </div>
    <p class="text-sm text-slate-500 mb-4">Koleksi terbaru dari komunitas YPL.</p>
    <div class="flex gap-2 mb-5 text-[13px] font-semibold overflow-x-auto pb-1">
      <a href="{{ route('home', ['q' => $q]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ !$activeCat ? 'bg-[#002f80] text-white border-[#002f80] shadow' : 'bg-white border-[#d6d6d6] hover:border-[#002f80]' }}">Semua</a>
      @foreach($categories as $c)
        <a href="{{ route('home', ['kategori' => $c->slug, 'q' => $q]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ $activeCat === $c->slug ? 'bg-[#002f80] text-white border-[#002f80] shadow' : 'bg-white border-[#d6d6d6] hover:border-[#002f80]' }}">{{ $c->name }}</a>
      @endforeach
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      @forelse($docs as $d)
        @include('components.doc-card', ['d' => $d])
      @empty
        <div class="sm:col-span-2 bg-white rounded-2xl border border-dashed border-[#d6d6d6] p-10 text-center text-slate-400">
          <span class="inline-block">@include('components.icon', ['name' => 'inbox', 'class' => 'w-12 h-12'])</span>
          <p class="font-bold mt-2 text-slate-700">Belum ada dokumen di sini</p>
          <p class="text-sm text-slate-500">Coba kata kunci lain atau @auth<a class="text-[#002f80] underline font-semibold" href="{{ route('documents.create') }}">unggah yang pertama</a>@else<a class="text-[#002f80] underline font-semibold" href="{{ route('register') }}">daftar dan unggah</a>@endauth.</p>
        </div>
      @endforelse
    </div>
    <div class="mt-5">{{ $docs->links() }}</div>
  </div>

  <aside class="space-y-4">
    <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
      <h3 class="font-extrabold tracking-tight flex items-center gap-2 text-[#002f80]"><span class="text-[#c90018]">@include('components.icon', ['name' => 'flame', 'class' => 'w-5 h-5'])</span> Dokumen Paling Dibaca</h3>
      <ol class="mt-3 space-y-1">
        @forelse($popular as $i => $p)
          <li>
            <a href="{{ route('documents.show', $p) }}" class="flex items-center gap-3 p-2 rounded-xl hover:bg-[#002f80]/5 transition group">
              <span class="text-2xl font-extrabold w-7 text-center {{ $i === 0 ? 'text-[#c90018]' : 'text-slate-300' }}">{{ $i + 1 }}</span>
              <span class="min-w-0"><span class="block text-sm font-bold text-slate-800 truncate group-hover:text-[#002f80]">{{ $p->title }}</span>
              <span class="text-xs text-slate-400">{{ number_format($p->views_count) }} dibaca</span></span>
            </a>
          </li>
        @empty
          <li class="text-sm text-slate-400">Belum ada data.</li>
        @endforelse
      </ol>
    </div>
    @auth
    <div class="rounded-2xl p-5 text-white shadow-lg shadow-[#002f80]/25" style="background:linear-gradient(135deg,#001c55,#002f80)">
      <div class="font-extrabold flex items-center gap-2">Bagikan pengetahuanmu @include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4'])</div>
      <p class="text-xs text-slate-300 mt-1 mb-3">Unggah PDF atau tulis langsung di platform.</p>
      <div class="flex gap-2 text-xs font-bold">
        <a href="{{ route('documents.create') }}" class="bg-white text-[#002f80] rounded-full px-4 py-2 inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'upload', 'class' => 'w-3.5 h-3.5']) Unggah</a>
        <a href="{{ route('editor.create') }}" class="bg-white/15 border border-white/30 rounded-full px-4 py-2 inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'pen', 'class' => 'w-3.5 h-3.5']) Tulis</a>
      </div>
    </div>
    @else
    <div class="rounded-2xl p-5 text-white shadow-lg" style="background:linear-gradient(135deg,#001c55,#002f80)">
      <div class="font-extrabold flex items-center gap-2">Warga YPL? Bergabunglah @include('components.icon', ['name' => 'cap', 'class' => 'w-4 h-4'])</div>
      <p class="text-xs text-slate-300 mt-1 mb-3">Simpan favorit, lanjutkan bacaan, dan unggah dokumen.</p>
      <a href="{{ route('register') }}" class="btn-accent !py-2 text-xs">Daftar Gratis @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-3.5 h-3.5'])</a>
    </div>
    @endauth
  </aside>
</div>
@endsection
