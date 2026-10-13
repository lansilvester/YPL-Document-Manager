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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
@layer components {
  /* ============================================================
     Design tokens BAB 16 — sumber tunggal warna, radius, bayangan,
     ruang dan gerak. Komponen di bawah memakai var(--…) agar
     perubahan tema cukup di satu tempat.
     ============================================================ */
  @include('components.tokens')
  body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
  .font-reading { font-family: Georgia, 'Times New Roman', serif; }
  html, body { overflow-x: clip; }
  @supports not (overflow: clip) { html, body { overflow-x: hidden; } }
  ::-webkit-scrollbar { width: 10px; height: 10px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #9aa5b5; border-radius: 8px; border: 2px solid var(--ypl-bg); }
  .prose p { margin: .6em 0; } .prose h2 { font-size: 1.25em; font-weight: 700; margin: .8em 0 .4em; }
  .prose h3 { font-size: 1.1em; font-weight: 700; margin: .7em 0 .3em; }
  .prose ul { list-style: disc; margin-left: 1.4em; } .prose ol { list-style: decimal; margin-left: 1.4em; }
  .field { width: 100%; border-radius: var(--ypl-radius-md); border: 1px solid var(--ypl-gray); padding: .6rem .9rem; font-size: .9rem; background: #fff; }
  .field:focus { outline: none; border-color: var(--ypl-navy); box-shadow: var(--ypl-ring); }
  /* Skala tipografi kanonis BAB 15: judul halaman, eyebrow, label seksi */
  .page-title { font-size: 1.5rem; line-height: 2rem; font-weight: 800; letter-spacing: -0.025em; color: var(--ypl-navy); }
  @media (min-width: 640px) { .page-title { font-size: 1.875rem; line-height: 2.25rem; } }
  .page-eyebrow { font-size: .75rem; font-weight: 700; letter-spacing: .1em; }
  .page-sub { font-size: .875rem; color: var(--ypl-muted); margin-top: .25rem; }
  .section-label { font-size: .875rem; font-weight: 800; letter-spacing: .1em; color: var(--ypl-navy); }
  .btn-primary { background: var(--ypl-grad-navy); color: #fff; border-radius: var(--ypl-radius-pill); padding: .6rem 1.4rem; font-weight: 700; font-size: .875rem; box-shadow: var(--ypl-shadow-navy); transition: var(--ypl-transition); display: inline-flex; align-items: center; gap: .45rem; justify-content: center; }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: var(--ypl-shadow-navy-lg); }
  .btn-accent { background: var(--ypl-grad-red); color: #fff; border-radius: var(--ypl-radius-pill); padding: .6rem 1.4rem; font-weight: 700; font-size: .875rem; box-shadow: var(--ypl-shadow-red); transition: var(--ypl-transition); display: inline-flex; align-items: center; gap: .45rem; justify-content: center; }
  .btn-accent:hover { transform: translateY(-1px); }
  .btn-ghost { border: 1px solid var(--ypl-gray); background: #fff; border-radius: var(--ypl-radius-pill); padding: .55rem 1.2rem; font-weight: 600; font-size: .875rem; transition: var(--ypl-transition); display: inline-flex; align-items: center; gap: .45rem; justify-content: center; }
  .btn-ghost:hover { border-color: var(--ypl-navy); color: var(--ypl-navy); }
  /* Gradien khas BAB 15 — satu sumber, tanpa inline berulang */
  .grad-navy { background: var(--ypl-grad-navy); }
  .grad-red { background: var(--ypl-grad-red); }
  .brand-line { background: linear-gradient(90deg, var(--ypl-navy) 60%, var(--ypl-red) 60%); }
  .card-hover { transition: transform .25s ease, box-shadow .25s ease; }
  .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -18px rgba(0,30,80,.35); }
}
</style>
</head>
<body class="bg-[var(--ypl-bg)] text-slate-800 min-h-screen flex flex-col antialiased">

<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-[var(--ypl-gray)] shadow-[0_2px_20px_-12px_rgba(0,30,80,.45)]">
  <div class="h-1 brand-line"></div>
  {{-- Satu baris: logo + nama situs + menu + akun sejajar, container lebar --}}
  <div class="max-w-[1400px] mx-auto px-4 py-2.5 flex items-center gap-3">
    <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 min-w-0">
      <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo {{ \App\Models\Setting::get('foundation_name') }}" class="h-10 w-auto rounded-lg bg-white object-contain">
      <span class="leading-tight hidden min-[420px]:block">
        <span class="block font-extrabold tracking-tight text-slate-900 text-[15px] whitespace-nowrap">{{ \App\Models\Setting::get('site_name') }}</span>
        <span class="block text-[11px] font-bold tracking-wide text-[var(--ypl-red)] whitespace-nowrap">{{ strtoupper(\App\Models\Setting::get('foundation_name')) }}</span>
      </span>
    </a>
    <nav class="hidden lg:flex flex-1 items-center justify-center gap-0.5 text-sm font-semibold overflow-x-auto whitespace-nowrap min-w-0" aria-label="Navigasi utama" style="scrollbar-width:none">
      <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-full hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)] transition inline-flex items-center gap-1.5 shrink-0 {{ request()->routeIs('home') ? 'bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)]' : '' }}">@include('components.icon', ['name' => 'home', 'class' => 'w-4 h-4']) Home</a>
      <a href="{{ route('library.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)] transition inline-flex items-center gap-1.5 shrink-0 {{ request()->routeIs('library.*') ? 'bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)]' : '' }}">@include('components.icon', ['name' => 'compass', 'class' => 'w-4 h-4']) Library</a>
      <a href="{{ route('governance.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)] transition shrink-0 {{ request()->routeIs('governance.*') ? 'bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)]' : '' }}">Governance</a>
      <a href="{{ route('services.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)] transition shrink-0 {{ request()->routeIs('services.*') ? 'bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)]' : '' }}">Services</a>
      <a href="{{ route('community.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)] transition shrink-0 {{ request()->routeIs('community.*') ? 'bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)]' : '' }}">Community</a>
      <a href="{{ route('calendar.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)] transition shrink-0 {{ request()->routeIs('calendar.*') ? 'bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)]' : '' }}">Calendar</a>
    </nav>
    <div class="flex items-center gap-2 ml-auto shrink-0">
      @auth
        @php($unreadNotifications = auth()->user()->unreadNotifications->count())
        <a href="{{ route('notifications.index') }}" class="relative w-10 h-10 rounded-xl grid place-items-center border border-[var(--ypl-gray)] text-[var(--ypl-navy)] hover:border-[var(--ypl-navy)] transition shrink-0" aria-label="Notifikasi{{ $unreadNotifications ? " ({$unreadNotifications} belum dibaca)" : '' }}">
          @include('components.icon', ['name' => 'bell', 'class' => 'w-5 h-5'])
          @if($unreadNotifications)
            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[var(--ypl-red)] text-white text-[10px] font-extrabold grid place-items-center">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
          @endif
        </a>
        <div class="relative" id="profile-menu">
          <button type="button" id="profile-menu-btn" aria-haspopup="true" aria-expanded="false" class="flex items-center gap-2 rounded-full pl-1 pr-2 py-1 border border-[var(--ypl-gray)] hover:border-[var(--ypl-navy)] hover:shadow transition bg-white">
            <span class="w-8 h-8 rounded-full grid place-items-center text-white text-sm font-bold overflow-hidden shrink-0 grad-navy">
              @if(auth()->user()->avatar_path)<img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" class="w-8 h-8 object-cover" alt="">@else{{ strtoupper(substr(auth()->user()->name,0,1)) }}@endif
            </span>
            <span class="hidden xl:block text-sm font-bold text-slate-700 max-w-28 truncate">{{ auth()->user()->name }}</span>
            @include('components.icon', ['name' => 'chev-down', 'class' => 'w-4 h-4 text-slate-400'])
          </button>
          <div id="profile-menu-panel" class="hidden absolute right-0 mt-2 w-60 rounded-2xl border border-[var(--ypl-gray)] bg-white shadow-xl shadow-[var(--ypl-navy)]/10 overflow-hidden z-50">
            <div class="px-4 py-3 border-b border-slate-100" style="background:linear-gradient(135deg,var(--ypl-navy-900),var(--ypl-navy))">
              <div class="font-extrabold text-white text-sm truncate">{{ auth()->user()->name }}</div>
              <div class="text-[11px] text-slate-300 truncate">{{ auth()->user()->email }} · {{ strtoupper(auth()->user()->role) }}</div>
            </div>
            <div class="p-1.5 text-sm font-semibold">
              <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'home', 'class' => 'w-4 h-4']) Dashboard Saya</a>
              <a href="{{ route('notifications.index') }}" class="flex items-center justify-between gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]"><span class="flex items-center gap-2.5">@include('components.icon', ['name' => 'bell', 'class' => 'w-4 h-4']) Notifikasi</span>@if($unreadNotifications)<span class="min-w-[18px] h-[18px] px-1 rounded-full bg-[var(--ypl-red)] text-white text-[10px] font-extrabold grid place-items-center">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>@endif</a>
              <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'users', 'class' => 'w-4 h-4']) Profil Saya</a>
              @if(\App\Models\Setting::feature('upload'))<a href="{{ route('documents.create') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'upload', 'class' => 'w-4 h-4']) Unggah PDF</a>@endif
              @if(\App\Models\Setting::feature('editor'))<a href="{{ route('editor.create') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Tulis Dokumen</a>@endif
              <a href="{{ route('documents.my') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'folder', 'class' => 'w-4 h-4']) Dokumen Saya</a>
              <a href="{{ route('services.my') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'inbox', 'class' => 'w-4 h-4']) Permintaan Saya</a>
              @if(\App\Models\Setting::feature('bookmark'))<a href="{{ route('bookmarks.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'star', 'class' => 'w-4 h-4']) Bookmark</a>@endif
              @if(auth()->user()->isStaff())
                <div class="mt-1 pt-1 border-t border-slate-100">
                  <div class="px-3 pt-1 text-[11px] font-bold tracking-widest text-slate-400">KELOLA</div>
                  <a href="{{ route('staff.documents') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'tool', 'class' => 'w-4 h-4']) Ruang Staff</a>
                  <a href="{{ route('staff.analytics') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'chart', 'class' => 'w-4 h-4']) Analytics</a>
                  @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'shield', 'class' => 'w-4 h-4']) Dasbor Admin</a>
                  @endif
                  @if(auth()->user()->isSuperAdmin())
                    <a href="{{ route('sa.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[var(--ypl-red)]/5 text-[var(--ypl-red)]">@include('components.icon', ['name' => 'shield', 'class' => 'w-4 h-4']) Pusat Kendali SA</a>
                  @endif
                </div>
              @endif
            </div>
            <div class="border-t border-slate-100 p-1.5">
              <form action="{{ route('logout') }}" method="post" data-confirm="Yakin ingin keluar dari aplikasi?">@csrf<button class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-bold text-[var(--ypl-red)] hover:bg-[var(--ypl-red)]/5">@include('components.icon', ['name' => 'logout', 'class' => 'w-4 h-4']) Keluar</button></form>
            </div>
          </div>
        </div>
      @else
        @unless(request()->routeIs('register', 'login'))
        <a href="{{ route('login') }}" class="hidden lg:inline-block px-4 py-1.5 rounded-full text-sm font-semibold hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)] transition">Masuk</a>
        @if(\App\Models\Setting::feature('reg'))<a href="{{ route('register') }}" class="btn-primary !py-1.5 hidden lg:inline-flex">Daftar Gratis</a>@endif
        @endunless
      @endauth
    </div>
    {{-- Toggle menu mobile di kanan --}}
    <button type="button" id="nav-toggle" aria-label="Buka menu" aria-expanded="false" aria-controls="mobile-nav" class="lg:hidden w-10 h-10 rounded-xl grid place-items-center border border-[var(--ypl-gray)] text-[var(--ypl-navy)] shrink-0">
      <span id="nav-icon-open">@include('components.icon', ['name' => 'menu', 'class' => 'w-5 h-5'])</span>
      <span id="nav-icon-close" class="hidden">@include('components.icon', ['name' => 'x', 'class' => 'w-5 h-5'])</span>
    </button>
  </div>
  {{-- Panel menu mobile (hamburger) --}}
  <nav id="mobile-nav" class="hidden lg:hidden border-t border-slate-100 bg-white" aria-label="Menu mobile">
    <div class="px-4 py-2 grid gap-1 text-sm font-semibold">
      @guest
        @unless(request()->routeIs('register', 'login'))
        <a href="{{ route('login') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'logout', 'class' => 'w-4 h-4 scale-x-[-1]']) Masuk</a>
        @if(\App\Models\Setting::feature('reg'))<a href="{{ route('register') }}" class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-white grad-navy">@include('components.icon', ['name' => 'plus', 'class' => 'w-4 h-4']) Daftar Gratis</a>@endif
        <div class="border-t border-slate-100 my-1"></div>
        @endunless
      @endguest
      <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'home', 'class' => 'w-4 h-4']) Home</a>
      <a href="{{ route('library.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'compass', 'class' => 'w-4 h-4']) Library</a>
      <a href="{{ route('library.graph') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'link', 'class' => 'w-4 h-4']) Peta Pengetahuan</a>
      <a href="{{ route('governance.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">Governance</a>
      <a href="{{ route('services.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">Services</a>
      <a href="{{ route('community.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">Community</a>
      <a href="{{ route('calendar.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">Calendar</a>
      <a href="{{ route('ask.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">Ask YPL</a>
      <a href="{{ route('insight.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">Insight</a>
      @auth
        <div class="border-t border-slate-100 my-1"></div>
        <div class="px-3 pt-1 text-[11px] font-bold tracking-widest text-slate-400">AKUN & KERJA</div>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'home', 'class' => 'w-4 h-4']) Dashboard Saya</a>
        <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'bell', 'class' => 'w-4 h-4']) Notifikasi</a>
        @if(\App\Models\Setting::feature('editor'))<a href="{{ route('editor.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Tulis Dokumen</a>@endif
        <a href="{{ route('documents.my') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'folder', 'class' => 'w-4 h-4']) Dokumen Saya</a>
        <a href="{{ route('services.my') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'inbox', 'class' => 'w-4 h-4']) Permintaan Saya</a>
        @if(\App\Models\Setting::feature('bookmark'))<a href="{{ route('bookmarks.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'star', 'class' => 'w-4 h-4']) Bookmark Saya</a>@endif
        @if(auth()->user()->isStaff())<a href="{{ route('staff.documents') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'tool', 'class' => 'w-4 h-4']) Ruang Staff</a>@endif
        @if(auth()->user()->isStaff())<a href="{{ route('staff.analytics') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[var(--ypl-navy)]/5 hover:text-[var(--ypl-navy)]">@include('components.icon', ['name' => 'chart', 'class' => 'w-4 h-4']) Analytics</a>@endif
        @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[var(--ypl-navy)] text-white">@include('components.icon', ['name' => 'shield', 'class' => 'w-4 h-4']) Dasbor Admin</a>@endif
        @if(auth()->user()->isSuperAdmin())<a href="{{ route('sa.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[var(--ypl-red)] text-white">@include('components.icon', ['name' => 'shield', 'class' => 'w-4 h-4']) Pusat Kendali SA</a>@endif
      @endauth
    </div>
  </nav>
