@extends('layout')
@section('title', 'Profil')
@section('content')
<div class="max-w-md mx-auto bg-white rounded-3xl border border-[#d6d6d6] shadow-xl p-7">
  <div class="flex items-center gap-4 mb-5">
    @if($user->avatar_path)
      <img src="{{ asset('storage/'.$user->avatar_path) }}" class="w-16 h-16 rounded-2xl object-cover shadow">
    @else
      <span class="w-16 h-16 rounded-2xl grid place-items-center text-white text-2xl font-extrabold shadow" style="background:linear-gradient(135deg,#001c55,#0046c0)">{{ strtoupper(substr($user->name,0,1)) }}</span>
    @endif
    <div><h1 class="text-xl font-extrabold tracking-tight text-[#002f80]">Profil Saya</h1><p class="text-xs text-slate-400">{{ $user->email }}</p></div>
  </div>
  <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">@csrf @method('PUT')
    <div><label class="text-[13px] font-bold">Nama</label><input name="name" required value="{{ old('name', $user->name) }}" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Info akademik</label><input name="academic_info" value="{{ old('academic_info', $user->academic_info) }}" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Foto profil</label><input type="file" name="avatar" accept="image/*" class="field mt-1 file:mr-3 file:rounded-full file:border-0 file:bg-[#002f80]/10 file:text-[#002f80] file:text-xs file:font-bold file:px-4 file:py-2"></div>
    <button class="btn-primary w-full !py-3">Simpan Profil</button>
  </form>
</div>

{{-- Peningkatan otoritas: hanya untuk Anggota --}}
@if($user->role === 'anggota')
<div class="max-w-md mx-auto bg-white rounded-3xl border border-[#d6d6d6] shadow-xl p-7 mt-5">
  <h2 class="font-extrabold text-[#002f80] flex items-center gap-2">@include('components.icon', ['name' => 'shield', 'class' => 'w-4 h-4']) Peningkatan Peran</h2>
  @if($pendingRequest)
    <p class="text-sm text-slate-500 mt-2">Pengajuanmu menjadi <strong>{{ $pendingRequest->requested_role }}</strong> sedang menunggu persetujuan Admin YPL.</p>
  @else
    <p class="text-xs text-slate-400 mt-1 mb-3">Peran Staff/Admin memerlukan persetujuan. Peran admin hanya dapat disetujui Super Admin.</p>
    <form method="post" action="{{ route('profile.role-request') }}" class="space-y-3">@csrf
      <div><label class="text-[13px] font-bold">Minta peran</label><select name="requested_role" class="field mt-1"><option value="staff">Staff — kelola perpustakaan</option><option value="admin">Admin — kelola yayasan</option></select></div>
      <div><label class="text-[13px] font-bold">Alasan (opsional)</label><textarea name="reason" rows="2" placeholder="cth: Saya pustakawan SMP Lokon…" class="field mt-1"></textarea></div>
      <button class="btn-ghost w-full">Kirim Pengajuan</button>
    </form>
  @endif
</div>
@endif
@endsection
