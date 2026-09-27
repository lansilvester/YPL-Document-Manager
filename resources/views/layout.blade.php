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
}
</style>
</head>
<body class="bg-[#eef1f4] text-slate-800 min-h-screen flex flex-col antialiased">

<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-[#d6d6d6] shadow-[0_2px_20px_-12px_rgba(0,30,80,.45)]">
  <div class="h-1" style="background:linear-gradient(90deg,#002f80 60%,#c90018 60%)"></div>
  {{-- Baris 1: logo + pencarian + akun + toggle kanan --}}
  <div class="max-w-6xl mx-auto px-4 pt-3 pb-2 flex items-center gap-2 sm:gap-3">
    <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 min-w-0">
      <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo {{ \App\Models\Setting::get('foundation_name') }}" class="h-10 sm:h-12 w-auto rounded-lg bg-white object-contain">
      <span class="leading-tight hidden min-[420px]:block">
        <span class="block font-extrabold tracking-tight text-slate-900 text-[15px]">{{ \App\Models\Setting::get('site_name') }}</span>
        <span class="block text-[11px] font-bold tracking-wide text-[#c90018]">{{ strtoupper(\App\Models\Setting::get('foundation_name')) }}</span>
      </span>
    </a>
    <div class="flex items-center gap-2 ml-auto shrink-0">
      @auth
        <div class="relative" id="profile-menu">
          <button type="button" id="profile-menu-btn" aria-haspopup="true" aria-expanded="false" class="flex items-center gap-2 rounded-full pl-1 pr-2 py-1 border border-[#d6d6d6] hover:border-[#002f80] hover:shadow transition bg-white">
            <span class="w-8 h-8 rounded-full grid place-items-center text-white text-sm font-bold overflow-hidden shrink-0" style="background:linear-gradient(135deg,#002f80,#0046c0)">
              @if(auth()->user()->avatar_path)<img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" class="w-8 h-8 object-cover" alt="">@else{{ strtoupper(substr(auth()->user()->name,0,1)) }}@endif
            </span>
            <span class="hidden xl:block text-sm font-bold text-slate-700 max-w-28 truncate">{{ auth()->user()->name }}</span>
            @include('components.icon', ['name' => 'chev-down', 'class' => 'w-4 h-4 text-slate-400'])
          </button>
          <div id="profile-menu-panel" class="hidden absolute right-0 mt-2 w-60 rounded-2xl border border-[#d6d6d6] bg-white shadow-xl shadow-[#002f80]/10 overflow-hidden z-50">
            <div class="px-4 py-3 border-b border-slate-100" style="background:linear-gradient(135deg,#000f2e,#002f80)">
              <div class="font-extrabold text-white text-sm truncate">{{ auth()->user()->name }}</div>
              <div class="text-[11px] text-slate-300 truncate">{{ auth()->user()->email }} · {{ strtoupper(auth()->user()->role) }}</div>
            </div>
            <div class="p-1.5 text-sm font-semibold">
              <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'users', 'class' => 'w-4 h-4']) Profil Saya</a>
              <a href="{{ route('documents.my') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'folder', 'class' => 'w-4 h-4']) Dokumen Saya</a>
              <a href="{{ route('favorites.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'star', 'class' => 'w-4 h-4']) Favorit</a>
              @if(auth()->user()->isStaff())
                <a href="{{ route('staff.documents') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'tool', 'class' => 'w-4 h-4']) Ruang Staff</a>
              @endif
            </div>
            <div class="border-t border-slate-100 p-1.5">
              <form action="{{ route('logout') }}" method="post" data-confirm="Yakin ingin keluar dari aplikasi?">@csrf<button class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-bold text-[#c90018] hover:bg-[#c90018]/5">@include('components.icon', ['name' => 'logout', 'class' => 'w-4 h-4']) Keluar</button></form>
            </div>
          </div>
        </div>
      @else
        @unless(request()->routeIs('register', 'login'))
        <a href="{{ route('login') }}" class="hidden md:inline-block px-4 py-1.5 rounded-full text-sm font-semibold hover:bg-[#002f80]/5 hover:text-[#002f80] transition">Masuk</a>
        @if(\App\Models\Setting::feature('reg'))<a href="{{ route('register') }}" class="btn-primary !py-1.5 hidden md:inline-flex">Daftar Gratis</a>@endif
        @endunless
      @endauth
    </div>
    {{-- Toggle menu mobile di kanan --}}
    <button type="button" id="nav-toggle" aria-label="Buka menu" aria-expanded="false" aria-controls="mobile-nav" class="md:hidden w-10 h-10 rounded-xl grid place-items-center border border-[#d6d6d6] text-[#002f80] shrink-0">
      <span id="nav-icon-open">@include('components.icon', ['name' => 'menu', 'class' => 'w-5 h-5'])</span>
      <span id="nav-icon-close" class="hidden">@include('components.icon', ['name' => 'x', 'class' => 'w-5 h-5'])</span>
    </button>
  </div>
  {{-- Baris 2: menu navigasi desktop (geser horizontal, anti-tumpuk) --}}
  <nav class="border-t border-slate-100 hidden md:block" aria-label="Navigasi utama">
    <div class="max-w-6xl mx-auto px-4 flex items-center gap-1 text-sm font-semibold overflow-x-auto whitespace-nowrap py-1.5" style="scrollbar-width:none">
      <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'home', 'class' => 'w-4 h-4']) Beranda</a>
      <a href="{{ route('documents.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'compass', 'class' => 'w-4 h-4']) Jelajahi</a>
      @auth
        @if(\App\Models\Setting::feature('upload'))<a href="{{ route('documents.create') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'upload', 'class' => 'w-4 h-4']) Unggah</a>@endif
        @if(\App\Models\Setting::feature('editor'))<a href="{{ route('editor.create') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Tulis</a>@endif
        <a href="{{ route('documents.my') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'folder', 'class' => 'w-4 h-4']) Milik Saya</a>
        @if(\App\Models\Setting::feature('favorite'))<a href="{{ route('favorites.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'star', 'class' => 'w-4 h-4']) Favorit</a>@endif
        @if(auth()->user()->isStaff())<a href="{{ route('staff.documents') }}" class="px-3 py-1.5 rounded-full hover:bg-[#002f80]/5 hover:text-[#002f80] transition inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'tool', 'class' => 'w-4 h-4']) Staff</a>@endif
        @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-full bg-[#002f80] text-white hover:bg-[#001c55] transition">Admin</a>@endif
        @if(auth()->user()->isSuperAdmin())<a href="{{ route('sa.dashboard') }}" class="px-3 py-1.5 rounded-full bg-[#c90018] text-white hover:bg-[#7a0010] transition">SA</a>@endif
      @endauth
    </div>
  </nav>
  {{-- Panel menu mobile (hamburger) --}}
  <nav id="mobile-nav" class="hidden md:hidden border-t border-slate-100 bg-white" aria-label="Menu mobile">
    <div class="px-4 py-2 grid gap-1 text-sm font-semibold">
      @guest
        @unless(request()->routeIs('register', 'login'))
        <a href="{{ route('login') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'logout', 'class' => 'w-4 h-4 scale-x-[-1]']) Masuk</a>
        @if(\App\Models\Setting::feature('reg'))<a href="{{ route('register') }}" class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-white" style="background:linear-gradient(135deg,#002f80,#0046c0)">@include('components.icon', ['name' => 'plus', 'class' => 'w-4 h-4']) Daftar Gratis</a>@endif
        <div class="border-t border-slate-100 my-1"></div>
        @endunless
      @endguest
      <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'home', 'class' => 'w-4 h-4']) Beranda</a>
      <a href="{{ route('documents.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'compass', 'class' => 'w-4 h-4']) Jelajahi Dokumen</a>
      @auth
        <div class="border-t border-slate-100 my-1"></div>
        @if(\App\Models\Setting::feature('upload'))<a href="{{ route('documents.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'upload', 'class' => 'w-4 h-4']) Unggah PDF</a>@endif
        @if(\App\Models\Setting::feature('editor'))<a href="{{ route('editor.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Tulis Dokumen</a>@endif
        <a href="{{ route('documents.my') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'folder', 'class' => 'w-4 h-4']) Dokumen Saya</a>
        @if(\App\Models\Setting::feature('favorite'))<a href="{{ route('favorites.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'star', 'class' => 'w-4 h-4']) Favorit Saya</a>@endif
        @if(auth()->user()->isStaff())<a href="{{ route('staff.documents') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#002f80]/5 hover:text-[#002f80]">@include('components.icon', ['name' => 'tool', 'class' => 'w-4 h-4']) Ruang Staff</a>@endif
        @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#002f80] text-white">@include('components.icon', ['name' => 'shield', 'class' => 'w-4 h-4']) Dasbor Admin</a>@endif
        @if(auth()->user()->isSuperAdmin())<a href="{{ route('sa.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#c90018] text-white">@include('components.icon', ['name' => 'shield', 'class' => 'w-4 h-4']) Pusat Kendali SA</a>@endif
      @endauth
    </div>
  </nav>
