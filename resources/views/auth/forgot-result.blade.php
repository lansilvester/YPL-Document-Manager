@extends('layout')
@section('title', 'Kode Reset')
@section('content')
<div class="max-w-md mx-auto">
  <div class="rounded-3xl overflow-hidden shadow-xl border border-[#d6d6d6]">
    <div class="p-6 text-white text-center" style="background:linear-gradient(135deg,#000f2e,#002f80)">
      <img src="{{ asset('images/logo-ypl.jpg') }}" alt="Logo YPL" class="h-14 w-auto mx-auto rounded-xl bg-white object-contain px-2 py-1">
      <h1 class="text-xl font-extrabold tracking-tight mt-2">Kode Reset</h1>
      <p class="text-xs text-slate-300">Berlaku {{ $ttl }} menit · sekali pakai</p>
    </div>
    <div class="bg-white p-6">
      @if($issued)
        <p class="text-[13px] text-slate-600 mb-3 text-center">Gunakan kode berikut untuk mengatur ulang kata sandi akun <strong class="text-slate-800">{{ $reset_email }}</strong>:</p>
        <div class="flex items-center gap-2 justify-between">
          <input type="text" readonly value="{{ $reset_token }}" onclick="this.select();" class="field text-center !text-xl !font-mono !tracking-widest !py-3 font-bold text-[#002f80]">
          <button type="button" onclick="navigator.clipboard.writeText('{{ $reset_token }}')" class="btn-secondary shrink-0" title="Salin kode">@include('components.icon', ['name' => 'copy', 'class' => 'w-4 h-4'])</button>
        </div>
        <a href="{{ route('password.reset', ['token' => $reset_token, 'email' => $reset_email]) }}" class="btn-primary w-full !py-3 mt-4 text-center">Lanjutkan Reset @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4'])</a>
      @else
        <p class="text-[13px] text-slate-600 text-center mb-3">Data yang Anda masukkan tidak cocok dengan akun terdaftar, sehingga kode reset <strong>tidak diterbitkan</strong>.</p>
        <p class="text-[12px] text-slate-500 text-center">Pastikan email dan nama lengkap sesuai akun Anda, lalu coba lagi. Bila tetap terkendala, hubungi pengelola situs.</p>
        <a href="{{ route('password.request') }}" class="btn-primary w-full !py-3 mt-4 text-center">Coba Lagi</a>
      @endif
      <p class="text-[13px] text-center text-slate-500 mt-4"><a class="text-[#002f80] underline font-bold" href="{{ route('login') }}">Kembali ke Masuk</a></p>
    </div>
  </div>
</div>
@endsection