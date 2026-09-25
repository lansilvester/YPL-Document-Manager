@extends('layout')
@section('title', 'Profil')
@section('content')
<div class="max-w-md mx-auto bg-white rounded-3xl border border-slate-200 shadow-xl p-7">
  <div class="flex items-center gap-4 mb-5">
    @if($user->avatar_path)
      <img src="{{ asset('storage/'.$user->avatar_path) }}" class="w-16 h-16 rounded-2xl object-cover shadow">
    @else
      <span class="w-16 h-16 rounded-2xl grid place-items-center text-white text-2xl font-extrabold shadow" style="background:linear-gradient(135deg,#0d9488,#065f46)">{{ strtoupper(substr($user->name,0,1)) }}</span>
    @endif
    <div><h1 class="text-xl font-extrabold tracking-tight">Profil Saya</h1><p class="text-xs text-slate-400">{{ $user->email }}</p></div>
  </div>
  <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">@csrf @method('PUT')
    <div><label class="text-[13px] font-bold">Nama</label><input name="name" required value="{{ old('name', $user->name) }}" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Info akademik</label><input name="academic_info" value="{{ old('academic_info', $user->academic_info) }}" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Foto profil</label><input type="file" name="avatar" accept="image/*" class="field mt-1 file:mr-3 file:rounded-full file:border-0 file:bg-emerald-100 file:text-emerald-800 file:text-xs file:font-bold file:px-4 file:py-2"></div>
    <button class="btn-primary w-full !py-3">Simpan Profil</button>
  </form>
</div>
@endsection
