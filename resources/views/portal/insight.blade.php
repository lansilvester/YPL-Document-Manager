@extends('layout')
@section('title', 'YPL Insight')
@section('content')
<p class="page-eyebrow text-[#c90018] mt-2">PUBLIC / YPL INSIGHT</p>
<h1 class="page-title">YPL Insight</h1>
<p class="text-sm text-slate-500 mt-1">Etalase publik Yayasan Pendidikan Lokon: dokumen, angka, dan koleksi terbuka.</p>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
  @foreach([['Dokumen Publik', $stats['dokumen']], ['Kali Dibaca', $stats['views']], ['Agenda', $stats['agenda']], ['FAQ', $stats['faq']]] as [$label, $n])
    <div class="bg-white rounded-2xl border border-[#d6d6d6] p-4 text-center">
      <div class="text-2xl font-extrabold text-[#002f80] tabular-nums">{{ number_format($n) }}</div>
      <div class="text-[11px] font-bold tracking-widest text-slate-400 mt-1">{{ strtoupper($label) }}</div>
    </div>
  @endforeach
</div>

<h2 class="section-label mt-6 mb-2">TERBARU UNTUK PUBLIK</h2>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
  @forelse($docs as $d)
    @include('components.doc-card', ['d' => $d])
  @empty
    <p class="text-sm text-slate-400 sm:col-span-3 bg-white rounded-2xl border border-dashed p-8 text-center">Belum ada dokumen publik.</p>
  @endforelse
</div>

<div class="mt-6 text-center">
  <a href="{{ route('library.index', ['koleksi' => 'public']) }}" class="btn-primary">Lihat Koleksi Publik →</a>
</div>
@endsection
