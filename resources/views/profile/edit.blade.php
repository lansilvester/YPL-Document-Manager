@extends('layout')
@section('title', 'Profil')
@section('content')
<div class="max-w-md mx-auto bg-white rounded shadow p-6">
  <h1 class="text-xl font-bold mb-4">Atur Profil</h1>
  <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-3">@csrf @method('PUT')
    <div class="flex items-center gap-3">
      @if($user->avatar_path)<img src="{{ asset('storage/'.$user->avatar_path) }}" class="w-14 h-14 rounded-full object-cover">@endif
      <div><label class="text-sm">Foto profil</label><input type="file" name="avatar" accept="image/*" class="w-full border rounded px-3 py-2 text-sm"></div>
    </div>
    <div><label class="text-sm">Nama</label><input name="name" required value="{{ old('name', $user->name) }}" class="w-full border rounded px-3 py-2"></div>
    <div><label class="text-sm">Email (tidak dapat diubah)</label><input disabled value="{{ $user->email }}" class="w-full border rounded px-3 py-2 bg-stone-100"></div>
    <div><label class="text-sm">Info akademik</label><input name="academic_info" value="{{ old('academic_info', $user->academic_info) }}" class="w-full border rounded px-3 py-2"></div>
    <button class="w-full bg-emerald-700 text-white rounded py-2">Simpan</button>
  </form>
</div>
@endsection
