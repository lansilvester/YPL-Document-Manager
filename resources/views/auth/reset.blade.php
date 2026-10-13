@extends('layout')
@section('title', 'Atur Ulang Kata Sandi')
@section('content')
<div class="max-w-md mx-auto">
  <div class="rounded-3xl overflow-hidden shadow-xl border border-[var(--ypl-gray)]">
    <div class="p-6 text-white text-center" style="background:linear-gradient(135deg,var(--ypl-navy-900),var(--ypl-navy))">
      <img src="{{ asset('images/logo-ypl.jpg') }}" alt="Logo YPL" class="h-14 w-auto mx-auto rounded-xl bg-white object-contain px-2 py-1">
      <h1 class="text-xl font-extrabold tracking-tight mt-2">Atur ulang kata sandi</h1>
      <p class="text-xs text-slate-300">Untuk akun <strong>{{ $email }}</strong></p>
    </div>
    <form method="post" action="{{ route('password.update') }}" class="space-y-4 bg-white p-6">@csrf
      <input type="hidden" name="email" value="{{ $email }}">
      <input type="hidden" name="token" value="{{ $token }}">
      <div><label class="text-[13px] font-bold">Kode reset</label><input type="text" readonly value="{{ $token }}" class="field mt-1 text-center font-mono tracking-widest text-[var(--ypl-navy)] font-bold"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-[13px] font-bold">Kata sandi baru</label><input name="password" type="password" required class="field mt-1" autofocus></div>
        <div><label class="text-[13px] font-bold">Konfirmasi</label><input name="password_confirmation" type="password" required class="field mt-1"></div>
      </div>
      @error('token')<p class="text-xs font-semibold text-[var(--ypl-red)]">{{ $message }}</p>@enderror
      @error('password')<p class="text-xs font-semibold text-[var(--ypl-red)]">{{ $message }}</p>@enderror
      <p class="text-[11px] text-slate-400 -mt-2">Min. 8 karakter, wajib ada <strong>huruf besar</strong>, <strong>huruf kecil</strong>, <strong>angka</strong>, dan <strong>karakter khusus</strong> (cth: <span class="font-mono">Lokon#2026</span>).</p>
      <button class="btn-primary w-full !py-3">Simpan Kata Sandi Baru @include('components.icon', ['name' => 'check', 'class' => 'w-4 h-4'])</button>
      <p class="text-[13px] text-center text-slate-500"><a class="text-[var(--ypl-navy)] underline font-bold" href="{{ route('login') }}">Kembali ke Masuk</a></p>
    </form>
  </div>
</div>
@endsection