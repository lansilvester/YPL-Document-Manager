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

<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-[#d6d6d6] shadow-[0_2px_20px_-12px_rgba(0,30,80,.45)]">
  <div class="h-1" style="background:linear-gradient(90deg,#002f80 60%,#c90018 60%)"></div>
  {{-- Baris 1: logo + pencarian + akun --}}
  <div class="max-w-6xl mx-auto px-4 pt-3 pb-2 flex items-center gap-3">
    <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0">
      <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo {{ \App\Models\Setting::get('foundation_name') }}" class="h-12 w-auto rounded-lg bg-white object-contain">
      <span class="leading-tight hidden sm:block">
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
              <form action="{{ route('logout') }}" method="post" onsubmit="return confirm('Yakin ingin keluar dari aplikasi?')">@csrf<button class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-bold text-[#c90018] hover:bg-[#c90018]/5">@include('components.icon', ['name' => 'logout', 'class' => 'w-4 h-4']) Keluar</button></form>
            </div>
          </div>
        </div>
      @else
        <a href="{{ route('login') }}" class="px-4 py-1.5 rounded-full text-sm font-semibold hover:bg-[#002f80]/5 hover:text-[#002f80] transition">Masuk</a>
        @if(\App\Models\Setting::feature('reg'))<a href="{{ route('register') }}" class="btn-primary !py-1.5">Daftar Gratis</a>@endif
      @endauth
    </div>
  </div>
  {{-- Baris 2: menu navigasi (geser horizontal, anti-tumpuk) --}}
  <nav class="border-t border-slate-100">
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
</header>

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
  var Toast = Swal.mixin({
    toast: true, position: 'top-end', showConfirmButton: false,
    timer: 3500, timerProgressBar: true,
    customClass: { popup: 'rounded-2xl' },
  });
  @if(session('ok'))
    Toast.fire({ icon: 'success', title: @json(session('ok')) });
  @endif
  @if($errors->any())
    Swal.fire({
      icon: 'error', title: 'Periksa kembali',
      html: @json('<ul style="text-align:left">' . collect($errors->all())->map(fn ($e) => '<li>' . e($e) . '</li>')->implode('') . '</ul>'),
      confirmButtonText: 'Mengerti',
      confirmButtonColor: '#002f80',
      customClass: { popup: 'rounded-3xl' },
    });
  @endif
  // Konfirmasi cantik untuk form destruktif (data-confirm="pesan…?")
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning', title: 'Yakin?',
        text: form.getAttribute('data-confirm'),
        showCancelButton: true, confirmButtonText: 'Ya, lanjutkan', cancelButtonText: 'Batal',
        confirmButtonColor: '#c90018', cancelButtonColor: '#002f80',
        customClass: { popup: 'rounded-3xl' },
      }).then(function (r) { if (r.isConfirmed) form.submit(); });
    });
  });
});
</script>
@stack('scripts')
</body>
</html>
