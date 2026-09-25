@extends('layout')
@section('title', 'Daftar')
@section('content')
<div class="max-w-md mx-auto bg-white rounded shadow p-6">
  <h1 class="text-xl font-bold mb-1">Daftar Akun</h1>
  <p class="text-sm text-stone-600 mb-4">Pendaftaran divalidasi sistem internal YPL — gunakan email resmi yayasan.</p>
  <form method="post" action="/daftar" class="space-y-3">@csrf
    <div><label class="text-sm">Nama</label><input name="name" required value="{{ old('name') }}" class="w-full border rounded px-3 py-2"></div>
    <div><label class="text-sm">Email YPL</label><input name="email" type="email" required value="{{ old('email') }}" class="w-full border rounded px-3 py-2"></div>
    <div><label class="text-sm">Info akademik (opsional)</label><input name="academic_info" value="{{ old('academic_info') }}" placeholder="cth: Guru Matematika / Kelas 8A" class="w-full border rounded px-3 py-2"></div>
    <div><label class="text-sm">Kata sandi</label><input name="password" type="password" required class="w-full border rounded px-3 py-2"></div>
    <div><label class="text-sm">Konfirmasi kata sandi</label><input name="password_confirmation" type="password" required class="w-full border rounded px-3 py-2"></div>
    <button class="w-full bg-emerald-700 text-white rounded py-2">Daftar</button>
  </form>
</div>
@endsection
