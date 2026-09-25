<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Lokon Dokumen Digital') — YPL</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<style>
  :root { --ypl-navy: #002f80; --ypl-red: #c90018; --ypl-gray: #d6d6d6; }
  body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
  ::-webkit-scrollbar { width: 10px; height: 10px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #9aa5b5; border-radius: 8px; border: 2px solid #eef1f4; }
  .prose p { margin: .6em 0; } .prose h2 { font-size: 1.25em; font-weight: 700; margin: .8em 0 .4em; }
  .prose h3 { font-size: 1.1em; font-weight: 700; margin: .7em 0 .3em; }
  .prose ul { list-style: disc; margin-left: 1.4em; } .prose ol { list-style: decimal; margin-left: 1.4em; }
  .field { width: 100%; border-radius: .8rem; border: 1px solid #d6d6d6; padding: .6rem .9rem; font-size: .9rem; background: #fff; }
  .field:focus { outline: none; border-color: #002f80; box-shadow: 0 0 0 3px rgba(0,47,128,.15); }
  .btn-primary { background: linear-gradient(135deg, #002f80, #0046c0); color: #fff; border-radius: 9999px; padding: .6rem 1.4rem; font-weight: 700; font-size: .875rem; box-shadow: 0 8px 20px -8px rgba(0,47,128,.55); transition: .2s; display: inline-flex; align-items: center; gap: .45rem; justify-content: center; }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 12px 24px -8px rgba(0,47,128,.6); }
  .btn-accent { background: linear-gradient(135deg, #a30014, #c90018); color: #fff; border-radius: 9999px; padding: .6rem 1.4rem; font-weight: 700; font-size: .875rem; box-shadow: 0 8px 20px -8px rgba(201,0,24,.55); transition: .2s; display: inline-flex; align-items: center; gap: .45rem; justify-content: center; }
  .btn-accent:hover { transform: translateY(-1px); }
  .btn-ghost { border: 1px solid #d6d6d6; background: #fff; border-radius: 9999px; padding: .55rem 1.2rem; font-weight: 600; font-size: .875rem; transition: .2s; display: inline-flex; align-items: center; gap: .45rem; justify-content: center; }
  .btn-ghost:hover { border-color: #002f80; color: #002f80; }
  .card-hover { transition: transform .25s ease, box-shadow .25s ease; }
  .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -18px rgba(0,30,80,.35); }
</style>
</head>
<body class="bg-[#eef1f4] text-slate-800 min-h-screen flex flex-col antialiased">

<header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-[#d6d6d6] shadow-[0_2px_20px_-12px_rgba(0,30,80,.45)]">
  <div class="h-1" style="background:linear-gradient(90deg,#002f80 60%,#c90018 60%)"></div>
  <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center gap-x-4 gap-y-3">
    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
      <span class="w-10 h-10 rounded-2xl grid place-items-center text-white shadow-lg shadow-[#002f80]/30" style="background:linear-gradient(135deg,#001c55,#002f80)">
        @include('components.icon', ['name' => 'book', 'class' => 'w-5 h-5'])
      </span>
      <span class="leading-tight">
        <span class="block font-extrabold tracking-tight text-slate-900">Lokon Dokumen Digital</span>
        <span class="block text-[11px] font-bold tracking-wide text-[#c90018]">YAYASAN PENDIDIKAN LOKON</span>
      </span>
    </a>
    <form action="{{ route('home') }}" method="get" class="flex items-center gap-2 ml-auto order-last sm:order-none w-full sm:w-auto">
      <div class="relative flex-1 sm:flex-none">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">@include('components.icon', ['name' => 'search', 'class' => 'w-4 h-4'])</span>
        <input name="q" value="{{ $q ?? request('q') }}" placeholder="Cari judul, deskripsi…" class="rounded-full bg-slate-100 border border-transparent focus:bg-white focus:border-[#002f80] pl-9 pr-4 py-2 text-sm w-full sm:w-60 outline-none transition">
      </div>
      <button class="btn-primary !py-2 hidden sm:inline-flex">Cari</button>
    </form>
    <nav class="flex items-center gap-1 text-sm font-semibold">
      <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition">Beranda</a>
      <a href="{{ route('documents.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition">Jelajahi</a>
      @auth
        <a href="{{ route('documents.create') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition inline-flex items-center gap-1">@include('components.icon', ['name' => 'upload', 'class' => 'w-4 h-4']) Unggah</a>
        <a href="{{ route('editor.create') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition hidden md:inline-flex items-center gap-1">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Tulis</a>
        <span class="hidden lg:inline text-[#d6d6d6]">|</span>
        <a href="{{ route('documents.my') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition hidden lg:inline">Milik Saya</a>
        <a href="{{ route('favorites.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition hidden lg:inline">Favorit</a>
        @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-full bg-[#002f80] text-white hover:bg-[#001c55] transition">Admin</a>@endif
        @if(auth()->user()->isSuperAdmin())<a href="{{ route('sa.users') }}" class="px-3 py-1.5 rounded-full bg-[#c90018] text-white hover:bg-[#7a0010] transition">SA</a>@endif
        @if(in_array(auth()->user()->role, ['admin', 'staff'], true))<a href="{{ route('staff.documents') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition inline-flex items-center gap-1">@include('components.icon', ['name' => 'tool', 'class' => 'w-4 h-4']) Staff</a>@endif
        <a href="{{ route('profile.edit') }}" title="Profil" class="w-9 h-9 rounded-full grid place-items-center text-white text-sm font-bold shadow" style="background:linear-gradient(135deg,#002f80,#0046c0)">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</a>
        <form action="{{ route('logout') }}" method="post" class="inline">@csrf<button class="text-slate-400 hover:text-[#c90018] inline-flex items-center gap-1 text-xs font-semibold px-1" title="Keluar">@include('components.icon', ['name' => 'logout', 'class' => 'w-4 h-4'])</button></form>
      @else
        <a href="{{ route('login') }}" class="px-4 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition">Masuk</a>
        <a href="{{ route('register') }}" class="btn-primary !py-1.5">Daftar Gratis</a>
      @endauth
    </nav>
  </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-6 w-full flex-1">
  @if(session('ok'))
    <div class="mb-5 rounded-2xl bg-white border border-[#002f80]/20 px-5 py-3 text-sm font-medium flex items-center gap-2 shadow-sm">
      <span class="w-6 h-6 rounded-full text-white grid place-items-center shrink-0" style="background:#002f80">@include('components.icon', ['name' => 'check', 'class' => 'w-3.5 h-3.5'])</span><span>{{ session('ok') }}</span>
    </div>
  @endif
  @if($errors->any())
    <div class="mb-5 rounded-2xl bg-[#c90018]/5 border border-[#c90018]/25 text-[#7a0010] px-5 py-3 text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif
  @yield('content')
</main>

<footer class="mt-10 border-t border-[#d6d6d6] bg-white">
  <div class="h-1" style="background:linear-gradient(90deg,#002f80 60%,#c90018 60%)"></div>
  <div class="max-w-6xl mx-auto px-4 py-8 grid sm:grid-cols-3 gap-6 text-sm">
    <div>
      <div class="font-extrabold text-[#002f80] flex items-center gap-2">@include('components.icon', ['name' => 'book', 'class' => 'w-4 h-4']) Lokon Dokumen Digital</div>
      <p class="text-slate-500 mt-1">Platform dokumen digital Yayasan Pendidikan Lokon — baca, unggah, tulis, dan bagikan pengetahuan.</p>
    </div>
    <div>
      <div class="font-bold text-slate-900 mb-2">Jelajahi</div>
      <div class="flex flex-col gap-1 text-slate-500">
        <a class="hover:text-[#002f80]" href="{{ route('documents.index') }}">Semua dokumen</a>
        <a class="hover:text-[#002f80]" href="{{ route('documents.create') }}">Unggah PDF</a>
        <a class="hover:text-[#002f80]" href="{{ route('editor.create') }}">Tulis dokumen</a>
      </div>
    </div>
    <div>
      <div class="font-bold text-slate-900 mb-2">Yayasan</div>
      <div class="flex flex-col gap-1 text-slate-500">
        <span>Yayasan Pendidikan Lokon</span><span>{{ date('Y') }} · Tomohon, Sulawesi Utara</span>
      </div>
    </div>
  </div>
</footer>
@stack('scripts')
</body>
</html>
