@extends('layout')
@section('title', 'Daftar')
@section('content')
<div class="max-w-md mx-auto">
  <div class="rounded-3xl overflow-hidden shadow-xl border border-[#d6d6d6]">
    <div class="p-6 text-white text-center" style="background:linear-gradient(135deg,#000f2e,#002f80)">
      <span class="inline-block w-12 h-12 rounded-2xl bg-white/15 border border-white/25 place-items-center grid">@include('components.icon', ['name' => 'cap', 'class' => 'w-6 h-6'])</span>
      <h1 class="text-xl font-extrabold tracking-tight mt-2">Bergabung dengan YPL</h1>
      <p class="text-xs text-slate-300">Gunakan email resmi yayasan untuk mendaftar.</p>
    </div>
    <form method="post" action="/daftar" class="space-y-4 bg-white p-6">@csrf
      <div><label class="text-[13px] font-bold">Nama lengkap</label><input name="name" required value="{{ old('name') }}" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Email YPL</label><input name="email" type="email" required value="{{ old('email') }}" placeholder="nama@lokon.or.id" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Info akademik <span class="font-normal text-slate-400">(opsional)</span></label><input name="academic_info" value="{{ old('academic_info') }}" placeholder="cth: Guru Matematika / Kelas 8A" class="field mt-1"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-[13px] font-bold">Kata sandi</label><input name="password" type="password" required class="field mt-1"></div>
        <div><label class="text-[13px] font-bold">Konfirmasi</label><input name="password_confirmation" type="password" required class="field mt-1"></div>
      </div>
      <button class="btn-primary w-full !py-3">Buat Akun @include('components.icon', ['name' => 'check', 'class' => 'w-4 h-4'])</button>
    </form>
  </div>
</div>
@endsection
