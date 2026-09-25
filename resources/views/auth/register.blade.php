@extends('layout')
@section('title', 'Daftar')
@section('content')
<div class="max-w-md mx-auto">
  <div class="rounded-3xl overflow-hidden shadow-xl">
    <div class="p-6 text-white text-center" style="background:linear-gradient(135deg,#052e22,#0d9488)">
      <div class="text-3xl">🎓</div>
      <h1 class="text-xl font-extrabold tracking-tight mt-1">Bergabung dengan YPL</h1>
      <p class="text-xs text-emerald-100">Gunakan email resmi yayasan untuk mendaftar.</p>
    </div>
    <form method="post" action="/daftar" class="space-y-4 bg-white p-6">@csrf
      <div><label class="text-[13px] font-bold">Nama lengkap</label><input name="name" required value="{{ old('name') }}" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Email YPL</label><input name="email" type="email" required value="{{ old('email') }}" placeholder="nama@lokon.or.id" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Info akademik <span class="font-normal text-slate-400">(opsional)</span></label><input name="academic_info" value="{{ old('academic_info') }}" placeholder="cth: Guru Matematika / Kelas 8A" class="field mt-1"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-[13px] font-bold">Kata sandi</label><input name="password" type="password" required class="field mt-1"></div>
        <div><label class="text-[13px] font-bold">Konfirmasi</label><input name="password_confirmation" type="password" required class="field mt-1"></div>
      </div>
      <button class="btn-primary w-full !py-3">Buat Akun ✨</button>
    </form>
  </div>
</div>
@endsection