</header>

@yield('hero')

<main class="@yield('main_width', 'max-w-6xl') mx-auto px-4 py-6 w-full flex-1">
  @yield('content')
</main>

<footer class="mt-10 border-t border-[var(--ypl-gray)] bg-white">
  <div class="h-1 brand-line"></div>
  <div class="max-w-6xl mx-auto px-4 py-8 grid sm:grid-cols-3 gap-6 text-sm">
    <div>
      <div class="flex items-center gap-2">
        <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo YPL" class="h-10 w-auto rounded-md bg-white object-contain">
        <span class="font-extrabold text-[var(--ypl-navy)]">{{ \App\Models\Setting::get('site_name') }}</span>
      </div>
      <p class="text-slate-500 mt-1">YPL Smart Digital Library — knowledge, governance, community dalam satu rumah digital.</p>
    </div>
    <div>
      <div class="font-bold text-slate-900 mb-2">Library</div>
      <div class="flex flex-col gap-1 text-slate-500">
        <a class="hover:text-[var(--ypl-navy)]" href="{{ route('library.index') }}">Digital Bookshelf</a>
        <a class="hover:text-[var(--ypl-navy)]" href="{{ route('governance.index') }}">Governance</a>
        <a class="hover:text-[var(--ypl-navy)]" href="{{ route('library.graph') }}">Peta Pengetahuan</a>
        <a class="hover:text-[var(--ypl-navy)]" href="{{ route('services.index') }}">Services</a>
        <a class="hover:text-[var(--ypl-navy)]" href="{{ route('insight.index') }}">Insight</a>
      </div>
    </div>
    <div>
      <div class="font-bold text-slate-900 mb-2">Yayasan</div>
      <div class="flex flex-col gap-1 text-slate-500">
        <span>{{ \App\Models\Setting::get('foundation_name') }}</span><span>{{ date('Y') }} · Tomohon, Sulawesi Utara</span>
      </div>
    </div>
  </div>
