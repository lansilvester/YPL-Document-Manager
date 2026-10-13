@extends('layout')
@section('title', 'Lupa Kata Sandi')
@section('content')
<div class="max-w-md mx-auto">
  <div class="rounded-3xl overflow-hidden shadow-xl border border-[var(--ypl-gray)]">
    <div class="p-6 text-white text-center" style="background:linear-gradient(135deg,var(--ypl-navy-900),var(--ypl-navy))">
      <img src="{{ asset('images/logo-ypl.jpg') }}" alt="Logo YPL" class="h-14 w-auto mx-auto rounded-xl bg-white object-contain px-2 py-1">
      <h1 class="text-xl font-extrabold tracking-tight mt-2">Lupa kata sandi?</h1>
      <p class="text-xs text-slate-300">Kami akan menyiapkan kode reset. Kode tampil satu kali di layar dan berlaku {{ $ttl ?? 15 }} menit.</p>
    </div>
    <form method="post" action="{{ route('password.email') }}" class="space-y-4 bg-white p-6">@csrf
      <div>
        <label class="text-[13px] font-bold">Email terdaftar</label>
        <input name="email" type="email" required value="{{ old('email') }}" class="field mt-1">
        @error('email')<p class="text-xs font-semibold text-[var(--ypl-red)] mt-1">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="text-[13px] font-bold">Nama lengkap <span class="font-normal text-slate-400">(sesuai akun)</span></label>
        <input name="name" required value="{{ old('name') }}" class="field mt-1">
        @error('name')<p class="text-xs font-semibold text-[var(--ypl-red)] mt-1">{{ $message }}</p>@enderror
      </div>
      <button class="btn-primary w-full !py-3">Buat Kode Reset @include('components.icon', ['name' => 'lock', 'class' => 'w-4 h-4'])</button>
      <p class="text-[13px] text-center text-slate-500"><a class="text-[var(--ypl-navy)] underline font-bold" href="{{ route('login') }}">Kembali ke Masuk</a></p>
    </form>
  </div>
</div>
@endsection