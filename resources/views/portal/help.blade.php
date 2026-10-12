@extends('layout')
@section('title', 'Help Center')
@section('content')
<p class="page-eyebrow text-[#c90018] mt-2">SERVICES / HELP CENTER</p>
<h1 class="page-title">Help Center</h1>
<p class="text-sm text-slate-500 mt-1">Jawaban cepat atas pertanyaan umum — tanpa harus paham struktur organisasi.</p>

@foreach($groups as $category => $items)
  <h2 class="section-label mt-6 mb-2">{{ strtoupper($category) }} ({{ $items->count() }})</h2>
  <div class="space-y-2">
    @foreach($items as $f)
      <details class="bg-white rounded-2xl border border-[#d6d6d6] group">
        <summary class="cursor-pointer list-none flex items-center gap-3 p-4 font-bold text-sm text-slate-900 group-hover:text-[#002f80]">
          <span class="shrink-0 w-6 h-6 rounded-full grid place-items-center bg-[#002f80]/5 text-[#002f80] text-xs font-extrabold group-open:rotate-45 transition">+</span>
          <span class="min-w-0 flex-1">{{ $f->question }}</span>
          <a href="{{ route('faqs.show', $f) }}" class="shrink-0 text-[11px] text-slate-400 hover:text-[#002f80] font-bold" aria-label="Buka halaman FAQ">Open →</a>
        </summary>
        <p class="px-4 pb-4 pl-13 text-sm text-slate-600">{{ $f->answer }}</p>
      </details>
    @endforeach
  </div>
@endforeach

@if($groups->isEmpty())
  <div class="bg-white rounded-2xl border border-dashed border-[#d6d6d6] p-8 text-center mt-5">
    <p class="font-bold text-slate-700">Belum ada FAQ</p>
    <p class="text-sm text-slate-500 mt-1">Coba <a href="{{ route('search.index') }}" class="text-[#002f80] font-bold underline">pencarian</a> atau tanya langsung di bawah.</p>
  </div>
@endif

<div class="grid sm:grid-cols-2 gap-3 mt-6">
  <a href="{{ route('services.index') }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-5 block group">
    <div class="font-extrabold text-slate-900 group-hover:text-[#002f80]">Butuh bantuan manusia?</div>
    <div class="text-[13px] text-slate-500 mt-1">Ajukan layanan: transportasi, cuti, maintenance, dan lainnya.</div>
    <div class="text-xs font-bold text-[#002f80] mt-2 text-right">Ajukan →</div>
  </a>
  <a href="{{ route('ask.index') }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-5 block group">
    <div class="font-extrabold text-slate-900 group-hover:text-[#002f80]">Tanya Ask YPL</div>
    <div class="text-[13px] text-slate-500 mt-1">Jawaban berbasis dokumen resmi dengan sitasi sumber.</div>
    <div class="text-xs font-bold text-[#002f80] mt-2 text-right">Tanya →</div>
  </a>
</div>
@endsection
