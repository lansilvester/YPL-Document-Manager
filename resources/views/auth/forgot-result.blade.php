@extends('layout')
@section('title', 'Periksa Email')
@section('content')
<div class="max-w-md mx-auto">
  <div class="rounded-3xl overflow-hidden shadow-xl border border-[var(--ypl-gray)]">
    <div class="p-6 text-white text-center" style="background:linear-gradient(135deg,var(--ypl-navy-900),var(--ypl-navy))">
      <img src="{{ asset('images/logo-ypl.jpg') }}" alt="Logo YPL" class="h-14 w-auto mx-auto rounded-xl bg-white object-contain px-2 py-1">
      <h1 class="text-xl font-extrabold tracking-tight mt-2">Periksa Email Anda</h1>
      <p class="text-xs text-slate-300">Kode berlaku {{ $ttl }} menit · sekali pakai</p>
    </div>
    <div class="bg-white p-6">
      <p class="text-[13px] text-slate-600 mb-3 text-center">Bila <strong class="text-slate-800">{{ $reset_email }}</strong> terdaftar, kami telah mengirim <strong>kode reset 8 karakter</strong> ke email tersebut. Salin kode dari email itu.</p>
      <p class="text-[12px] text-slate-500 text-center">Tidak menemukan emailnya? Periksa folder spam/promosi. Demi keamanan, kami tidak menampilkan kode di halaman ini maupun memberi tahu apakah email terdaftar.</p>
      <a href="{{ route('password.reset', ['email' => $reset_email]) }}" class="btn-primary w-full !py-3 mt-4 text-center">Sudah Punya Kode — Lanjut Reset @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4'])</a>
      <p class="text-[13px] text-center text-slate-500 mt-4"><a class="text-[var(--ypl-navy)] underline font-bold" href="{{ route('password.request') }}">Kirim ulang</a> · <a class="text-[var(--ypl-navy)] underline font-bold" href="{{ route('login') }}">Kembali ke Masuk</a></p>
    </div>
  </div>
</div>
@endsection
