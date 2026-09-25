@extends('layout')
@section('title', 'Masuk')
@section('content')
<div class="max-w-md mx-auto">
  <div class="rounded-3xl overflow-hidden shadow-xl">
    <div class="p-6 text-white text-center" style="background:linear-gradient(135deg,#052e22,#0d9488)">
      <div class="text-3xl">📚</div>
      <h1 class="text-xl font-extrabold tracking-tight mt-1">Selamat datang kembali</h1>
      <p class="text-xs text-emerald-100">Masuk untuk melanjutkan bacaanmu.</p>
    </div>
    <form method="post" action="/masuk" class="space-y-4 bg-white p-6">@csrf
      <div><label class="text-[13px] font-bold">Email</label><input name="email" type="email" required value="{{ old('email') }}" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Kata sandi</label><input name="password" type="password" required class="field mt-1"></div>
      <label class="text-[13px] flex gap-2 items-center text-slate-500"><input type="checkbox" name="remember" value="1" class="accent-emerald-600"> Ingat saya</label>
      <button class="btn-primary w-full !py-3">Masuk →</button>
      <p class="text-[13px] text-center text-slate-500">Belum punya akun? <a class="text-emerald-700 underline font-bold" href="{{ route('register') }}">Daftar</a></p>
    </form>
  </div>
</div>
@endsection