</footer>
<script>
(function () {
  // Toggle menu mobile (hamburger)
  var navToggle = document.getElementById('nav-toggle');
  var navPanel = document.getElementById('mobile-nav');
  var navIconOpen = document.getElementById('nav-icon-open');
  var navIconClose = document.getElementById('nav-icon-close');
  function setNav(open) {
    if (!navToggle || !navPanel) return;
    navPanel.classList.toggle('hidden', !open);
    navIconOpen.classList.toggle('hidden', open);
    navIconClose.classList.toggle('hidden', !open);
    navToggle.setAttribute('aria-expanded', String(open));
    navToggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
  }
  if (navToggle && navPanel) {
    navToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      setNav(navPanel.classList.contains('hidden'));
    });
    navPanel.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { setNav(false); });
    });
    document.addEventListener('click', function (e) {
      if (!navPanel.classList.contains('hidden') && !navPanel.contains(e.target)) setNav(false);
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setNav(false); });
    // Jika layar membesar saat panel terbuka, tutup otomatis
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 1024) setNav(false);
    });
  }

  var btn = document.getElementById('profile-menu-btn');
  var panel = document.getElementById('profile-menu-panel');
  if (!btn || !panel) return;
  function close() { panel.classList.add('hidden'); btn.setAttribute('aria-expanded', 'false'); }
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    var open = panel.classList.toggle('hidden');
    btn.setAttribute('aria-expanded', String(!open));
  });
  document.addEventListener('click', function (e) {
    if (!document.getElementById('profile-menu').contains(e.target)) close();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
{{-- Notifikasi global ala SweetAlert untuk setiap simpan/ubah --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  var navy = '#002f80', red = '#c90018';

  // Toast sukses: ringkas, jeda saat disentuh, bisa ditutup manual
  var Toast = Swal.mixin({
    toast: true, position: 'top-end', showConfirmButton: false, showCloseButton: true,
    timer: 4000, timerProgressBar: true,
    customClass: { popup: 'rounded-2xl', title: '!text-sm', closeButton: '!text-xs' },
    didOpen: function (t) {
      t.addEventListener('mouseenter', Swal.stopTimer);
      t.addEventListener('mouseleave', Swal.resumeTimer);
    },
  });

  @if(session('ok'))
    Toast.fire({ icon: 'success', title: 'Berhasil!', text: @json(session('ok')) });
  @endif

  @if($errors->any())
    (function () {
      var items = @json($errors->all()).map(function (m, i) {
        return '<li style="display:flex;gap:.5rem;align-items:flex-start;padding:.35rem 0;border-bottom:1px solid #f1f5f9">'
          + '<span style="min-width:1.35rem;height:1.35rem;border-radius:9999px;background:#fbe0e4;color:var(--ypl-red-600);font-size:.7rem;font-weight:800;display:inline-flex;align-items:center;justify-content:center">' + (i + 1) + '</span>'
          + '<span>' + m.replace(/</g, '&lt;') + '</span></li>';
      }).join('');
      Swal.fire({
        icon: 'error',
        title: 'Ada ' + @json(count($errors->all())) + ' hal yang perlu diperbaiki',
        html: '<ul style="text-align:left;list-style:none;margin:0;padding:0">' + items + '</ul>',
        confirmButtonText: 'Mengerti, saya perbaiki',
        confirmButtonColor: navy,
        showCloseButton: true,
        customClass: { popup: 'rounded-3xl', title: '!text-lg' },
      });
    })();
  @endif

  // Konfirmasi cantik untuk form destruktif (data-confirm="pesan…?")
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning', title: 'Yakin melanjutkan?',
        text: form.getAttribute('data-confirm'),
        showCancelButton: true, confirmButtonText: 'Ya, lanjutkan', cancelButtonText: 'Batalkan',
        confirmButtonColor: red, cancelButtonColor: navy,
        reverseButtons: true, focusCancel: true, showCloseButton: true,
        customClass: { popup: 'rounded-3xl', title: '!text-lg' },
      }).then(function (r) {
        if (r.isConfirmed) {
          Swal.fire({
            title: 'Memproses…', allowOutsideClick: false, showConfirmButton: false,
            customClass: { popup: 'rounded-3xl' },
            didOpen: function () { Swal.showLoading(); },
          });
          form.submit();
        }
      });
    });
  });

  // Upload interaktif: progress bar + tombol batal untuk semua form multipart berisi file
  document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var fileInput = form.querySelector('input[type="file"]');
      var file = fileInput && fileInput.files[0];
      if (!file) return; // tanpa file: submit normal
      e.preventDefault();

      var bar = document.createElement('div');
      bar.id = 'upload-progress-handler';
      bar.innerHTML = '<div style="display:flex;align-items:center;gap:.6rem;background:#fff;border:1px solid var(--ypl-gray);border-radius:1rem;padding:.7rem 1rem;margin-bottom:.8rem;box-shadow:0 10px 30px -12px rgba(0,30,80,.35)">'
        + '<div style="flex:1;height:.6rem;border-radius:9999px;background:var(--ypl-bg);overflow:hidden"><div data-role="fill" style="height:100%;width:0%;border-radius:9999px;background:linear-gradient(90deg,var(--ypl-navy),var(--ypl-navy-600));transition:width .15s"></div></div>'
        + '<strong data-role="pct" style="font-size:.8rem;color:var(--ypl-navy);min-width:3rem;text-align:right">0%</strong>'
        + '<button type="button" data-role="cancel" style="font-size:.75rem;font-weight:700;color:var(--ypl-red)">Batal</button></div>';
      form.prepend(bar);
      var fill = bar.querySelector('[data-role="fill"]');
      var pct = bar.querySelector('[data-role="pct"]');
      var submitBtn = form.querySelector('[type="submit"], button:not([type])');
      if (submitBtn) submitBtn.disabled = true;

      var xhr = new XMLHttpRequest();
      bar.querySelector('[data-role="cancel"]').addEventListener('click', function () { xhr.abort(); });
      xhr.upload.addEventListener('progress', function (ev) {
        if (!ev.lengthComputable) return;
        var p = Math.round(ev.loaded / ev.total * 100);
        fill.style.width = p + '%';
        pct.textContent = p + '%';
      });
      xhr.addEventListener('load', function () {
        if (xhr.status >= 200 && xhr.status < 300) {
          fill.style.width = '100%';
          pct.textContent = '100%';
          window.location.href = xhr.responseURL || window.location.href;
          return;
        }
        bar.remove();
        if (submitBtn) submitBtn.disabled = false;
        var msg = 'Unggahan gagal (kode ' + xhr.status + ').';
        try {
          var res = JSON.parse(xhr.responseText);
          if (res && res.errors) msg = Object.values(res.errors).flat().join(' ');
          else if (res && res.message) msg = res.message;
        } catch (err) { /* pakai pesan default */ }
        Swal.fire({ icon: 'error', title: 'Unggahan gagal', text: msg, confirmButtonText: 'Mengerti', confirmButtonColor: '#002f80', customClass: { popup: 'rounded-3xl' } });
      });
      xhr.addEventListener('error', function () {
        bar.remove();
        if (submitBtn) submitBtn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Koneksi terputus', text: 'Periksa internet lalu coba lagi.', confirmButtonText: 'Mengerti', confirmButtonColor: '#002f80', customClass: { popup: 'rounded-3xl' } });
      });
      xhr.addEventListener('abort', function () {
        bar.remove();
        if (submitBtn) submitBtn.disabled = false;
      });
      xhr.open('POST', form.action);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.setRequestHeader('X-CSRF-TOKEN', form.querySelector('input[name="_token"]').value);
      xhr.send(new FormData(form));
    });
  });

  // Bookmark interaktif & toggled dengan AJAX (instan, animasi micro-interaction & toast feedback)
  document.addEventListener('submit', function (e) {
    var form = e.target.closest('form[data-bookmark-form]');
    if (!form) return;
    e.preventDefault();

    var btn = form.querySelector('[data-bookmark-btn], button[type="submit"]');
    if (!btn || btn.dataset.busy === '1') return;
    btn.dataset.busy = '1';
    btn.classList.add('opacity-75', 'scale-95');

    var tokenInput = form.querySelector('input[name="_token"]');
    var token = tokenInput ? tokenInput.value : '';

    fetch(form.action, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token
      }
    })
    .then(function (res) {
      if (res.status === 401 || res.redirected) {
        window.location.href = '{{ route('login') }}';
        return null;
      }
      return res.json();
    })
    .then(function (data) {
      if (!data || typeof data.marked === 'undefined') return;
      var isMarked = Boolean(data.marked);
      updateBookmarkBtn(form, isMarked);

      // Sinkronisasi tombol bookmark untuk dokumen yang sama bila ada lebih dari satu di layar
      var docId = form.getAttribute('data-doc');
      if (docId) {
        document.querySelectorAll('form[data-bookmark-form][data-doc="' + docId + '"]').forEach(function (otherForm) {
          if (otherForm !== form) updateBookmarkBtn(otherForm, isMarked);
        });
      }

      if (window.Swal) {
        var Toast = Swal.mixin({
          toast: true, position: 'top-end', showConfirmButton: false, timer: 2000,
          customClass: { popup: 'rounded-2xl', title: '!text-xs font-bold' }
        });
        Toast.fire({
          icon: isMarked ? 'success' : 'info',
          title: data.message || (isMarked ? 'Ditambahkan ke bookmark' : 'Dihapus dari bookmark')
        });
      }
    })
    .catch(function (err) {
      console.error('Bookmark toggle failed, falling back:', err);
      form.submit();
    })
    .finally(function () {
      btn.dataset.busy = '0';
      btn.classList.remove('opacity-75', 'scale-95');
    });
  });

  function updateBookmarkBtn(form, isMarked) {
    var btn = form.querySelector('[data-bookmark-btn], button[type="submit"]');
    if (!btn) return;
    btn.setAttribute('data-marked', isMarked ? '1' : '0');
    btn.setAttribute('aria-pressed', isMarked ? 'true' : 'false');
    var labelOn = btn.getAttribute('data-label-on') || 'Tersimpan';
    var labelOff = btn.getAttribute('data-label-off') || 'Bookmark';
    btn.setAttribute('aria-label', isMarked ? labelOn : labelOff);

    var iconMarked = form.querySelector('[data-icon-marked]');
    var iconUnmarked = form.querySelector('[data-icon-unmarked]');
    if (iconMarked && iconUnmarked) {
      iconMarked.classList.toggle('hidden', !isMarked);
      iconMarked.classList.toggle('inline-flex', isMarked);
      iconUnmarked.classList.toggle('hidden', isMarked);
      iconUnmarked.classList.toggle('inline-flex', !isMarked);
    }

    var label = form.querySelector('[data-label]');
    if (label) {
      label.textContent = isMarked ? labelOn : labelOff;
    }

    var activeClasses = (btn.getAttribute('data-active-class') || '').split(' ').filter(Boolean);
    var inactiveClasses = (btn.getAttribute('data-inactive-class') || '').split(' ').filter(Boolean);
    if (isMarked) {
      inactiveClasses.forEach(function (c) { btn.classList.remove(c); });
      activeClasses.forEach(function (c) { btn.classList.add(c); });
    } else {
      activeClasses.forEach(function (c) { btn.classList.remove(c); });
      inactiveClasses.forEach(function (c) { btn.classList.add(c); });
    }

    btn.classList.add('scale-105');
    setTimeout(function () { btn.classList.remove('scale-105'); }, 180);
  }
});
</script>
@stack('scripts')
</body>
</html>
