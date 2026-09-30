@extends('layout')
@section('title', $title)
@section('content')
<div class="max-w-2xl mt-8">
  <p class="text-xs font-bold tracking-widest text-[#c90018]">{{ $eyebrow }}</p>
  <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#002f80] mt-1">{{ $title }}</h1>
  <p class="text-sm text-slate-500 mt-2">{{ $desc }}</p>
  <div class="flex flex-wrap gap-2 mt-5">
    @foreach($actions as $a)
      <a href="{{ $a['href'] }}" class="{{ ($a['primary'] ?? false) ? 'btn-primary' : 'btn-ghost' }}">{{ $a['label'] }}</a>
    @endforeach
    <a href="{{ route('home') }}" class="btn-ghost">← Beranda</a>
  </div>
  <p class="text-xs text-slate-400 mt-6">Modul penuh hadir di Phase 3–6 YPL SDL Blueprint.</p>
</div>
@endsection
