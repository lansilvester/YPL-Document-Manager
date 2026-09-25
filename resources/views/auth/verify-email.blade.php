@extends('layout')
@section('title', 'Verifikasi Email')
@section('content')
<div class="max-w-md mx-auto bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-6 text-center">
  <h1 class="text-xl font-extrabold text-[#002f80]">Verifikasi emailmu</h1>
  <p class="text-sm text-slate-500 mt-2">Kami sudah mengirim tautan verifikasi ke {{ auth()->user()->email }}. Verifikasi email sebelum mengunggah atau menulis dokumen.</p>
  <form method="post" action="{{ route('verification.send') }}" class="mt-4">@csrf<button class="btn-primary">Kirim ulang tautan</button></form>
</div>
@endsection