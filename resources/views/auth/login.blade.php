@extends('layout')
@section('title', 'Masuk')
@section('content')
<div class="max-w-md mx-auto">
  <div class="rounded-3xl overflow-hidden shadow-xl border border-[#d6d6d6]">
    <div class="p-6 text-white text-center" style="background:linear-gradient(135deg,#000f2e,#002f80)">
      <img src="{{ asset('images/logo-ypl.jpg') }}" alt="Logo YPL" class="h-14 w-auto mx-auto rounded-xl bg-white object-contain px-2 py-1">
      <h1 class="text-xl font-extrabold tracking-tight mt-2">Selamat datang kembali</h1>
      <p class="text-xs text-slate-300">Masuk untuk melanjutkan bacaanmu.</p>
    </div>
    <form method="post" action="/masuk" class="space-y-4 bg-white p-6">@csrf
      <div><label class="text-[13px] font-bold">Email</label><input name="email" type="email" required value="{{ old('email') }}" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Kata sandi</label><input name="password" type="password" required class="field mt-1"></div>
      <label class="text-[13px] flex gap-2 items-center text-slate-500"><input type="checkbox" name="remember" value="1" class="accent-[#002f80]"> Ingat saya</label>
      <button class="btn-primary w-full !py-3">Masuk @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4'])</button>
      <p class="text-[12px] text-center"><a class="text-[#002f80] underline font-semibold" href="{{ route('password.request') }}">Lupa kata sandi?</a></p>
      <p class="text-[13px] text-center text-slate-500">Jika belum punya akun, silahkan <a class="text-[#002f80] underline font-bold" href="{{ route('register') }}">Daftar</a></p>
    </form>
  </div>
</div>
@endsection
