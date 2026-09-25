@extends('layout')
@section('title', 'Masuk')
@section('content')
<div class="max-w-md mx-auto bg-white rounded shadow p-6">
  <h1 class="text-xl font-bold mb-4">Masuk</h1>
  <form method="post" action="/masuk" class="space-y-3">@csrf
    <div><label class="text-sm">Email</label><input name="email" type="email" required value="{{ old('email') }}" class="w-full border rounded px-3 py-2"></div>
    <div><label class="text-sm">Kata sandi</label><input name="password" type="password" required class="w-full border rounded px-3 py-2"></div>
    <label class="text-sm flex gap-2 items-center"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
    <button class="w-full bg-emerald-700 text-white rounded py-2">Masuk</button>
  </form>
  <p class="text-sm mt-3">Belum punya akun? <a class="underline" href="{{ route('register') }}">Daftar</a></p>
</div>
@endsection