</header>

@yield('hero')

<main class="max-w-6xl mx-auto px-4 py-6 w-full flex-1">
  @yield('content')
</main>

<footer class="mt-10 border-t border-[#d6d6d6] bg-white">
  <div class="h-1" style="background:linear-gradient(90deg,#002f80 60%,#c90018 60%)"></div>
  <div class="max-w-6xl mx-auto px-4 py-8 grid sm:grid-cols-3 gap-6 text-sm">
    <div>
      <div class="flex items-center gap-2">
        <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo YPL" class="h-10 w-auto rounded-md bg-white object-contain">
        <span class="font-extrabold text-[#002f80]">{{ \App\Models\Setting::get('site_name') }}</span>
      </div>
      <p class="text-slate-500 mt-1">Platform dokumen digital {{ \App\Models\Setting::get('foundation_name') }} — baca, unggah, tulis, dan bagikan pengetahuan.</p>
    </div>
    <div>
      <div class="font-bold text-slate-900 mb-2">Jelajahi</div>
      <div class="flex flex-col gap-1 text-slate-500">
        <a class="hover:text-[#002f80]" href="{{ route('documents.index') }}">Semua dokumen</a>
        @if(\App\Models\Setting::feature('upload'))<a class="hover:text-[#002f80]" href="{{ route('documents.create') }}">Unggah PDF</a>@endif
        @if(\App\Models\Setting::feature('editor'))<a class="hover:text-[#002f80]" href="{{ route('editor.create') }}">Tulis dokumen</a>@endif
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
      if (window.innerWidth >= 768) setNav(false);
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
          + '<span style="min-width:1.35rem;height:1.35rem;border-radius:9999px;background:#fbe0e4;color:#a30014;font-size:.7rem;font-weight:800;display:inline-flex;align-items:center;justify-content:center">' + (i + 1) + '</span>'
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
      bar.innerHTML = '<div style="display:flex;align-items:center;gap:.6rem;background:#fff;border:1px solid #d6d6d6;border-radius:1rem;padding:.7rem 1rem;margin-bottom:.8rem;box-shadow:0 10px 30px -12px rgba(0,30,80,.35)">'
        + '<div style="flex:1;height:.6rem;border-radius:9999px;background:#eef1f4;overflow:hidden"><div data-role="fill" style="height:100%;width:0%;border-radius:9999px;background:linear-gradient(90deg,#002f80,#0046c0);transition:width .15s"></div></div>'
        + '<strong data-role="pct" style="font-size:.8rem;color:#002f80;min-width:3rem;text-align:right">0%</strong>'
        + '<button type="button" data-role="cancel" style="font-size:.75rem;font-weight:700;color:#c90018">Batal</button></div>';
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
});
</script>
@stack('scripts')
</body>
</html>
