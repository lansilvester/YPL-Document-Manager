@extends('layout')
@section('title', 'Daftar')
@section('content')
<div class="max-w-md mx-auto">
  <div class="rounded-3xl overflow-hidden shadow-xl border border-[#d6d6d6]">
    <div class="p-6 text-white text-center" style="background:linear-gradient(135deg,#000f2e,#002f80)">
      <img src="{{ asset('images/logo-ypl.jpg') }}" alt="Logo YPL" class="h-14 w-auto mx-auto rounded-xl bg-white object-contain px-2 py-1">
      <h1 class="text-xl font-extrabold tracking-tight mt-2">Bergabung dengan YPL</h1>
      <p class="text-xs text-slate-300">Daftar dengan email aktif untuk mulai membaca.</p>
    </div>
    <form method="post" action="/daftar" class="space-y-4 bg-white p-6">@csrf
      <div><label class="text-[13px] font-bold">Nama lengkap</label><input name="name" required value="{{ old('name') }}" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Email</label><input name="email" type="email" required value="{{ old('email') }}" placeholder="nama@email.com" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Nomor WA <span class="font-normal text-slate-400">(opsional)</span></label><input name="wa_number" value="{{ old('wa_number') }}" placeholder="cth: 089612345678" inputmode="tel" class="field mt-1">
        <p class="text-[11px] text-slate-400 mt-1">Format bebas: <span class="font-mono">0896…</span>, <span class="font-mono">+62896…</span>, <span class="font-mono">62896…</span> — otomatis dirapikan.</p>
        @error('wa_number')<p class="text-xs font-semibold text-[#c90018] mt-1">{{ $message }}</p>@enderror</div>
      <div><label class="text-[13px] font-bold">Info akademik <span class="font-normal text-slate-400">(opsional)</span></label><input name="academic_info" value="{{ old('academic_info') }}" placeholder="cth: Guru Matematika / Kelas 8A" class="field mt-1"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-[13px] font-bold">Kata sandi</label><input name="password" type="password" required class="field mt-1"></div>
        <div><label class="text-[13px] font-bold">Konfirmasi</label><input name="password_confirmation" type="password" required class="field mt-1"></div>
      </div>
      <p class="text-[11px] text-slate-400 -mt-2">Min. 8 karakter, wajib ada <strong>huruf besar</strong>, <strong>huruf kecil</strong>, <strong>angka</strong>, dan <strong>karakter khusus</strong> (cth: <span class="font-mono">Lokon#2026</span>).</p>
      <button class="btn-primary w-full !py-3">Buat Akun @include('components.icon', ['name' => 'check', 'class' => 'w-4 h-4'])</button>
      <p class="text-[13px] text-center text-slate-500">Jika sudah punya akun, silahkan <a class="text-[#002f80] underline font-bold" href="{{ route('login') }}">Masuk</a></p>
    </form>
  </div>
</div>
@endsection
