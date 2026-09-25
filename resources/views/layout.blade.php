<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Lokon Dokumen Digital') — YPL</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-stone-100 text-stone-900 min-h-screen flex flex-col">
<nav class="bg-emerald-900 text-white">
  <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center gap-3">
    <a href="{{ route('home') }}" class="font-bold text-lg">📚 Lokon Dokumen Digital</a>
    <span class="text-emerald-300 text-sm hidden sm:inline">Yayasan Pendidikan Lokon</span>
    <form action="{{ route('home') }}" method="get" class="flex gap-2 ml-auto">
      <input name="q" value="{{ $q ?? request('q') }}" placeholder="Cari judul / deskripsi…" class="rounded px-3 py-1.5 text-sm text-stone-900 w-44 sm:w-64">
      <button class="bg-emerald-600 hover:bg-emerald-500 rounded px-3 py-1.5 text-sm">Cari</button>
    </form>
    <div class="flex items-center gap-3 text-sm">
      @auth
        <a href="{{ route('documents.my') }}" class="hover:underline">Dokumen Saya</a>
        <a href="{{ route('favorites.index') }}" class="hover:underline">Favorit</a>
        @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="hover:underline">Admin</a>@endif
        <a href="{{ route('profile.edit') }}" class="hover:underline">{{ auth()->user()->name }}</a>
        <form action="{{ route('logout') }}" method="post">@csrf<button class="underline">Keluar</button></form>
      @else
        <a href="{{ route('login') }}" class="hover:underline">Masuk</a>
        <a href="{{ route('register') }}" class="bg-emerald-600 rounded px-3 py-1.5">Daftar</a>
      @endauth
    </div>
  </div>
  <div class="bg-emerald-950">
    <div class="max-w-6xl mx-auto px-4 py-2 flex flex-wrap gap-2 text-sm">
      <a href="{{ route('home') }}" class="hover:underline">Beranda</a>
      <a href="{{ route('documents.index') }}" class="hover:underline">Semua Dokumen</a>
      @auth
        <a href="{{ route('documents.create') }}" class="hover:underline">⬆ Unggah PDF</a>
        <a href="{{ route('editor.create') }}" class="hover:underline">✍ Tulis Dokumen</a>
      @endauth
    </div>
  </div>
</nav>
<main class="max-w-6xl mx-auto px-4 py-6 w-full flex-1">
  @if(session('ok'))<div class="mb-4 rounded bg-emerald-100 border border-emerald-300 text-emerald-900 px-4 py-2">{{ session('ok') }}</div>@endif
  @if($errors->any())<div class="mb-4 rounded bg-red-100 border border-red-300 text-red-900 px-4 py-2"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</main>
<footer class="text-center text-xs text-stone-500 py-6">Lokon Dokumen Digital · Yayasan Pendidikan Lokon · {{ date('Y') }}</footer>
@stack('scripts')
</body>
</html>
