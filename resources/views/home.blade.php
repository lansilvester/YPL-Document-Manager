@extends('layout')
@section('title', 'Beranda')
@section('hero')
{{-- Hero Library Mode: Everything YPL. One digital home. --}}
<section class="w-full overflow-hidden text-white relative shadow-xl shadow-[#002f80]/25" style="background:linear-gradient(115deg,#001233 5%,#002f80 55%,#0a4fc4 100%)">
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
  <div class="relative max-w-6xl mx-auto px-4 py-10 sm:py-14">
   <div class="max-w-2xl">
    <div class="inline-flex items-center gap-2 bg-white/10 border border-white/25 rounded-full px-3 py-1 text-xs font-semibold tracking-wide">YPL SMART DIGITAL LIBRARY</div>
    <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight mt-3 leading-tight">Everything YPL.<br class="hidden sm:block"> One digital home.</h1>
    <p class="text-slate-200 mt-2 text-sm sm:text-base">Knowledge • Governance • Community</p>
    <form action="{{ route('search.index') }}" method="get" class="mt-5 flex gap-2 bg-white rounded-full p-1.5 shadow-lg max-w-xl">
      <input name="q" value="{{ $q }}" placeholder="Apa yang ingin Anda ketahui atau lakukan?" class="flex-1 bg-transparent text-slate-800 text-sm px-4 outline-none min-w-0">
      <button class="shrink-0 rounded-full font-bold text-sm px-6 py-2.5 text-white transition hover:brightness-110 inline-flex items-center gap-2" style="background:linear-gradient(135deg,#a30014,#c90018)">Search</button>
    </form>
    <p class="text-slate-200 mt-2 text-xs sm:text-sm">Jelajahi {{ number_format($stats['dokumen']) }} dokumen · {{ $stats['kategori'] }} kategori · {{ number_format($stats['views']) }} kali dibaca komunitas.</p>
    @guest
      @if(\App\Models\Setting::feature('reg'))<p class="mt-3 text-xs text-slate-300">Punya materi untuk dibagikan? <a href="{{ route('register') }}" class="underline font-bold text-white">Daftar dengan email YPL</a> — gratis.</p>@endif
    @endguest
   </div>
  </div>
</section>
@endsection
@section('content')

{{-- Saya ingin... (BAB 5: Handbook, Policy, SOP, Formulir, Calendar, Ask YPL) --}}
<div class="mt-6">
  <h2 class="section-label">SAYA INGIN…</h2>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-2">
    <a href="{{ route('library.index') }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block"><div class="font-bold text-sm text-slate-900">Handbook</div><div class="text-xs text-slate-500 mt-1">Explore →</div></a>
    <a href="{{ route('governance.index', ['tipe' => 'policy']) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block"><div class="font-bold text-sm text-slate-900">Policy</div><div class="text-xs text-slate-500 mt-1">Explore →</div></a>
    <a href="{{ route('governance.index', ['tipe' => 'sop']) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block"><div class="font-bold text-sm text-slate-900">SOP</div><div class="text-xs text-slate-500 mt-1">Explore →</div></a>
    <a href="{{ route('governance.index', ['tipe' => 'form']) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block"><div class="font-bold text-sm text-slate-900">Formulir</div><div class="text-xs text-slate-500 mt-1">Explore →</div></a>
    <a href="{{ route('calendar.index') }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block"><div class="font-bold text-sm text-slate-900">Calendar</div><div class="text-xs text-slate-500 mt-1">Explore →</div></a>
    <a href="{{ route('ask.index') }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block"><div class="font-bold text-sm text-slate-900">Ask YPL</div><div class="text-xs text-slate-500 mt-1">Explore →</div></a>
  </div>
</div>

{{-- Explore the Knowledge --}}
<div class="mt-8">
  <h2 class="text-xl font-extrabold tracking-tight text-[#002f80]">EXPLORE THE KNOWLEDGE</h2>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-3">
    <a href="{{ route('library.index') }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-5 block"><div class="font-extrabold text-slate-900">YPL Handbooks</div><div class="text-[13px] text-slate-500 mt-1">10 living digital books</div></a>
    <a href="{{ route('governance.index') }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-5 block"><div class="font-extrabold text-slate-900">Governance Library</div><div class="text-[13px] text-slate-500 mt-1">Policy • Regulation • SOP</div></a>
    <a href="{{ route('library.index', ['koleksi' => 'academic']) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-5 block"><div class="font-extrabold text-slate-900">Learning Library</div><div class="text-[13px] text-slate-500 mt-1">Academic resources</div></a>
    <a href="{{ route('library.index', ['koleksi' => 'boarding']) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-5 block"><div class="font-extrabold text-slate-900">Boarding Library</div><div class="text-[13px] text-slate-500 mt-1">Student life & residential knowledge</div></a>
  </div>
</div>

{{-- What's New + YPL Today --}}
<div class="grid lg:grid-cols-3 gap-4 mt-8">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-[#d6d6d6] p-5">
    <h3 class="font-extrabold tracking-tight text-[#002f80]">WHAT'S NEW</h3>
    <div class="mt-2 divide-y divide-slate-100">
      @forelse($latestDocs ?? [] as $n)
        <a href="{{ route('documents.show', $n) }}" class="flex items-center gap-3 py-2 group">
          <span class="text-xs font-bold bg-[#002f80]/5 text-[#002f80] px-2 py-1 rounded-full shrink-0">v{{ $n->version }}</span>
          <span class="min-w-0"><span class="block text-sm font-bold truncate group-hover:text-[#002f80]">{{ $n->title }}</span><span class="text-xs text-slate-400">{{ $n->created_at->format('d M Y') }} · {{ $n->category->name ?? 'Tanpa kategori' }}</span></span>
        </a>
      @empty
        <p class="text-sm text-slate-400 py-2">Belum ada pembaruan.</p>
      @endforelse
    </div>
  </div>
  <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#001c55,#002f80)">
    <h3 class="font-extrabold">YPL TODAY</h3>
    <div class="mt-2 space-y-2">
      @forelse($todayDocs ?? [] as $t)
        <a href="{{ route('documents.show', $t) }}" class="block bg-white/10 hover:bg-white/15 rounded-xl px-3 py-2 transition">
          <span class="block text-[11px] font-bold text-amber-300">REVIEW {{ $t->review_at->format('d M Y') }}</span>
          <span class="block text-sm font-bold truncate">{{ $t->title }}</span>
        </a>
      @empty
        <p class="text-xs text-slate-300">Tidak ada deadline review dekat. Lihat <a href="{{ route('calendar.index') }}" class="underline font-bold text-white">Calendar</a>.</p>
      @endforelse
    </div>
  </div>
</div>
@endsection
